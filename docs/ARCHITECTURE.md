# SchoolShare architecture

This document describes the code currently in the repository. It is not a
future design specification. Known functional limitations are linked to the
[repository review](REPOSITORY_REVIEW_2026-10-05.md).

## Runtime shape

```text
Browser
  │  server-rendered HTML, Bootstrap CDN assets, selected module CDNs
  ▼
Laravel 13 application
  ├─ web routes + OTP/auth middleware
  ├─ controllers and Eloquent models
  ├─ private local disk: storage/app/private
  ├─ public local disk:  storage/app/public (avatars/public assets)
  └─ relational database
```

The browser uses server-rendered Blade. Bootstrap is delivered from jsDelivr;
PDF.js 6.4.299 is loaded as a module from jsDelivr; CodeMirror modules are
loaded from esm.sh. These external assets are not installed through the PHP
dependency graph and should be reviewed as part of frontend dependency work.

## Access model

All project/file/checkpoint/folder/collaborator web routes require `auth` and
`otp.verified`. Public profile pages and marketing pages do not. Projects use
these current access rules:

| Actor | Public project | Private project | Can edit files/checkpoints |
| --- | --- | --- | --- |
| Guest | No project routes; public profile only | No | No |
| Authenticated unrelated user | Can access public project | No | No |
| Project owner | Yes | Yes | Yes |
| Viewer collaborator | Yes | Yes | No |
| Editor collaborator | Yes | Yes | Yes |

Only the owner manages collaborators and project settings. Owners and editor
collaborators can create folders, upload, edit, restore, and delete files or
checkpoints; viewers cannot make project-content changes.

Nested checkpoint, file, folder, comment, and collaborator routes use scoped
binding beneath the project. Folder IDs supplied to upload/move operations are
validated against that project.

## Data model

| Record | Responsibility |
| --- | --- |
| `users` | Authentication, profile, plan field, storage counter, admin flag |
| `projects` | Owner, name/slug, visibility, star count |
| `project_collaborators` | Project-user membership with `editor` or `viewer` role |
| `project_folders` | Project-scoped parent/child folders |
| `project_files` | Logical current file identity, current version pointer, folder |
| `file_versions` | Immutable storage path, size, MIME type, sequence, file, and checkpoint reference |
| `checkpoints` | Project event with title/message, reported upload size, and snapshot manifest |
| `checkpoint_*_snapshots` | Immutable file names/types/folder paths captured for a checkpoint |
| `stored_blobs` | One private-disk object, its billing owner, and byte charge |
| `activities` | Polymorphic activity records filtered by current project access |
| `comments`, `follows`, `starred_projects`, `project_forks` | Social/project features |

`stored_blobs` is introduced by the Group 3 migration. It is only authoritative
after that migration and reconciliation have been run on the deployed database
and private disk.

## Project-file lifecycle

### Upload and browser edit

1. The controller validates membership, filename safety, request limits, and
   project capacity.
2. `StorageLifecycle` serializes private-local-disk operations with a local
   file lock.
3. It checks the ledger for unreconciled legacy versions and performs a
   conditional quota reservation.
4. A UUID-backed object is registered in `stored_blobs`, then written and
   verified on the private disk.
5. A transaction creates the checkpoint/version and updates the logical file.
6. If metadata creation fails, an unreferenced newly-created blob is cleaned up;
   if cleanup itself fails, the charge/ledger remains for a later retry.

The current implementation assumes one host and the local `storage` directory.
It is not a distributed lock or object-storage coordination scheme.

### Restore and deletion

Restore rebuilds the recorded folder tree and logical files from immutable
checkpoint manifests. It creates new `file_versions` referencing retained
blobs; it does not copy bytes or add a storage charge. Deleted logical files
are soft-deleted while history references remain. A blob is released only when
no file-version or snapshot reference remains and private-disk deletion
succeeds. Checkpoints created before manifest support fail safely on restore.

### Reconciliation and cleanup

`php artisan schoolshare:recalculate-storage` imports/reconciles version paths
and identifiable legacy private files into the blob ledger. It does not delete
objects by default. `--cleanup` is destructive: it removes unreferenced
ledger-backed objects and expired generated exports. See
[SELF_HOSTING.md](../SELF_HOSTING.md).

## Bounded operations

Current defaults in `config/schoolshare.php` are:

| Operation | Bound |
| --- | --- |
| Upload | 20 files, 200 MiB batch, 100 MiB per file |
| Browser editor / README | 512 KiB |
| Text diff | 64 KiB and 1,000 lines per input |
| Project entries | 1,000 files/folders |
| File-version history | 10,000 versions per project |
| ZIP/fork bytes | 200 MiB |
| Folder depth | 32 |
| Cooperative operation budget | 30 seconds |

Uploads/restores, editing, previews, diffs, downloads/ZIP exports, and forks
also have named route rate limits. PHP and reverse-proxy body/timeout limits
still need deployment configuration.

## File views and ZIP export

Text/code content is read through bounded streams. PDFs use pinned PDF.js with
`isEvalSupported: false`. Office documents are currently pointed at Google Docs
Viewer, which cannot fetch an authenticated private download URL; users should
download those files instead. Synchronous Office diffing is disabled.

Current project ZIP export validates every folder/file segment, rejects
traversal, cycles, invalid parent references, name collisions, missing objects,
and oversized projects. It exports current file versions only. It currently
does **not** insert `SCHOOLSHARE_BY_ETHIONEXT.txt`; the source-license branding
promise is therefore not implemented.

## Known architectural limits

- A clean and populated MySQL/MariaDB deployment test is still required for the
  chosen production engine.
- Group 4 OTP hardening and Group 5 frontend dependency maintenance remain
  open.

See the review for evidence, remediation status, and deployment restrictions.
