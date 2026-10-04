# Architecture — SchoolShare Technical Deep Dive

> **Maintained by:** Mahi Zeki Mukhtar / EthioNext
> **For:** Developers contributing to or maintaining SchoolShare

---

## Overview

SchoolShare is a **server-rendered PHP web application** built on the Laravel 11
framework. It provides Git-inspired version control concepts (checkpoints,
history, restore, diff) in a student-friendly GUI — no command-line required.

---

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                     Browser (Client)                    │
│         Bootstrap 5 + Alpine.js + CodeMirror            │
└──────────────────────┬──────────────────────────────────┘
                       │ HTTP(S)
                       ▼
┌─────────────────────────────────────────────────────────┐
│                  Cloudflare CDN (free)                  │
│      Caches static assets, DDoS protection, SSL         │
└──────────────────────┬──────────────────────────────────┘
                       │ Dynamic requests
                       ▼
┌─────────────────────────────────────────────────────────┐
│               cPanel Web Server (Apache)                │
│         mod_rewrite → routes all traffic to             │
│              schoolshare/public/index.php               │
└──────────┬───────────────────────────┬──────────────────┘
           │                           │
           ▼                           ▼
┌─────────────────────┐   ┌────────────────────────────┐
│   PHP 8.1+ / Laravel│   │  Local Filesystem Storage  │
│   (App Logic Layer) │   │  storage/app/uploads/      │
│                     │   │  {user}/{project}/{checkpoint}/ │
│   Routes → Controllers   └────────────────────────────┘
│   → Services → Models│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│     MySQL Database  │
│  users, projects,   │
│  checkpoints, files │
│  activity_log, etc. │
└─────────────────────┘
```

---

## Core Concept: The Checkpoint System

The checkpoint system is the heart of SchoolShare. It replaces Git commits
with a simple, understandable "snapshot" model.

### How a Checkpoint Works

```
User uploads files + writes a message
         │
         ▼
CheckpointService::create()
    1. Validate file sizes (100 MB per file)
    2. Validate user storage quota (1 GB total)
    3. For each file:
       a. Compute MD5 hash of file content
       b. Check if same hash exists in checkpoint_files for this project
       c. If YES → record reference (no copy = deduplication)
       d. If NO  → save file to storage/app/uploads/{user}/{project}/{checkpoint}/
    4. Insert row into `checkpoints` table
    5. Insert rows into `checkpoint_files` table
    6. Update `users.storage_used_bytes`
    7. Log to `activity_log`
         │
         ▼
Checkpoint created ✓
```

### Storage Layout

```
storage/app/uploads/
└── 1/                          ← user_id = 1
    └── 5/                      ← project_id = 5
        ├── 12/                 ← checkpoint_id = 12 (first checkpoint)
        │   ├── essay_draft.docx
        │   └── notes.txt
        └── 19/                 ← checkpoint_id = 19 (second checkpoint)
            ├── essay_final.docx   ← changed file (new copy stored)
            └── notes.txt          ← if same hash → only a DB reference,
                                      physically same file as checkpoint 12
```

### Download (ZIP creation)

```
DownloadController::downloadCheckpoint()
    1. Look up all checkpoint_files for this checkpoint_id
    2. Open a PHP ZipArchive in memory
    3. Add each file from storage path → ZIP entry
    4. Add SCHOOLSHARE_BY_ETHIONEXT.txt → ZIP root (branding Layer 4)
    5. Stream ZIP to browser with correct headers
```

### Restore

```
RestoreController::restore()
    Restore is NOT a revert — it creates a NEW checkpoint
    that copies files from the old checkpoint:

    1. Load all checkpoint_files from the target checkpoint
    2. Call CheckpointService::create() with those files
    3. Checkpoint message: "Restored from [original message] — [date]"
    4. New checkpoint appears at top of history timeline
```

---

## Database Schema (Detailed)

### `users`
```sql
id               BIGINT UNSIGNED PK
name             VARCHAR(255)
email            VARCHAR(255) UNIQUE
password         VARCHAR(255)
avatar           VARCHAR(500) NULL
storage_used_bytes BIGINT DEFAULT 0
plan             ENUM('free','pro') DEFAULT 'free'
last_active_at   TIMESTAMP NULL
email_verified_at TIMESTAMP NULL
remember_token   VARCHAR(100) NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### `projects`
```sql
id               BIGINT UNSIGNED PK
user_id          BIGINT FK → users.id (CASCADE DELETE)
name             VARCHAR(255)
slug             VARCHAR(255)         -- URL-friendly: "my-history-essay"
description      TEXT NULL
subject_tag      VARCHAR(100) NULL    -- "Math", "English", etc.
visibility       ENUM('public','private') DEFAULT 'private'
star_count       INT UNSIGNED DEFAULT 0
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX(user_id), INDEX(slug), INDEX(visibility, created_at)
```

### `project_collaborators`
```sql
id               BIGINT UNSIGNED PK
project_id       BIGINT FK → projects.id (CASCADE DELETE)
user_id          BIGINT FK → users.id (CASCADE DELETE)
role             ENUM('editor','viewer') DEFAULT 'viewer'
invited_at       TIMESTAMP
UNIQUE(project_id, user_id)
```

### `checkpoints`
```sql
id               BIGINT UNSIGNED PK
project_id       BIGINT FK → projects.id (CASCADE DELETE)
user_id          BIGINT FK → users.id
message          VARCHAR(500)
file_count       INT UNSIGNED DEFAULT 0
total_size_bytes BIGINT DEFAULT 0
created_at       TIMESTAMP
updated_at       TIMESTAMP

INDEX(project_id, created_at DESC)
```

### `checkpoint_files`
```sql
id               BIGINT UNSIGNED PK
checkpoint_id    BIGINT FK → checkpoints.id (CASCADE DELETE)
original_filename VARCHAR(500)       -- "My Essay Final.docx"
stored_path      VARCHAR(1000)       -- "uploads/1/5/12/essay_final.docx"
file_size_bytes  BIGINT
mime_type        VARCHAR(255)
content_hash     CHAR(32)            -- MD5 for deduplication
is_deleted       TINYINT(1) DEFAULT 0

INDEX(checkpoint_id), INDEX(content_hash)
```

### `activity_log`
```sql
id               BIGINT UNSIGNED PK
project_id       BIGINT FK → projects.id (CASCADE DELETE)
user_id          BIGINT FK → users.id
action           VARCHAR(100)        -- "checkpoint.created", "file.downloaded"
meta             JSON NULL           -- { "checkpoint_id": 12, "filename": "..." }
created_at       TIMESTAMP

INDEX(project_id, created_at DESC)
```

### `starred_projects`
```sql
id               BIGINT UNSIGNED PK
user_id          BIGINT FK → users.id (CASCADE DELETE)
project_id       BIGINT FK → projects.id (CASCADE DELETE)
created_at       TIMESTAMP
UNIQUE(user_id, project_id)
```

### `license_keys`
```sql
id               BIGINT UNSIGNED PK
key_hash         VARCHAR(255) UNIQUE -- bcrypt hash of the actual key
owner_email      VARCHAR(255)
plan             ENUM('single_site','whitelabel','saas')
issued_at        TIMESTAMP
revoked_at       TIMESTAMP NULL      -- NULL = active
```

---

## Service Layer

Business logic lives in `app/Services/`, not in controllers.

| Service | Responsibility |
|---|---|
| `CheckpointService` | Create checkpoints, validate quotas, store files, deduplication |
| `DiffService` | Generate text diffs between checkpoint file versions |
| `ActivityService` | Log actions to `activity_log` |
| `LicenseService` | Validate license keys, determine branding mode |

---

## Middleware

| Middleware | Purpose |
|---|---|
| `AddBrandingHeaders` | Adds `X-Powered-By: EthioNext-SchoolShare` to all responses |
| `CheckStorageQuota` | Blocks upload requests if user is at/over 1 GB limit |
| `ProjectAccess` | Checks if authenticated user has access to a project |

---

## Branding Protection System

```
Layer 1 — UI Layer
  app.blade.php → logo in navbar, footer credit
  Hardcoded in template, not driven by config
  (so removing it requires modifying template files)

Layer 2 — HTTP Headers
  AddBrandingHeaders middleware
  → X-Powered-By: EthioNext-SchoolShare

Layer 3 — License Check (server-side)
  AppServiceProvider::boot()
  → Checks license_keys table for a valid, non-revoked key
  → If self-hosted (APP_ENV=production) AND no valid key:
     → Sets app()->instance('branding_required', true)
     → Routes middleware redirects to /branding-required

Layer 4 — File Attribution
  DownloadController::downloadCheckpoint()
  → Adds SCHOOLSHARE_BY_ETHIONEXT.txt to every ZIP download

Layer 5 — Legal
  LICENSE.md clearly prohibits branding removal without a
  Commercial License, making unauthorized removal a
  license violation.
```

---

## File Viewer Architecture

The in-browser viewer (`FileViewController`) determines how to render a file
based on its MIME type:

```
FileViewController::view($fileId)
    │
    ├── Text types (text/plain, text/html, application/json, etc.)
    │   → resources/views/files/viewer-codemirror.blade.php
    │   → CodeMirror 6, read-only (edit mode available for text/*)
    │
    ├── application/pdf
    │   → resources/views/files/viewer-pdf.blade.php
    │   → PDF.js embedded viewer
    │
    ├── image/* (image/jpeg, image/png, etc.)
    │   → resources/views/files/viewer-image.blade.php
    │   → <img> with lightbox
    │
    ├── video/* or audio/*
    │   → resources/views/files/viewer-media.blade.php
    │   → HTML5 <video> / <audio>
    │
    ├── application/vnd.openxmlformats-officedocument.*
    │   (Word, Excel, PowerPoint)
    │   → resources/views/files/viewer-google-docs.blade.php
    │   → Google Docs Viewer iframe embed
    │   (requires file to be temporarily publicly accessible)
    │
    └── Everything else
        → resources/views/files/viewer-download.blade.php
        → File info card + download button
```

---

## Performance Considerations

| Technique | Where Applied |
|---|---|
| PHP OPcache | Server-level (cPanel config) |
| Route/config/view caching | Deploy-time (`php artisan *:cache`) |
| Eager loading | All Eloquent queries use `->with(...)` |
| Pagination | File lists, history, activity feed (20/page) |
| DB indexes | All foreign keys + frequently queried columns |
| Deduplication | MD5 hash prevents storing identical files twice |
| Cloudflare CDN | Static assets cached at edge |
| Chunked uploads | Files >10MB uploaded in chunks (no timeouts) |
| File cache | Project stats cached for 5 min (Laravel file driver) |

---

## Security Architecture

| Threat | Mitigation |
|---|---|
| Unauthorized file access | `ProjectAccess` middleware on all file routes |
| Path traversal | Filenames sanitized (`preg_replace` before storage) |
| Malicious file upload | MIME type checked server-side with `finfo_file()` |
| SQL injection | Eloquent ORM with parameterized queries |
| XSS | Blade auto-escapes all `{{ }}` output |
| CSRF | Laravel `VerifyCsrfToken` on all POST/PUT/DELETE |
| Brute force login | Laravel rate limiting on login route |
| Storage abuse | Quota enforced in `CheckpointService` before storing |
| Oversized uploads | PHP `upload_max_filesize` + server-side check |

---

*SchoolShare by EthioNext — ethionext.com.et*
*Developer: Mahi Zeki Mukhtar — mahizeki037@gmail.com*
