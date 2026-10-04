# SchoolShare — Implementation Plan
### by EthioNext (ethionext.com.et)
> **Last updated:** October 2026 | **Status:** Building

---

## ✅ Decisions Locked

| Decision | Choice |
|---|---|
| Language | English only |
| Hosting | cPanel (shared/VPS) |
| Domain | ethionext.com.et |
| Users | Individual students (orgs = future) |
| File limit | 100 MB per file, 1 GB per user total |
| Business | Open source core + paid white-label |
| GitHub Repo | https://github.com/mz-mukhtar/School-Share |

---

## 👤 Developer & Contact

| Field | Value |
|---|---|
| **Developer** | Mahi Zeki Mukhtar |
| **Email** | mahizeki037@gmail.com |
| **Phone** | +251992194042 |
| **GitHub** | https://github.com/mz-mukhtar/School-Share |
| **Brand** | EthioNext · ethionext.com.et |

*This info appears on: landing page footer, README.md, SECURITY.md, and the in-app About page.*

---

## 🧰 Tech Stack

| Layer | Technology | Why |
|---|---|---|
| **Backend** | PHP 8.1+ / Laravel 11 | cPanel native, mature, great ORM |
| **Database** | MySQL 8 | Built into every cPanel plan |
| **File Storage** | Local filesystem (`storage/app/uploads/`) | No S3 needed; cPanel disk = storage |
| **Frontend** | Blade templates + Bootstrap 5 (CDN) | Server-rendered, no build step |
| **JS** | Alpine.js (CDN) | Lightweight reactivity, no Node build |
| **Code Editor** | CodeMirror 6 (CDN) | In-browser text/code editing |
| **PDF Viewer** | PDF.js (CDN, Mozilla) | View PDFs without downloading |
| **Office Viewer** | Google Docs Viewer (iframe embed) | View .docx/.pptx/.xlsx in browser |
| **Image Viewer** | Native `<img>` tag + lightbox | View images instantly |
| **Auth** | Laravel Breeze (sessions) | Built-in login/register/reset |
| **ZIP** | PHP ZipArchive (built-in) | Download ZIPs server-side |
| **Diff** | `jfcherng/php-diff` (Composer) | Text file diff for "What Changed?" |
| **Cache** | Laravel file cache driver | No Redis needed to start |
| **CDN** | Cloudflare (free tier) | Protect origin, cache static assets |

---

## 📋 FULL PHASE ROADMAP

---

### PHASE 1 — Repository Setup & Documentation
> **Goal**: GitHub repo ready, all docs written, codebase scaffolded

- [x] Create Laravel 11 project
- [x] Initialize Git, push to https://github.com/mz-mukhtar/School-Share
- [x] Write `README.md`
- [x] Write `LICENSE.md` (dual license — SCL v1.0)
- [x] Write `CONTRIBUTING.md`
- [x] Write `CODE_OF_CONDUCT.md`
- [x] Write `SECURITY.md`
- [x] Write `SELF_HOSTING.md`
- [x] Write `CHANGELOG.md`
- [x] Write `ROADMAP.md`
- [x] Write `docs/ARCHITECTURE.md`
- [x] Write `.env.example`
- [x] Write `config/schoolshare.php`
- [x] `.gitignore`

**Status**: ✅ Completed

---

### PHASE 2 — Foundation, Auth & Branding
> **Goal**: Running Laravel app with login/register and full EthioNext branding

- [x] Install Laravel Breeze
- [x] `database/migrations/` → extend `users` table: `storage_used_bytes`, `plan`, `last_active_at`
- [x] `resources/views/layouts/app.blade.php` → master layout
- [x] `resources/views/layouts/guest.blade.php` → login/register layout with branding
- [x] `app/Http/Middleware/AddBrandingHeaders.php` → X-Powered-By header
- [x] Auth pages: login, register, forgot-password — styled with Bootstrap 5
- [x] `resources/views/dashboard.blade.php` → empty dashboard placeholder
- [ ] Enable PHP OPcache settings
- [ ] `app/Providers/AppServiceProvider.php` → boot-time license check (Layer 3)

**Status**: 🟡 Nearly Completed (License check pending)

---

### PHASE 3 — Landing Page
> **Goal**: Public-facing marketing page at the root URL

- [x] `resources/views/welcome.blade.php` → full landing page
- [x] `routes/web.php` → `GET /` → `welcome.blade.php`
- [x] Custom CSS for landing page sections
- [ ] `resources/views/about.blade.php` → /about page
- [ ] `resources/views/pricing.blade.php` → /pricing page
- [ ] SEO meta tags: title, description, og:image, canonical URL
- [ ] Sitemap: `routes/web.php` → GET /sitemap.xml

**Status**: 🟡 Partial (Missing about/pricing/sitemap)

---

### PHASE 4 — Projects (Repositories)
> **Goal**: Students can create and manage projects

- [x] `database/migrations/` → `projects`, `project_collaborators`, `starred_projects` tables
- [x] `app/Models/Project.php`
- [x] `app/Http/Controllers/ProjectController.php`
- [x] `resources/views/projects/index.blade.php`
- [x] `resources/views/projects/create.blade.php`
- [x] `resources/views/projects/show.blade.php`
- [x] `resources/views/projects/edit.blade.php`
- [x] Subject tag dropdown (Omitted by design decision)
- [x] Public/private toggle
- [x] `resources/views/explore.blade.php` → browse public projects
- [x] Star/bookmark projects

**Status**: ✅ Completed

---

### PHASE 5 — File Upload & Checkpoints
> **Goal**: Core version control — upload files, create checkpoints

- [x] `database/migrations/` → `checkpoints`, `checkpoint_files` tables
- [x] `app/Models/Checkpoint.php`, `app/Models/CheckpointFile.php`
- [x] `app/Http/Controllers/CheckpointController.php` (Acting as Service)
- [x] Upload UI: drag-and-drop zone
- [ ] Chunk upload for large files (>10MB)
- [x] Checkpoint message textarea
- [x] File Size & Quota Validation limits (1GB total)
- [x] Content-based deduplication

**Status**: ✅ Completed

---

### PHASE 6 — In-Browser Viewer & Editor
> **Goal**: View and edit files without downloading

- [x] `app/Http/Controllers/FileController.php` (Acting as FileViewController)
- [x] `resources/views/projects/files/show.blade.php` → unified viewer
- [x] Text/code files: **CodeMirror 6** (read-only + toggle edit mode)
- [x] PDF: **PDF.js** embedded viewer
- [x] Images: `<img>` with zoom controls
- [x] Video/Audio: HTML5 player
- [x] Office files: **Google Docs Viewer** iframe
- [x] Edit mode (text files only) with direct save to new checkpoint
- [x] Authorization validation

**Status**: ✅ Completed

---

### PHASE 7 — Download & Version History
> **Goal**: Browse past checkpoints, download any version

- [x] `ZipController.php`
- [x] Checkpoint timeline integrated into Project Show view
- [x] `checkpoints/show.blade.php` → list of files in checkpoint
- [x] Restore previous checkpoint
- [ ] Cache checkpoint list

**Status**: ✅ Completed (Caching optional but restore is working)

---

### PHASE 8 — "What Changed?" Diff View
> **Goal**: Show what's different between versions

- [x] `composer require jfcherng/php-diff`
- [x] Diff view implemented in FileController
- [x] Diff UI integrated in the browser
- [x] Links to view what changed

**Status**: ✅ Completed

---

### PHASE 9 — Collaboration & Activity
> **Goal**: Teammates can share a project

- [x] `CollaboratorController` → invite, remove
- [x] `ActivityLog` tracking
- [x] Invite form
- [x] Activity feed (feed route and FeedController)
- [x] Project access middleware logic
- [ ] Laravel Mail → send invite notification email
- [ ] In-app notifications

**Status**: 🟡 Partial (Core functional, emails pending)

---

### PHASE 10 — Storage, Profile & Business Features
> **Goal**: Quotas enforced, profile polished, business layer live

- [x] Public Profile view with contribution map & following/followers
- [x] Private Profile with storage limits
- [x] Enforced storage limits based on configuration
- [ ] `app/Console/Commands/RecalculateStorage.php`
- [ ] Pricing page (Free / Pro / White-label)
- [ ] License key validation
- [ ] Admin panel
- [ ] Stripe integration

**Status**: 🟡 Partial (Missing business/admin features)

---

### PHASE 11 — Polish, Search & Launch
> **Goal**: Production-ready, fast, beautiful

- [x] Search projects by name/subject (`ExploreController`)
- [ ] 404, 403, 500 error pages
- [ ] Rate limiting on upload
- [ ] Cloudflare / Production caching
- [ ] Security audit

**Status**: 🟡 Partial (Search implemented, polish pending)

---

## 📌 Phase Status Summary

| Phase | Description | Status |
|---|---|---|
| Phase 1 | Repository Setup & Documentation | ✅ Completed |
| Phase 2 | Foundation, Auth & Branding | ✅ Completed |
| Phase 3 | Landing Page | 🟡 Partial |
| Phase 4 | Projects | ✅ Completed |
| Phase 5 | File Upload & Checkpoints | ✅ Completed |
| Phase 6 | In-Browser Viewer & Editor | ✅ Completed |
| Phase 7 | Download & Version History | ✅ Completed |
| Phase 8 | Diff View | ✅ Completed |
| Phase 9 | Collaboration & Activity | 🟡 Partial (Emails pending) |
| Phase 10 | Storage, Profile & Business | 🟡 Partial |
| Phase 11 | Polish, Search & Launch | 🟡 Partial |
