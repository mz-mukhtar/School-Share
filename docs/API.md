# API Reference — SchoolShare Internal Routes

> This document describes all HTTP routes in SchoolShare.
> These are server-rendered web routes (not a public REST API).
> All routes require authentication unless marked **[public]**.

---

## Authentication Routes

| Method | URL | Description |
|---|---|---|
| GET | `/` | Landing page **[public]** |
| GET | `/about` | About page **[public]** |
| GET | `/pricing` | Pricing page **[public]** |
| GET | `/sitemap.xml` | XML sitemap **[public]** |
| GET | `/register` | Registration form **[public]** |
| POST | `/register` | Submit registration **[public]** |
| GET | `/login` | Login form **[public]** |
| POST | `/login` | Submit login **[public]** |
| POST | `/logout` | Logout |
| GET | `/forgot-password` | Password reset form **[public]** |
| POST | `/forgot-password` | Send reset email **[public]** |
| GET | `/reset-password/{token}` | Reset password form **[public]** |
| POST | `/reset-password` | Submit new password **[public]** |

---

## Dashboard

| Method | URL | Description |
|---|---|---|
| GET | `/dashboard` | User dashboard (my projects) |

---

## Projects

| Method | URL | Description |
|---|---|---|
| GET | `/projects` | List my projects |
| GET | `/projects/create` | New project form |
| POST | `/projects` | Create a project |
| GET | `/projects/{slug}` | Project page (files, checkpoints) |
| GET | `/projects/{slug}/edit` | Edit project settings |
| PUT | `/projects/{slug}` | Update project settings |
| DELETE | `/projects/{slug}` | Delete project |

**URL pattern:** `/projects/{owner-slug}/{project-slug}` (future)
**Current:** `/projects/{project-slug}` (single-user owner)

---

## Explore (Public Projects)

| Method | URL | Description |
|---|---|---|
| GET | `/explore` | Browse public projects **[public]** |
| GET | `/explore?q=math` | Search public projects **[public]** |

---

## Checkpoints (Version Control)

| Method | URL | Description |
|---|---|---|
| GET | `/projects/{slug}/history` | Checkpoint history/timeline |
| GET | `/projects/{slug}/checkpoints/{id}` | View a single checkpoint |
| POST | `/projects/{slug}/checkpoints` | Create a new checkpoint (file upload) |
| POST | `/projects/{slug}/checkpoints/{id}/restore` | Restore a checkpoint |

---

## Files — Viewer & Editor

| Method | URL | Description |
|---|---|---|
| GET | `/files/{id}/view` | View file in browser |
| GET | `/files/{id}/edit` | Open file in in-browser editor |
| POST | `/files/save-edit` | Save in-browser edit as new checkpoint |
| GET | `/files/{id}/raw` | Serve raw file content (for iframes/viewers) |

---

## Downloads

| Method | URL | Description |
|---|---|---|
| GET | `/projects/{slug}/download` | Download latest checkpoint as ZIP |
| GET | `/checkpoints/{id}/download` | Download specific checkpoint as ZIP |
| GET | `/files/{id}/download` | Download a single file |

---

## Diff View

| Method | URL | Description |
|---|---|---|
| GET | `/projects/{slug}/diff` | Diff view (compare two checkpoints) |
| GET | `/projects/{slug}/diff?from={id}&to={id}` | Specific checkpoint comparison |

---

## Collaborators

| Method | URL | Description |
|---|---|---|
| GET | `/projects/{slug}/collaborators` | List collaborators |
| POST | `/projects/{slug}/collaborators` | Invite collaborator by email |
| DELETE | `/projects/{slug}/collaborators/{userId}` | Remove collaborator |

---

## Activity

| Method | URL | Description |
|---|---|---|
| GET | `/projects/{slug}/activity` | Project activity feed |

---

## Stars

| Method | URL | Description |
|---|---|---|
| POST | `/projects/{slug}/star` | Star a project |
| DELETE | `/projects/{slug}/star` | Unstar a project |

---

## Profile

| Method | URL | Description |
|---|---|---|
| GET | `/profile` | View profile |
| GET | `/profile/edit` | Edit profile form |
| PUT | `/profile` | Update profile |
| DELETE | `/profile` | Delete account |
| POST | `/profile/avatar` | Upload avatar |

---

## License System

| Method | URL | Description |
|---|---|---|
| GET | `/license` | License activation page |
| POST | `/license/activate` | Activate a license key |
| GET | `/branding-required` | Shown for unlicensed self-hosts **[public]** |

---

## Admin (Owner Only)

| Method | URL | Description |
|---|---|---|
| GET | `/admin` | Admin dashboard |
| GET | `/admin/users` | List all users |
| GET | `/admin/storage` | Storage usage overview |
| DELETE | `/admin/users/{id}` | Delete a user |

---

## Search

| Method | URL | Description |
|---|---|---|
| GET | `/search?q=essay` | Search projects (name, description, tag) |

---

## Key HTTP Response Codes

| Code | Meaning in SchoolShare |
|---|---|
| 200 | Success |
| 302 | Redirect (after login, after checkpoint creation, etc.) |
| 401 | Not logged in → redirect to /login |
| 403 | No permission to access this project |
| 404 | Project or file not found |
| 413 | File too large (100 MB limit) |
| 422 | Validation error (form submitted with invalid data) |
| 429 | Too many requests (rate limit hit) |
| 500 | Server error (check `storage/logs/laravel.log`) |

---

## Key Response Headers (All Responses)

```
X-Powered-By: EthioNext-SchoolShare
Content-Type: text/html; charset=UTF-8
```

---

## File Download Headers

```
Content-Type: application/zip
Content-Disposition: attachment; filename="project-name-checkpoint-2026-10-04.zip"
Content-Length: {bytes}
```

---

## Future: Public REST API (v2.0)

A public REST API is planned for future versions to support:
- Mobile app (iOS/Android)
- Third-party integrations
- Command-line client (`schoolshare` CLI tool)

The API will use bearer token authentication and return JSON.
Documentation will be published at `/api/docs` when available.

---

*SchoolShare by EthioNext — ethionext.com.et*
*Developer: Mahi Zeki Mukhtar — mahizeki037@gmail.com*
