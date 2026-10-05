# SchoolShare repository review — current reassessment

Updated: 5 October 2026

Reviewed application commit: `b9bc3a7`

Original pre-remediation baseline: `c6bf19b`

Scope: current source, migrations, configuration, committed tests, dependencies,
documentation, and isolated diagnostic probes. This update did not deploy the
application or repeat the complete browser workflow on the current revision.

## Current assessment

**Overall rating: 7.5/10, up from the original 4/10.**

The repository is substantially stronger than the baseline. Security Groups
1–5 have been remediated in code, storage ownership and checkpoint snapshots
have explicit domain services, the build dependencies are reproducible, and
the committed regression suite passes. The previous XSS, privacy leaks,
archive traversal, broken checkpoint/diff/comment paths, plaintext OTPs, and
frontend advisory chain must not be presented as unchanged current findings.

However, “all F01–F22 fixed” was too broad. Reassessment found residual
username and admin-runtime failures, a plan-filter defect, and remaining
upgrade-request concurrency risk. Production-engine upgrades, real mail,
backup recovery, and concurrent production behavior are not yet verified.
This is an improved, tested repository, not a production-readiness certificate.

## Verification evidence

These checks were rerun for this reassessment unless explicitly marked as
earlier evidence:

| Check | Current result | Boundary of the evidence |
| --- | --- | --- |
| `php artisan test --compact` | **181 passed; 853 assertions** | SQLite in-memory database, fake storage/mail where used; not a production-engine or live-SMTP test |
| `node tests/JavaScript/PdfPreview.test.mjs` | **5 passed** | PDF integration logic with simulated browser/library objects, not a new real-browser run |
| `npm run build` | Passed; Vite 8.3.2 | Builds the Vite scaffold, which current application layouts do not load through `@vite` |
| `composer validate --strict --no-check-publish` | Passed | Manifest/lockfile validation, not deployment verification |
| `npm audit --json` | **0 vulnerabilities at every severity** | Registry audit of `package-lock.json`; does not audit hardcoded CDN assets |
| `composer audit --locked --format=json` | **No advisories or abandoned packages** | Current registry results for the locked PHP graph; not proof of application security |
| Fresh locked npm installation | Passed during Group 5 remediation | `npm ci --ignore-scripts` was verified then; this reassessment reran build/tests/audits without replacing dependencies |
| SQLite migration chain | Exercised successfully by the committed suite | Includes a populated legacy-OTP migration test; does not establish populated MySQL/MariaDB upgrade safety |
| Additional residual probes | **3 diagnostic cases confirmed the observed defects; 8 assertions** | Separate temporary probes asserted undesirable current behavior; they are not three new product acceptance tests |
| Real browser / responsive UI | Earlier evidence only | Original/remediation checks included responsive widths and PDF navigation; full current end-to-end browser acceptance was not rerun |

The three residual probes reproduced a blank-username project-page failure,
an admin search/plan-filter mismatch, and a populated admin user-list failure
without `intl`. They used isolated SQLite data; no existing application rows
or uploads were modified. Their results are explained below and do not rely
on temporary files remaining available.

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
| F08 | **Partially fixed:** new users receive generated usernames | Clearing a username still produces null and breaks profile-link rendering; existing null usernames are not repaired by a creation hook; see R01 |
| F09 | Explore searches the current tag relationship | MySQL-family query behavior still needs release testing |
| F10 | Project/account lifecycle cleanup and recoverable logical-file deletion implemented | Folder/file history retention is deliberate; final physical collection is reference-aware and may require cleanup retry |
| F11 | Complete immutable checkpoint manifests and restore implemented | Rename/move/delete/restore regression passes; legacy checkpoints without manifests fail safely rather than inventing history |
| F12 | Reconciliation uses private version/blob paths and correct billing ownership | Reconciliation and destructive `--cleanup` require coordinated backup and deployment review |
| F13 | **Partially fixed:** admin routes, method, relationship and detail view repaired | Populated admin user list still fails without `intl`; deployment requirements omit it; see R02 |
| F14 | Canonical `free`/`pro`/`custom` plans and legacy `student` conversion implemented | Admin plan labels still conflate custom and yearly billing; stored billing-cycle labels are not recurring billing |
| F15 | Version sequence allocation and immutable per-version MIME metadata implemented | Broad concurrent production-engine verification remains outstanding |
| F16 | Historical links select the recorded version; documentation narrowed to current-project ZIP export | A historical checkpoint ZIP remains out of scope, not silently “completed” |
| F17 | Shared edit/access helpers align owner/editor/viewer operations and UI decisions | Continue expanding role-matrix coverage for remaining screens; editors may delete project content/history under the current contract |
| F18 | Upgrade routes, retryable mail and locked pending-only approve/reject transitions implemented | Concurrent pending-request deduplication remains unsafe; automatic subscriptions/expiry/renewal are not implemented; see R04 |
| F19 | Project/collaborator limits, operation bounds and transactional star/follow mutations implemented | Some legacy config keys remain unused, including cache TTLs and the separate global storage setting; avoid presenting them as enforced controls |
| F20 | Non-null root scope columns and uniqueness constraints implemented | Backfill/deduplication and collation behavior need populated target-engine checks |
| F21 | Account/invitation/upgrade changes survive mail failure with retry paths; OTP reporting is sanitized | Real SMTP is unverified; invitation/upgrade exception handling still needs consistent redaction review; see R05 |
| F22 | Contributor deletion preserves foreign-project checkpoints with anonymized attribution | The committed history-preservation regression passes; billing ownership can intentionally block account deletion while foreign live blobs remain charged |

The two original registration/profile test failures are fixed. There are no
failures in the committed 181-case suite, but the additional probes demonstrate
why that must not be described as complete functional coverage.

## Remaining problems and release priorities

### R01 — P1: nullable usernames can still break rendered pages

**Reproduced:** submit a profile update with the unchanged email and an empty
`username`. The request succeeds, the username becomes null, and viewing that
user's existing project returns 500. `ProfileUpdateRequest` permits nullable
usernames; `User::booted()` generates them only during creation; project,
explore and feed templates require them for `profile.public` URLs.

Prevent clearing the route identity or consistently support a stable fallback;
backfill existing null identities. Add committed tests for blank updates and
legacy accounts, including the reader-facing pages, before closing F08.

### R02 — P1: admin user-list runtime requirement is incomplete

**Reproduced in the current PHP runtime:** the populated admin user list returns
500 without `intl`. Its view calls `Illuminate\Support\Number::fileSize`.
The current PHP module list lacks `intl`, and README/self-hosting requirements
do not explicitly include it. Passing authentication/project tests did not
exercise this screen.

Either require/install/document `intl` and verify the admin workflow, or use a
supported formatting fallback. Commit populated admin-list/detail/plan-change
tests, including the intended deployment-extension configuration.

### R03 — P2: admin search can bypass the selected plan filter

**Reproduced at the query/view-data boundary:** search plus `plan=pro` returns a
free-plan user whose name matches. `Admin/UserController::index` applies an
ungrouped name/email OR before the plan condition, yielding `name matches OR
(email matches AND plan matches)` rather than `(name OR email) AND plan`.
This is a functional admin-filter defect, not demonstrated private-data leakage
to non-admins. Group the search alternatives and add a combined-filter test.

### R04 — P2: pending upgrade requests are not concurrency-safe

**Source-inspected risk, not a reproduced concurrent run:**
`UpgradeController::store` checks for an existing pending request before its
creation transaction. The schema has no matching pending-request uniqueness
constraint, and creation locks no shared user/request row. Concurrent requests
can both observe no pending row and create duplicates. Locked approve/reject
transitions do not close this separate creation race.

Serialize creation around a stable account lock or enforce a suitable database
invariant, then test concurrent submissions on the chosen database engine.

### R05 — P2: mail error redaction is not yet a system-wide contract

**Source-inspected risk:** OTP failures are sanitized, but collaborator and
upgrade paths still report original transport exceptions. Upgrade delivery
also stores a truncated raw message in `mail_delivery_error`. Transport errors
can contain sensitive details depending on the driver; no actual credential
leak was demonstrated. Apply a consistent redaction policy and restricted log
access without losing retry/operational diagnostics.

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
   behavior and trusted-proxy client IPs. `config/mail.php` reads `MAIL_SCHEME`
   and `MAIL_URL`, not the legacy `MAIL_ENCRYPTION` setting shown in the guide.
4. Demonstrate a coordinated backup restore and reconciliation, establish
   monitoring/alerts, and select a reviewed cleanup schedule. No production
   recovery drill or operational service configuration was verified here.
5. Add CI for PHP tests, JavaScript tests, build and dependency audits. Add the
   missing admin/upgrade/identity regressions and a repeatable browser smoke
   workflow. There is currently no checked-in CI or automated browser suite.
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
| `README.md` | Keep as the entry point; setup and feature limits are much more accurate, but add the explicit admin `intl` requirement |
| `SELF_HOSTING.md` | Keep as the single operations guide; retain the production-engine gate, correct the SMTP variable example, add `intl`, and record actual deployment/recovery evidence when obtained |
| `docs/ARCHITECTURE.md` | Keep; reflects the ledger, snapshots, OTP service, access rules and single-host limits |
| `docs/API.md` | Keep as a web-route reference, not a REST specification; add the implemented `GET /upgrades/{plan}` and `POST /upgrades` user routes |
| `SECURITY.md` | Keep; reports current controls and deployment boundaries without claiming absolute security |
| `CHANGELOG.md`, `CONTRIBUTING.md` | Keep; record all five security groups and current verification/reproducible install commands |
| `ROADMAP.md`, `PRODUCTION_GUIDE.md` | Retired; do not recreate duplicate roadmap/deployment guidance |
| `LICENSE.md` | Keep, but the copyright holder must resolve its SCL terms versus Composer's MIT metadata |
| `CODE_OF_CONDUCT.md` | Useful for a public contributor community; optional for a closed solo project |
| `AGENTS.md`, `CLAUDE.md`, generated skills/MCP files | Tooling, not runtime product requirements; keep common policy aligned and local/generated material separate from shipped code |

Remaining product/documentation mismatches include the guest layout's “Open
Source” label alongside the restrictive SCL text, custom/yearly plan-label
confusion in the admin UI, and promised ZIP attribution not inserted by the
current exporter. The application checks only an `SS-` license-key prefix;
it does not implement commercial entitlement validation. These are explicit
limitations, not validated licensing enforcement or subscription automation.

Extend existing guides rather than adding speculative documents. The useful
missing detail is a supported-engine release checklist, admin provisioning and
recovery procedure, and a deliberate privacy/retention explanation covering
public/private content, collaboration and external CDN/viewer requests. Third-
party notices must match redistributed assets. Legal/product choices belong to
the maintainer; this reassessment does not choose the source-license model.

## Recalculated project rating

Weights are unchanged from the original review so the comparison is meaningful.
The score is an engineering judgment about the repository and available
verification evidence, not an industry certification or a percentage of lines
covered by tests.

| Area | Score / 10 | Weight | Current reasoning |
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

The strongest next gains are fixing R01–R04, making those failures part of the
committed suite/CI, and proving deployment plus restore on the intended host.
More features or a framework rewrite would not substitute for those checks.

## Baseline and history

At `c6bf19b`, the review recorded reproduced browser execution/private metadata
leaks, unsafe ZIP entries, broken core feature paths, migration failures and two
failures in the original 25-test suite. Those were historical findings, not the
results of today's 181-case run. Subsequent remediation is recorded in
[CHANGELOG.md](../CHANGELOG.md) and Git history; the previous detailed report is
available in the report file at commit `b9bc3a7`.

This reassessment replaces obsolete present-tense findings, intermediate test
counts, expired temporary-artifact references and the old rating with current
statuses. Only documentation is changed in the repository by this update;
the residual issues above were investigated, not fixed or deployed.
