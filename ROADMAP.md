# SchoolShare — Public Roadmap

> **Last updated:** October 2026
> **Maintained by:** Mahi Zeki Mukhtar / EthioNext (ethionext.com.et)

This document outlines what has been built, what is currently being built,
and what is planned for the future.

Want to contribute? See [CONTRIBUTING.md](CONTRIBUTING.md).
Have a feature request? [Open an issue](https://github.com/mz-mukhtar/School-Share/issues).

---

## ✅ Completed

### Phase 1 — Repository Setup & Documentation *(October 2026)*
- [x] Laravel 11 project scaffold
- [x] GitHub repository initialized
- [x] README, LICENSE, CONTRIBUTING, CODE_OF_CONDUCT
- [x] SECURITY, SELF_HOSTING, CHANGELOG docs
- [x] Architecture and API documentation
- [x] Application configuration file

---

## 🚧 In Progress

### Phase 2 — Foundation, Auth & Branding
- [ ] User registration and login (Laravel Breeze)
- [ ] EthioNext branding in layout (navbar, footer)
- [ ] Branding response headers middleware
- [ ] Database migrations for users table

### Phase 3 — Landing Page
- [ ] Marketing landing page (hero, features, pricing, contact)
- [ ] About page
- [ ] Pricing page
- [ ] SEO meta tags and sitemap

---

## 📋 Planned

### Phase 4 — Projects
- [ ] Create / manage projects (like repositories)
- [ ] Public / private visibility
- [ ] Subject tags (Math, English, Science, etc.)
- [ ] Browse public projects (Explore page)
- [ ] Star / bookmark projects

### Phase 5 — File Upload & Checkpoints
- [ ] Drag-and-drop file upload
- [ ] Checkpoint messages
- [ ] Chunked upload for large files
- [ ] 100 MB per file / 1 GB total storage quota
- [ ] Content-based deduplication (MD5 hash)

### Phase 6 — In-Browser Viewer & Editor
- [ ] Text/code viewer with CodeMirror 6
- [ ] PDF viewer with PDF.js
- [ ] Image/video/audio native viewers
- [ ] Office files via Google Docs Viewer
- [ ] In-browser text editor → save as new checkpoint

### Phase 7 — Download & Version History
- [ ] Visual checkpoint timeline
- [ ] Download any checkpoint as ZIP
- [ ] Restore any previous checkpoint
- [ ] EthioNext attribution in ZIP files

### Phase 8 — Diff View ("What Changed?")
- [ ] Side-by-side text diff view
- [ ] File change summary per checkpoint
- [ ] Compare any two checkpoints

### Phase 9 — Collaboration & Activity
- [ ] Invite teammates by email
- [ ] Editor / viewer permissions
- [ ] Activity feed
- [ ] Email notifications

### Phase 10 — Storage, Profile & Business
- [ ] Profile page with storage usage bar
- [ ] Storage quota enforcement
- [ ] Pricing page (Free / Pro / White-label)
- [ ] License key system for white-label self-hosting
- [ ] Admin dashboard

### Phase 11 — Polish, Search & Launch *(v1.0.0)*
- [ ] Project search by name/subject
- [ ] Rate limiting on sensitive endpoints
- [ ] Security audit
- [ ] Performance / load testing
- [ ] v1.0.0 public launch

---

## 🔮 Future (Post v1.0)

These are ideas for future versions, not yet scheduled:

- [ ] **Organizations / School Accounts** — group projects under a school
- [ ] **Mobile app** — iOS and Android (React Native or Flutter)
- [ ] **Offline mode** — work without internet, sync when connected
- [ ] **Real-time collaboration** — Google Docs-style simultaneous editing
- [ ] **Amharic language support** — full UI localization
- [ ] **AI-powered suggestions** — "Your file looks like it changed a lot — add a checkpoint?"
- [ ] **Submission system** — teachers can request submissions; students "submit" a checkpoint
- [ ] **Assignment templates** — teachers create project templates for students to fork
- [ ] **Google Drive sync** — import/export files from Google Drive
- [ ] **Stripe payments** — in-app payment for Pro plans
- [ ] **Two-factor authentication (2FA)**
- [ ] **Self-hosted S3-compatible storage** (Cloudflare R2, MinIO)

---

## 📬 Suggest a Feature

Open an issue at:
https://github.com/mz-mukhtar/School-Share/issues/new?labels=enhancement

Or contact directly:
- 📧 mahizeki037@gmail.com
- 🌐 https://ethionext.com.et

---

*SchoolShare by EthioNext — Built for students, by developers who care.*
