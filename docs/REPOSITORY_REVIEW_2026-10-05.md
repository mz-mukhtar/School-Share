# SchoolShare repository review — current reassessment

Updated: 11 October 2026

Reviewed application base: `ec62e40`, plus the October 11 working-tree release
repairs described below. These changes have not been committed or deployed by
this verification pass.

Original pre-remediation baseline: `c6bf19b`

Scope: current source, migrations, configuration, repository tests, dependencies,
documentation, and production cache compilation with isolated temporary paths.
This update did not deploy the application, change the existing development
database/environment, or repeat the complete browser workflow.

## Current assessment

**Release repairs R01–R05 are implemented; production acceptance remains open.**
The October 5 engineering rating was 7.5/10, up from the original 4/10; that
historical score is retained below and has not been recalculated for this pass.

The repository is substantially stronger than the baseline. Security Groups
1–5 have been remediated in code, storage ownership and checkpoint snapshots
have explicit domain services, the build dependencies are reproducible, and
the committed regression suite passes. The previous XSS, privacy leaks,
archive traversal, broken checkpoint/diff/comment paths, plaintext OTPs, and
frontend advisory chain must not be presented as unchanged current findings.

The October 11 repairs preserve/backfill missing usernames, remove the admin
formatter's `intl` dependency, repair its plan-edit route, group admin search
filters, lock pending upgrade creation, and standardize redacted delivery errors
for OTP/invitation/upgrade mail. A newly reported critical npm advisory was also
resolved by updating `concurrently` and `shell-quote` within the existing ranges.
Production-engine upgrades, concurrent acceptance, real mail, and backup recovery
remain unverified. Repository checks do not close those deployment gates.

## Verification evidence

These checks were rerun for this reassessment unless explicitly marked as
earlier evidence:

| Check | Current result | Boundary of the evidence |
| --- | --- | --- |
| `php artisan test --compact` | **207 passed; 1,027 assertions** | SQLite in-memory database, fake storage/mail where used; not a production-engine or live-SMTP test |
| `npm test` and `node tests/JavaScript/PdfPreview.test.mjs` | **5 JavaScript cases passed** | Simulated browser/library objects; npm's process runner reports one file, direct execution confirms all five cases |
| `npm run build` | Passed; Vite 8.3.2 | Builds the Vite scaffold, which current application layouts do not load through `@vite` |
| `composer validate --strict --no-check-publish` | Passed | Manifest/lockfile validation, not deployment verification |
| `npm run audit:security` | **0 vulnerabilities** | Fresh online registry audit after the locked update; does not audit hardcoded CDN assets |
| `composer audit --locked` | **No security advisories** | Current online registry results for the locked PHP graph; not proof of application security |
| Fresh locked npm installation | **Passed: `npm ci --ignore-scripts --offline`** | Clean installation using cached package archives; build/tests rerun afterward, with online audit separately |
| SQLite migration chain | Exercised successfully by the repository suite | Includes populated legacy-OTP and new username/error-repair cases; not populated MySQL/MariaDB upgrade proof |
| Release regression coverage | **26 additional cases** | Identity, admin, upgrade retry/validation, redaction/failure recovery, and idempotent/chunked data repairs; no concurrent production-engine run |
| PHP formatting / platform | Pint and non-dev Composer platform checks passed | Local PHP lacks `intl` and `pdo_mysql`; admin pages succeed without `intl`, but MySQL/MariaDB cannot be tested in this runtime |
| Production cache compilation | Config, routes, and Blade views passed | Temporary paths with production/debug/secure-session/SMTP overrides; safe effective fields checked, temporary caches removed; no live SMTP connection |
| Existing development instance | **10 migrations pending; local/debug/log-mail configuration** | Read-only status check; 8 previous migrations plus 2 new repairs were not applied to existing user data |
| Real browser / responsive UI | Earlier evidence only | Original/remediation checks included responsive widths and PDF navigation; full current end-to-end browser acceptance was not rerun |

The October 5 diagnostic probes are historical evidence. Their failures are now
covered by normal regressions in `ProfileTest`, `AdminUserManagementTest`, and
`ReleaseDataRepairTest`. New tests reproduced blank-username and admin-page
failures before the fixes, then passed afterward. Existing application rows and
uploads were not modified; migrations were exercised only in test databases.

The repaired npm graph is `concurrently` 10.0.6 → `shell-quote` 1.12.0. The prior
10.0.5 → 1.9.0 graph produced two critical findings for
[GHSA-pqg4-j6r4-53mv](https://github.com/advisories/GHSA-pqg4-j6r4-53mv).
Only these two lockfile package entries changed; no dependency was added.

Installed PHP/Laravel/PHPUnit versions are 8.5.11 / 13.34.0 / 12.5.37.
The current Composer lock requires PHP 8.4.1+, despite the manifest allowing
`^8.3`. The frontend build declares Node.js 22.12+ and npm 10+.

## Security findings: current status

“Fixed” below means implemented in the repository and covered by relevant
regression checks. It does not mean every deployment has applied the required
migrations, adopted legacy blobs, or configured production infrastructure.

| Baseline ID / group | Original issue | Current implementation and status |
| --- | --- | --- |
| S01 / Group 1 | Active HTML in README Markdown | **Fixed:** raw HTML is escaped, unsafe links are disabled, and rendering inputs are bounded |
| S02 / Group 1 | Vulnerable PDF.js integration | **Fixed:** pinned PDF.js/worker 6.4.299, evaluation disabled, safe failure fallback, five JavaScript regression cases |
| S03 / Group 2 | Private projects entered My Projects search | **Fixed:** search alternatives stay grouped beneath the owner's relationship |
| S04 / Group 2 | Private checkpoint/activity metadata leaked | **Fixed:** current access scopes filter feed activity before pagination; public contributions exclude private activity |
| S05 / Group 2 | Foreign-project folder references and nested resources | **Fixed at request boundaries:** project-scoped validation/binding and guarded legacy deletion; existing invalid references require explicit audit/repair |
| S06 / Group 3 | Wrong quota owner and free retained blobs | **Fixed in code:** `stored_blobs` billing ledger, conditional reservations, reference-aware collection and reconciliation; deployed legacy data must be adopted |
| S07 / Group 4 | OTP guessing, resend budget, plaintext codes and changed-email verification | **Fixed:** shared email-bound HMAC verifier service, expiry, guess caps, shared route limits, persisted delivery budget, consumption and email-change invalidation |
| S08 / Group 2 | Unauthorized private-project starring | **Fixed:** current access is checked before mutation; star changes are transactional |
| S09 / Group 3 | Traversing ZIP entry paths | **Fixed:** validate new names and independently validate legacy paths, ancestry, cycles and collisions at export |
| S10 / Group 3 | Unbounded operations and inconsistent filesystem outcomes | **Mitigated:** bounded input/work, named throttles, lifecycle locking and failure compensation; individual stalled calls still need host timeouts |
| S11 / Group 5 | Unlocked frontend advisory chain | **Fixed:** npm lockfile, reproducible setup and Tailwind 4 Vite integration; the legacy `braces` chain is absent and the current audit is clean |

### OTP contract now implemented

`EmailOtpService` stores one keyed SHA-256 verifier per user, bound to user ID,
the current email and code. User-row transactions coordinate verification,
issuance and email changes. The plaintext code is not stored in the token
table, flashed as old input, or included in the mail subject.

- A code expires after 15 minutes and stops accepting guesses after five
  incorrect attempts; successful verification consumes it.
- Verification permits ten submissions per account and thirty per IP per ten
  minutes.
- Both resend routes share three requests per account and ten per IP per ten
  minutes. A database-backed budget additionally permits three deliveries per
  account per ten minutes, including initial and failed sends. Replacing the
  token or clearing the cache does not reset that delivery counter.
- Registration permits five attempts per IP per ten minutes.
- Email changes clear verification and old tokens before issuing a new code.
  Already-issued signed links recheck the current email under lock and consume
  outstanding tokens. Legacy notice/resend routes share the OTP workflow.
- SMTP failures preserve the account/email change and show a retry warning.
  OTP mail error reporting omits potentially sensitive transport details.

The protected-token migration intentionally invalidates pending legacy codes;
unverified users must resend after deployment. Preserve `APP_KEY`, use a real
mail transport and persistent limiter cache, and validate client-IP behavior
behind the selected proxy. See [SELF_HOSTING.md](../SELF_HOSTING.md).

Regression evidence is in `ProjectPreviewSecurityTest`, `ProjectPrivacyTest`,
`ActivityVisibilityTest`, `ProjectBoundaryTest`, `StorageLifecycleSecurityTest`,
`ResourceBoundsSecurityTest`, and `Auth/OtpSecurityTest` under `tests/Feature`.
Audits and these tests are bounded evidence, not an exhaustive penetration test
or a full secret-history review.

## Functional correctness / data integrity: current status

The original IDs are retained for traceability. “Implemented” describes the
current code, not a claim that every acceptance condition has automated or
production-engine coverage.

| ID | Current status | Remaining qualification |
| --- | --- | --- |
| F01 | Versioning migration no longer drops the referenced `project_files` table | Fresh/populated MySQL/MariaDB validation remains a release gate; an already-partial failed schema needs its own recovery audit |
| F02 | Legacy conversion creates versions and updates real pointers without `_tmp_version_id` | Current `down()` does not reconstruct the original legacy file layout; use coordinated backups/forward fixes, and prove the populated upgrade on the target engine |
| F03 | Global slug uniqueness, suffix/fallback generation and creation retry implemented | Migration can rename colliding legacy slugs; verify existing links and concurrency on the deployment engine |
| F04 | Checkpoint pages load the actual version/snapshot relationships | Relevant checkpoint/access regression cases pass |
| F05 | Bounded text diff uses the installed API and escapes output | Office diffing is intentionally disabled, not implemented |
| F06 | Comments bind beneath their project and enforce access/deletion permissions | Scoped comment regression cases pass |
| F07 | Collaborator forms pass model IDs consistently | Numeric usernames and foreign-member management are covered |
| F08 | **Fixed in code:** blank saves retain route identities; missing identities are generated/backfilled | Backfill must be migrated; blank legacy names, collisions, reader pages, and generated-name length are covered; see R01 |
| F09 | Explore searches the current tag relationship | MySQL-family query behavior still needs release testing |
| F10 | Project/account lifecycle cleanup and recoverable logical-file deletion implemented | Folder/file history retention is deliberate; final physical collection is reference-aware and may require cleanup retry |
| F11 | Complete immutable checkpoint manifests and restore implemented | Rename/move/delete/restore regression passes; legacy checkpoints without manifests fail safely rather than inventing history |
| F12 | Reconciliation uses private version/blob paths and correct billing ownership | Reconciliation and destructive `--cleanup` require coordinated backup and deployment review |
| F13 | **Fixed in code:** populated admin pages use the application formatter and correct plan-edit route | List/detail/dashboard and authorized/unauthorized/invalid plan changes pass without `intl`; see R02 |
| F14 | Canonical `free`/`pro`/`custom` plans, corrected admin user-management labels, and legacy `student` conversion implemented | Stored billing-cycle labels are not recurring billing |
| F15 | Version sequence allocation and immutable per-version MIME metadata implemented | Broad concurrent production-engine verification remains outstanding |
| F16 | Historical links select the recorded version; documentation narrowed to current-project ZIP export | A historical checkpoint ZIP remains out of scope, not silently “completed” |
| F17 | Shared edit/access helpers align owner/editor/viewer operations and UI decisions | Continue expanding role-matrix coverage for remaining screens; editors may delete project content/history under the current contract |
| F18 | Upgrade routes, retryable mail, account-locked pending creation, and pending-only approve/reject transitions implemented | Repeated submissions reuse one account/plan request; real concurrent target-engine acceptance remains open; automatic subscriptions/expiry/renewal are not implemented; see R04 |
| F19 | Project/collaborator limits, operation bounds and transactional star/follow mutations implemented | Some legacy config keys remain unused, including cache TTLs and the separate global storage setting; avoid presenting them as enforced controls |
| F20 | Non-null root scope columns and uniqueness constraints implemented | Backfill/deduplication and collation behavior need populated target-engine checks |
| F21 | Account/invitation/upgrade changes survive mail failure with retry paths; specified delivery reports and stored upgrade errors are redacted | Real SMTP remains unverified; apply the legacy-error migration and protect old logs/backups; see R05 |
| F22 | Contributor deletion preserves foreign-project checkpoints with anonymized attribution | The committed history-preservation regression passes; billing ownership can intentionally block account deletion while foreign live blobs remain charged |

The two original registration/profile test failures are fixed. There are no
failures in the current 207-case suite. This remains bounded coverage, not proof
of every production-engine, concurrent, or browser behavior.

## Release repairs and remaining priorities

### R01 — P1: missing usernames — repaired in code

**Historical defect:** a blank profile update cleared the username and broke
project/profile URL rendering. `User::saving` now preserves a filled original
username, or generates one when the account has no identity. Valid changes to
an available username still work. Generated names and collision suffixes fit
the profile's 50-character validation limit.

`2026_10_11_104126_backfill_missing_usernames` repairs existing null, empty, and
space-only values in chunks using deterministic `user_{id}` names and collision
suffixes. It leaves filled identities alone and is repeatable; rollback retains
the assigned identities. `ProfileTest` and `ReleaseDataRepairTest` cover blank
updates, duplicate rejection, long-name collisions, legacy saves/backfill,
reruns, chunk boundaries, and project/profile/explore/feed page rendering.
The migration still needs staging/target-engine acceptance and deployment.

### R02 — P1: populated admin pages — repaired in code

**Historical defect:** populated admin pages called `Number::fileSize`, which
requires the locally absent `intl` extension. They now reuse `User`'s existing
storage formatter, so `intl` is optional for these pages. Verification also
found and repaired the user-list form's nonexistent `admin.users.update-plan`
route; the form now uses the actual `admin.users.updatePlan` route.

`AdminUserManagementTest` verifies populated list/detail/dashboard rendering
without `intl`, correct form links, all supported plan changes, invalid-plan
rejection, and denied non-admin access. User-management plan labels now show
Pro and Custom without conflating Custom with yearly billing. Hosting guides
explicitly name the required database driver and the removed `intl` dependency.

### R03 — P2: combined admin filters — repaired in code

**Historical defect:** the ungrouped name/email OR allowed a name match to bypass
the selected plan. `Admin/UserController::index` now groups the alternatives
before applying the plan filter. A rendered-page/view-data regression includes
both name and email matches on Pro, a matching Free account, and an unrelated
Pro account; only the two matching Pro accounts are returned.

### R04 — P2: pending upgrade creation — account lock implemented

**Historical risk:** pending-request lookup occurred outside the creation
transaction, allowing two requests to observe no pending row. Creation now
locks the account first inside the transaction, then performs a locking lookup
and creates only if no pending request exists for that account/plan. Laravel
retries concurrency failures up to three transaction attempts. Mail delivery
runs after the transaction commits.

`UpgradeRequestTest` verifies repeat submission reuse, preservation of the
original billing cycle, creation after a rejected request, input validation,
and unverified-account rejection. `MailDeliverySecurityTest` verifies failed
deliveries retry the same row. These are sequential SQLite cases: SQLite does
not provide MySQL/MariaDB row-lock behavior. Real simultaneous submissions on
the selected target engine remain part of R06; no such run is claimed here.

### R05 — P2: OTP/invitation/upgrade mail redaction — repaired in code

`MailDeliveryFailure` standardizes the specified OTP, collaborator-invitation,
and upgrade-instruction failure reports. It retains only the operation and
exception class, with no raw transport message or previous exception. Upgrade
requests store that safe message, while delivery status and retry behavior are
preserved. `2026_10_11_104127_redact_upgrade_mail_delivery_errors` replaces
previously stored non-null errors with a generic retry diagnostic; rollback
does not restore the original messages.

OTP regressions and `MailDeliverySecurityTest` exercise synthetic sensitive
transport messages, absence of raw reports/exception chains, failed buyer and
admin emails, saved collaborator/request state, and successful retries.
`ReleaseDataRepairTest` covers legacy stored-error redaction without changing
request status. Apply the migration and restrict historical logs/backups; it
cannot erase messages already copied there. Real SMTP remains an R06 gate.

### R06 — release gate: prove deployment and recovery

Before a public production release:

1. Test clean installation and populated legacy upgrade on the exact supported
   MySQL/MariaDB engine. Verify migrated slugs, names, version pointers, root
   uniqueness, foreign keys, snapshots, and retained charges. Do not infer
   engine portability from SQLite alone.
2. Back up database and private disk together, deploy required migrations, and
   reconcile legacy blobs. Do not run destructive cleanup without reviewing
   its targets and recovery backup. Pending legacy OTPs are invalidated.
3. Verify real SMTP delivery/failure/retry, persistent cache, HTTPS/session
   behavior and trusted-proxy client IPs. `.env.example` and `SELF_HOSTING.md`
   now use `MAIL_SCHEME`/`MAIL_URL` correctly, describe SMTPS/STARTTLS and DSN
   precedence, and include secure sessions and persistent single-host storage.
   Template correction/cache compilation does not demonstrate real delivery.
4. Demonstrate a coordinated backup restore and reconciliation, establish
   monitoring/alerts, and select a reviewed cleanup schedule. No production
   recovery drill or operational service configuration was verified here.
5. Add CI for the repository's PHP/JavaScript tests, build, and dependency audits,
   including the new admin/upgrade/identity/redaction regressions. Add a repeatable
   browser smoke workflow. CI and an automated browser suite are still absent.
6. Run concurrent quota/identity/upgrade and representative load checks. Set
   PHP/proxy body and time limits; cooperative application budgets do not
   interrupt a stalled individual filesystem, database or SMTP call.

## Architecture: what improved and what to modify next

Keep the Laravel monolith. `StorageLifecycle`, `CheckpointSnapshotService`,
`ArchivePath` and `EmailOtpService` now give the important invariants clearer
owners; microservices or a SPA are not prerequisites for correctness.

The current design intentionally supports one application host and private
local storage. Its OS file lock is not shared across independent replicas or
deployment directories. Add shared storage and coordinated locking before
horizontal scaling; do not treat a second web node as a configuration-only
change.

Maintain one permission contract across controllers and templates. The
project access/edit helpers and scoped routes are a useful foundation; expand
the role matrix as screens change. Extract further operations only where they
remove actual duplication or make an invariant independently testable.

Measure query counts and representative request costs before adding indexes or
services. Some shared-project/folder lists and view relationship lookups still
warrant profiling. Move large exports or durable mail delivery to bounded jobs
only when workers and retries can be operated reliably; current work remains
synchronous and deliberately bounded.

The frontend remains split: live layouts use Bootstrap/CDN assets while a
separate Tailwind 4/Vite/Alpine scaffold builds successfully. Choose one delivery
strategy when justified. The npm lockfile does not cover Bootstrap, PDF.js or
CodeMirror CDN URLs; retain an external-asset inventory and review CDN
availability, integrity and privacy separately.

Office preview through Google Docs Viewer cannot retrieve authenticated file
URLs. Keep download fallback and disabled Office diffing explicit; do not expose
private files to make the external preview work. Current-project ZIP export is
implemented; historical checkpoint ZIPs are not.

## Documentation and product-contract status

| Document | Current decision / remaining work |
| --- | --- |
| `README.md` | Updated requirements, release regression commands, and production configuration link; admin formatting no longer needs `intl` |
| `SELF_HOSTING.md` | Updated production SMTP/session/driver/storage template and irreversible repair guidance; retain the production-engine gate and record real deployment/recovery evidence when obtained |
| `docs/ARCHITECTURE.md` | Keep; reflects the ledger, snapshots, OTP service, access rules and single-host limits |
| `docs/API.md` | Keep as a web-route reference, not a REST specification; add the implemented `GET /upgrades/{plan}` and `POST /upgrades` user routes |
| `SECURITY.md` | Keep; reports current controls and deployment boundaries without claiming absolute security |
| `CHANGELOG.md`, `CONTRIBUTING.md` | Keep; record all five security groups and current verification/reproducible install commands |
| `ROADMAP.md`, `PRODUCTION_GUIDE.md` | Retired; do not recreate duplicate roadmap/deployment guidance |
| `LICENSE.md` | Keep, but the copyright holder must resolve its SCL terms versus Composer's MIT metadata |
| `CODE_OF_CONDUCT.md` | Useful for a public contributor community; optional for a closed solo project |
| `AGENTS.md`, `CLAUDE.md`, generated skills/MCP files | Tooling, not runtime product requirements; keep common policy aligned and local/generated material separate from shipped code |

Remaining product/documentation mismatches include the guest layout's “Open
Source” label alongside the restrictive SCL text and promised ZIP attribution
not inserted by the
current exporter. The application checks only an `SS-` license-key prefix;
it does not implement commercial entitlement validation. These are explicit
limitations, not validated licensing enforcement or subscription automation.

Extend existing guides rather than adding speculative documents. The useful
missing detail is a supported-engine release checklist, admin provisioning and
recovery procedure, and a deliberate privacy/retention explanation covering
public/private content, collaboration and external CDN/viewer requests. Third-
party notices must match redistributed assets. Legal/product choices belong to
the maintainer; this reassessment does not choose the source-license model.

## Historical engineering rating — 5 October 2026

The following table preserves the October 5 assessment and its then-current
reasoning for comparison with the original baseline. It is not the current
issue list: R01–R05 repairs and the 207-case results are recorded above. The
October 11 pass did not rerate architecture, interface, or production operations.

| Area | Score / 10 | Weight | October 5 reasoning (historical) |
| --- | --- | --- | --- |
| Security | 8 | 20% | Original groups remediated with targeted tests and clean locked audits; CDN, deployment and non-OTP error-redaction checks remain |
| Functional correctness / data integrity | 7.5 | 25% | Stronger snapshots, version metadata, ownership and failure handling; residual identity/admin defects and upgrade creation race prevent a higher score |
| Architecture / maintainability | 7.5 | 15% | Appropriate monolith with clearer invariant ownership; controller/query consistency, split frontend and single-host coordination remain constraints |
| Testing / verification | 7.5 | 15% | 181 passing PHP cases and five JavaScript cases now cover meaningful boundaries; uncovered admin/identity cases, no CI/browser automation, and no production-engine/concurrent acceptance |
| Deployment / operations | 6 | 10% | Repaired code and consolidated runbook, but target-engine upgrade, extension/mail/proxy setup and backup recovery still unproven |
| Documentation accuracy | 8 | 10% | Useful current architecture/security/operations guides; remaining extension/SMTP/routes/product-copy inconsistencies are explicitly identified |
| Interface / product clarity | 8 | 5% | Coherent concept and earlier responsive/browser evidence; current full browser acceptance and plan/preview clarity still need work |

Weighted result: **7.525/10**, rounded overall rating: **7.5/10**.
The original weighted result was 3.95/10, rounded to 4/10.

The next release gains are CI/browser automation and proving clean/populated
deployment, concurrent behavior, real SMTP, and coordinated restore on the
intended host. The code repairs above do not substitute for those checks.

## Baseline and history

At `c6bf19b`, the review recorded reproduced browser execution/private metadata
leaks, unsafe ZIP entries, broken core feature paths, migration failures and two
failures in the original 25-test suite. Those were historical findings, not the
results of today's 207-case run. Subsequent remediation is recorded in
[CHANGELOG.md](../CHANGELOG.md) and Git history; the previous detailed report is
available in the report file at commit `b9bc3a7`.

The October 11 update includes application/lockfile repairs, two data migrations,
new regressions, and corrected production documentation. It supersedes the
October 5 present-tense R01–R05 findings while preserving their IDs and historical
rating. No live deployment, existing-database migration, cleanup, or production
recovery/concurrency drill was performed.
