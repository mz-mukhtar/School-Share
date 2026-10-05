# SchoolShare web-route reference

SchoolShare has server-rendered web routes, not a public REST API. State-changing
browser requests use Laravel's session/CSRF model and normally redirect with
session messages. JSON is returned only when a request explicitly expects it or
an endpoint is designed that way (for example, browser text editing).

All project-management routes require an authenticated, OTP-verified account
unless noted otherwise. `{project}` resolves by project slug; nested resources
are scoped to that project.

## Public routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/` | Landing page; authenticated users are redirected to dashboard |
| GET | `/about`, `/pricing`, `/guide`, `/terms` | Static pages |
| GET | `/sitemap.xml` | Sitemap |
| GET | `/u/{username}` | Public profile |
| GET | `/u/{username}/followers` | Public follower list |
| GET | `/u/{username}/following` | Public following list |
| GET/POST | `/register`, `/login` | Guest registration/login |
| GET/POST | `/forgot-password` | Guest password-reset request |
| GET | `/reset-password/{token}` | Password-reset form |
| POST | `/reset-password` | Set reset password |

## Authenticated verification/account routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET/POST | `/otp/verify` | Display/submit the custom OTP verification form |
| POST | `/otp/resend` | Resend OTP; `throttle:6,1` |
| GET | `/verify-email` | Framework email-verification notice |
| GET | `/verify-email/{id}/{hash}` | Signed framework verification link |
| POST | `/email/verification-notification` | Framework verification resend |
| GET/POST | `/confirm-password` | Password confirmation |
| PUT | `/password` | Update password |
| POST | `/logout` | Log out |

## OTP-verified user routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/dashboard` | Dashboard |
| GET | `/feed` | Authenticated activity feed |
| GET | `/explore` | Browse/search public projects; not a guest route |
| POST | `/users/{user}/follow` | Toggle follow |
| GET | `/notifications` | List notifications |
| POST | `/notifications/mark-all-read` | Mark notifications read |
| GET | `/notifications/{id}/read` | Mark one notification read |
| GET | `/profile` | Profile form |
| PATCH | `/profile` | Update profile |
| DELETE | `/profile` | Delete account after password confirmation |

## Project routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/projects` | List owned projects |
| GET/POST | `/projects/create`, `/projects` | Project form/create |
| GET | `/projects/{project}` | Project tree, checkpoint list, README rendering |
| GET | `/projects/{project}/edit` | Owner settings form |
| PUT/PATCH | `/projects/{project}` | Update owner settings |
| DELETE | `/projects/{project}` | Delete project and its ledger-backed file data |
| POST | `/projects/{project}/star` | Toggle star; current access required |
| POST | `/projects/{project}/fork` | Fork public project; `throttle:forks` |

The current route identity is globally resolved by slug. Slugs are only created
as owner-scoped unique values, so duplicate-owner slug behavior remains a known
product defect; do not build an external integration on that identity.

## Nested project routes

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/projects/{project}/checkpoints` | Checkpoint list |
| GET/POST | `/projects/{project}/checkpoints/create`, `/projects/{project}/checkpoints` | Upload form/create; `throttle:uploads` |
| GET | `/projects/{project}/checkpoints/{checkpoint}` | Checkpoint detail |
| POST | `/projects/{project}/checkpoints/{checkpoint}/restore` | Restore surviving logical files; `throttle:uploads` |
| DELETE | `/projects/{project}/checkpoints/{checkpoint}` | Delete checkpoint/version references |
| POST | `/projects/{project}/checkpoints/{checkpoint}/comments` | Create comment |
| DELETE | `/projects/{project}/comments/{comment}` | Delete permitted comment |
| GET | `/projects/{project}/files/{file}` | File viewer; `throttle:file-views` |
| GET | `/projects/{project}/files/{file}/download` | Download selected/current version; `throttle:downloads` |
| GET | `/projects/{project}/files/{file}/diff?from={version}&to={version}` | Bounded text/code diff; `throttle:diffs` |
| PUT | `/projects/{project}/files/{file}` | Rename/move or save bounded text content; `throttle:editor` |
| DELETE | `/projects/{project}/files/{file}` | Delete logical file/version references |
| POST | `/projects/{project}/folders` | Create folder (owner-only) |
| DELETE | `/projects/{project}/folders/{folder}` | Delete folder subtree |
| GET | `/projects/{project}/download-zip` | Download current project ZIP; `throttle:downloads` |
| POST | `/projects/{project}/collaborators` | Add collaborator by username and role |
| PUT | `/projects/{project}/collaborators/{collaborator}` | Change role |
| DELETE | `/projects/{project}/collaborators/{collaborator}` | Remove collaborator |

There is no historical checkpoint ZIP route, raw-file endpoint, standalone
file-editor endpoint, project activity endpoint, license-activation route, or
public API endpoint in this repository.

## Administration

All routes below require `auth`, `otp.verified`, and `is_admin`:

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/admin` | Dashboard |
| GET | `/admin/users` | User list |
| GET | `/admin/users/{user}` | User detail |
| PATCH | `/admin/users/{user}/plan` | Change plan field |
| GET | `/admin/upgrades` | Upgrade requests |
| PATCH | `/admin/upgrades/{upgradeRequest}/approve` | Approve request |
| PATCH | `/admin/upgrades/{upgradeRequest}/reject` | Reject request |

## Error and response behavior

Browser routes generally redirect unauthenticated users to login and return
403/404 for authorization or scoped-binding failures. Validation normally
returns 302 plus session errors for HTML requests and 422 JSON for JSON
requests. Bounded storage/export operations can return 409, 413, or 503;
throttled routes return 429. These are web behavior conventions, not a stable
machine-consumable API contract.
