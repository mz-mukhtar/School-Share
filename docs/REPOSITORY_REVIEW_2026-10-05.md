# SchoolShare repository review

Date: 5 October 2026  
Baseline commit: `c6bf19b`  
Scope: application code, routes, models, migrations, views, configuration, dependencies, tests, documentation, and an isolated browser workflow.

> Historical baseline: this review records evidence from commit `c6bf19b`.
> Its individual findings are intentionally preserved and are not a statement
> of the current implementation. Follow-up remediation is recorded in
> `CHANGELOG.md` and the current operational documents.

## Assessment

SchoolShare has a coherent product idea, an appropriate Laravel foundation, and a usable interface. Registration, OTP verification, uploads, text editing, invitations, and basic access checks work in the tested scenarios. However, this version is not ready to be considered finished or production-ready. There are reproduced security defects, a failing deployment migration, and failures in the features that define the product: checkpoint viewing, diffing, historical recovery, and collaboration management.

The highest priorities are to close the README and PDF viewer execution paths, prevent private metadata disclosure, repair project/folder isolation and storage accounting, and make the database migrations safe. Stabilize those contracts before adding new features.

The original review below describes the baseline commit. Follow-up fixes are tracked separately here so the original findings and evidence remain available.

## Remediation progress

Group 1 (browser code execution and unsafe rendering) was implemented and verified on 5 October 2026:

- **S01 fixed:** README Markdown now uses `html_input: escape` and `allow_unsafe_links: false`. Embedded HTML displays as text; unsafe Markdown link/image destinations are blocked. Headings, tables, code blocks, and safe links/images still render.
- **S02 fixed:** the PDF preview now loads pinned PDF.js 6.4.299 and its matching worker as modules, and passes `isEvalSupported: false`. Library, document, and page-render failures show a download fallback through text-only output.
- Regression coverage: `php artisan test --compact tests/Feature/ProjectPreviewSecurityTest.php` passes all 12 cases; `node tests/JavaScript/PdfPreview.test.mjs` passes all five cases, including runtime loading options, queued page navigation, and failure handling.
- Browser verification: the original harmless README marker no longer executed; a 14-page PDF rendered and next/previous navigation worked without recorded browser errors. Checks used the isolated synthetic database/storage from the review.
- Broader PHP suite: 35 of 37 cases pass. The two remaining failures are the previously documented registration redirect expectation and profile factory/storage-counter failure.

Group 2 (project privacy and authorization boundaries) was implemented and verified on 5 October 2026:

- **S03 fixed:** name/description search alternatives are grouped beneath the owner's project relationship. Other users' projects cannot enter My Projects results through the description branch or wildcard searches.
- **S04 fixed:** shared project/activity query scopes apply current public/owner/collaborator access before feed pagination. Project, checkpoint, and comment activities resolve their authoritative project; deleted, orphaned, and unknown subjects are excluded. Visibility changes and collaborator removal also affect existing activity, including activity authored by a removed member. Feed checkpoint links/names now come from the current project rather than stored metadata.
- Public profile contribution counts and recent activity now include public activity only, for guests and signed-in owners alike. The calendar is labeled “Public contributions.”
- **S05 fixed at application boundaries:** folder upload/move/parent validation requires membership in the current project. Nested checkpoint/file/folder/comment/collaborator routes use scoped binding. Breadcrumbs stop at foreign parents and repeated ancestors. Folder deletion queries remain project-scoped.
- Existing invalid folder links are protected rather than silently repaired: folder/project/account deletion returns 409 if a project's folders are referenced by another project's folders or files. This prevents database cascades from bypassing PHP traversal checks. These links must be audited and repaired explicitly before affected deletion operations proceed; no live-data cleanup or schema migration was performed.
- **S08 fixed:** starring and unstarring private projects require current access before changing stars, counters, or activity.
- Supporting fixes: checkpoint views load real file/version relationships, comment actions bind/check their project and apply reader/author/owner permissions, and collaborator forms pass model IDs consistently. This addresses baseline F04, F06, and F07 alongside the security changes; other role/UI inconsistencies in F17 remain.
- Regression coverage: 67 Group 2 cases pass across `tests/Feature/ProjectPrivacyTest.php`, `tests/Feature/ActivityVisibilityTest.php`, and `tests/Feature/ProjectBoundaryTest.php`. They cover privacy changes, roles, foreign IDs (including another project of the same owner), rejected-write side effects, legacy cascade protection, and authorized collaboration.
- Broader PHP suite: 102 of 104 cases pass. The only failures remain the baseline registration redirect expectation and profile factory/storage-counter failure. Group 1's 12 PHP cases and five JavaScript cases still pass; Laravel Pint and whitespace checks pass.

Group 3 (storage accounting, archive safety, and bounded file operations) was implemented on 5 October 2026; deployment prerequisites are listed below:

- **S06 fixed in application code:** `stored_blobs` records one billing owner and byte charge per private-disk object. Authorship and billing are separate. Restore references reuse the existing object without duplicating its charge. Checkpoint/file/folder/project/account deletion uses shared lifecycle handling; bytes are credited to the billing account only after the last reference is removed and disk deletion succeeds. Failed cleanup leaves a ledger record and charge for retry. Legacy adoption repairs undercharged retained objects; quota checks and cleanup never reduce counters below remaining recorded charges.
- Quota reservation is a conditional database update committed with the blob journal before filesystem writes. New objects have UUID storage names unrelated to submitted filenames. A non-expiring, non-blocking OS file lock serializes private-disk mutations, restore, fork, exports, and reconciliation. Contention returns 503 for retry instead of allowing overlapping lifecycle operations. Failed writes/copies and database failures compensate unreferenced objects; abrupt process termination leaves committed reservations for reconciliation rather than unrecorded physical copies.
- Account deletion returns 409 if the account still bills live objects in another owner's project. Those project owners must remove the contributed files before deletion proceeds; retained data is not silently made free or reassigned to another payer. Existing cross-project folder/cascade guards remain in place.
- **S09 fixed:** new folders, file renames, and uploaded names reject unsafe archive segments, including dot segments, separators, drive syntax, control bytes, and problematic Windows names. Export separately validates existing rows, folder ancestry, cycles, depth, and case-insensitive entry collisions. Unsafe or missing data cancels the export rather than producing a partial/traversing archive. Fork destinations no longer incorporate user-controlled filenames.
- **S10 mitigated at request boundaries:** configurable upload batches (20 files / 200 MiB), project entries (1,000), version history (10,000), folder depth (32), exports/forks (200 MiB), README/editor text (512 KiB), and diffs (64 KiB / 1,000 lines per input) bound synchronous work. Disk sizes are checked as well as stored metadata, and text reads are bounded streams. Markdown nesting and delimiter counts are limited. Upload/fork/restore/export loops have cooperative 30-second budgets; these do not interrupt an individual stalled filesystem/database call, so PHP/proxy timeouts and request-body limits still belong in deployment configuration.
- Upload and restore, editor/move, diff, fork, preview, download, and ZIP routes use configured account-based rate limits. ZIPs use checked archive operations and uncompressed entries to avoid unbounded compression work. Successful downloads delete their temporary files; failure paths clean up, and explicit cleanup removes expired generated exports after one hour.
- Synchronous Office-document parsing is disabled: Office files must be downloaded for comparison. Plain text/code diffing uses the installed `Differ`/renderer API correctly and escapes displayed content. This also addresses the text-diff portion of F05; Office diffing requires a separate bounded/isolated worker design before it can be re-enabled.
- `schoolshare:recalculate-storage` now imports legacy private-disk paths, counts shared paths once, includes historical versions and discoverable orphan files, and reconciles billing users from the ledger. The default does **not** delete orphan files. `--cleanup` explicitly removes unreferenced ledger-backed objects and expired generated exports; failed blob cleanup remains charged and causes a nonzero command result. Unknown billing owners stop reconciliation for manual audit rather than guessing or resetting usage to zero.
- Regression coverage: 58 new cases in `tests/Feature/StorageLifecycleSecurityTest.php` and `tests/Feature/ResourceBoundsSecurityTest.php` cover restore/deletion billing, collaborator and cascade cleanup, rejected writes, rollback compensation, partial fork failure, missing sources, zero-byte edits/history caps, lock contention, legacy adoption/reconciliation, archive contents/traversal/cycles/collisions/budgets, bounded rendering/diffs, and rate limiting. All 137 Group 1–3 PHP regression cases pass. The full suite passes 160 of 162 cases (612 assertions); the two baseline failures remain registration's redirect expectation and the profile factory's missing storage counter. All five JavaScript preview cases, Pint, and whitespace checks pass.

### Group 3 deployment and recovery

No migration, reconciliation, or cleanup was run against the configured application database or existing uploads during this batch. Verification used isolated SQLite databases and fake storage. The new ledger migration and legacy import require deployment; the original MariaDB fresh-install/legacy-versioning migration defects (F01/F02) remain separate outstanding work.

Back up the database and private disk together. Keep the application in maintenance mode while applying the new migration and importing existing storage:

```sh
php artisan down
php artisan migrate --force
php artisan schoolshare:recalculate-storage
php artisan up
```

Do not reopen the application if migration/reconciliation fails. Audit unidentified owners and invalid legacy references first. New uploads/edits/forks return 409 while file-version paths remain unregistered. Existing invalid archive names must be renamed before export. The ledger is charge metadata: a rollback that drops it loses billing/recovery information, so prefer a forward fix and restore coordinated backups if rollback is necessary.

For recovery after failed or interrupted operations, inspect retained charges/orphans, then explicitly run `php artisan schoolshare:recalculate-storage --cleanup`. This deletes unreferenced physical objects, so back up anything that may need recovery first. It does not delete live historical/restore references. Schedule the reviewed cleanup operation if automatic retry/expired-export maintenance is desired; no production scheduler was changed here. Pre-existing ZIPs under the former `storage/app/temp` location are outside the new generated-export directory and require a separate audit.

The lock assumes one application host and its current private local disk, with a writable `storage/framework/cache` directory. Multiple replicas, independent deployment directories, object storage, preemptive time limits, and larger queued exports/parsing require a coordinated distributed lifecycle design. Avatars remain outside project-blob billing. No concurrency/load test against a production database or multi-node deployment was performed.

The preceding Group 1–3 entries record their verification state at the time of
each batch, including the then-outstanding baseline failures. Subsequent
functional/data-integrity remediation repaired the F01–F22 findings, including
the migration conversion path and the two baseline test failures. Current
deployment limitations are in `SELF_HOSTING.md`; the original assessment and
rating below have not been recalculated.

### Groups 4 and 5 remediation (5 October 2026)

Group 4 (OTP verification, delivery abuse, and email identity) is fixed in the
application code:

- **S07 fixed:** `EmailOtpService` stores an HMAC-SHA-256 verifier bound to
  user ID, current email, and code. Codes expire after 15 minutes, stop accepting
  guesses after five failures, and are consumed on success. The unique user
  token and locked user-row transactions serialize issuance, verification, and
  email changes. Tokens are not plaintext, their verifier is hidden from model
  serialization, OTP input is not flashed, and mail subjects omit the code.
- Named verification limits permit ten submissions per account and thirty per
  IP per ten minutes. Both resend routes share three requests per account and
  ten per IP per ten minutes. A persisted delivery counter additionally allows
  three sends per account per ten minutes, counting initial and failed sends;
  token replacement and cache clearing do not reset it. Registration has a
  five-attempt IP limit per ten minutes.
- Changing email clears verification and the prior token before sending a
  recipient-bound replacement. Old signed links fail for the changed address.
  Valid already-issued signed links check the email under lock and consume
  outstanding tokens. Legacy notice/resend routes now use the same OTP flow.
- Registration and email changes survive SMTP failure and display an actionable
  resend warning. Delivery metadata is committed before sending, and an older
  delivery cannot overwrite a replacement token's status. Error reporting
  records the exception class, not potentially sensitive transport contents.
- The new protected-token migration invalidates legacy plaintext codes without
  deleting accounts or clearing verified status. It has **not** been run on the
  configured application database. Deploy in maintenance mode; unverified
  users must resend. Keep `APP_KEY` stable and use persistent limiter cache and
  a real mail transport. Actual SMTP delivery and production concurrency remain
  deployment checks.
- Regression coverage: 16 OTP security cases cover account/email binding,
  expiry, guess/route limits, resend rotation and persistent budgets, legacy
  route compatibility, token consumption, identity changes, mail failures,
  session input handling, and populated legacy-token migration/recovery.

Group 5 (frontend dependency reproducibility and advisory chain) is fixed:

- **S11 fixed:** added `package-lock.json` and changed the setup workflow to
  `npm ci --ignore-scripts`. Migrated the unused mixed Tailwind 3/Tailwind 4
  build configuration to Tailwind 4.3.3 with its Vite plugin, retaining the
  Figtree/forms configuration and bounding content scanning to declared
  templates. Removed the obsolete PostCSS/Tailwind 3 integration. The legacy
  `braces`/`micromatch`/`fast-glob`/`chokidar` chain is absent from the lockfile.
  Updating within Tailwind 3 alone still retained the
  [unpatched braces advisory](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm),
  which is why the build integration was migrated.
- The build environment requires Node.js 22.12+ and npm 10+. Current pages
  still use the existing Bootstrap/CDN layout; this is not a visual redesign
  or a claim that npm audits cover separately loaded CDN assets.
- A fresh offline cached `npm ci --ignore-scripts`, `npm run build`, and
  `npm test` passed. Direct execution of the PDF-preview test file passed all
  five JavaScript cases. `npm audit` reported zero vulnerabilities at all
  severities, and `composer audit --locked` reported no advisories or abandoned
  packages at remediation time. Rerun audits before release as advisories change.

Final verification for this batch: **181 PHP tests passed, 853 assertions**;
Laravel Pint and `git diff --check` passed. Test writes used isolated SQLite,
fake mail, and fake storage, not existing application data. The production
database upgrade gate, real SMTP/proxy/cache verification, single-host storage
constraint, and source-license/metadata conflict remain documented limitations.

### Documentation remediation (5 October 2026)

The current `README.md`, `SELF_HOSTING.md`, `SECURITY.md`,
`CONTRIBUTING.md`, `CHANGELOG.md`, architecture guide, web-route reference,
and public Terms wording were corrected against the current routes, models,
configuration, and tested remediation work. The duplicate production guide and
the obsolete roadmap were retired. They no longer describe Laravel 11, PHP 8.1,
unsupported deployment readiness, invented API/service layers, OSI open-source
status, or historical ZIP/recovery behavior.

The source-license/Composer-metadata conflict remains a copyright-holder
decision, rather than a documentation decision: `composer.json` declares MIT
while `LICENSE.md` defines the restrictive SchoolShare Community License. The
third-party notice table is now factual, but neither document determines which
source-license model should govern distribution.

## What was checked and what the results mean

| Check | Result |
| --- | --- |
| Existing PHPUnit suite | 25 tests: 23 passed, 2 failed; 61 assertions |
| Additional temporary audit probes | 49 tests: 19 passed, 29 assertion failures, 1 error; 84 assertions |
| PHP syntax | 111 repository PHP files checked; no syntax errors |
| Composer manifest validation | `composer validate --strict` passed |
| PHP dependency advisory check | `composer audit --locked --format=json`: no advisories or abandoned packages reported at review time |
| Frontend build | Passed in a temporary copy after installing the declared dependencies; Vite resolved to 8.3.2 |
| Frontend advisory check | Five high-severity package entries in the temporary dependency resolution, stemming from the `braces` dependency chain |
| Fresh SQLite installation | All migrations passed |
| Fresh MariaDB installation | Failed in the file-version migration with foreign-key error 1451 |
| Upgrade with an existing legacy file | Failed in an isolated SQLite database: nonexistent `_tmp_version_id` column |
| Browser | Existing Chromium, isolated SQLite database and storage, mail sent to an in-memory transport |
| Responsive dashboard | No horizontal overflow at 360, 768, or 1440 pixels in the tested state |
| Browser security check | A harmless README `onerror` marker executed; an exported ZIP contained `../notes.txt` |
| Cross-site request forgery check | A POST with the test session, cross-site headers, and no token returned 419 |

The additional tests assert desired behavior, so their failures identify audit findings; they are not new failures in the committed 25-test suite. They deliberately target suspected weak points and are not a statistical sample of every feature. One failed probe concerns a stale factory model rather than a failure in the real registration flow; that distinction is explained below.

The browser exercised public pages, registration, OTP entry, profile editing, project creation, upload, file preview, editor opening and saving, checkpoint navigation, README rendering, and ZIP download. Editor saving worked, but both versions were labeled “Version 1.” Checkpoint navigation returned 500. No JavaScript exceptions were recorded in that normal workflow.

Verification used synthetic accounts and files. The repository's configured database and existing uploads were not used for these writes. The live hosted site, real SMTP delivery, cPanel configuration, Cloudflare settings, large-file load, disaster recovery, and concurrent production traffic were not verified. PDF exploit execution was not attempted; the included version was compared with its maintainer's advisory.

Installed runtime: PHP 8.5.11, Laravel 13.34.0, PHPUnit 12.5.37, `jfcherng/php-diff` 7.0.1, and PHPWord 1.1.0. Although the application manifest allows PHP 8.3, the existing locked Symfony dependencies require PHP 8.4.1 or newer. That requirement was already present before this review's Boost installation.

## Security findings

Severity describes impact and practical prerequisites. “Reproduced” means observed with isolated fixtures or the browser. “Code risk” means a concrete missing control was found but its production impact was not load-tested.

### S01 — High: stored cross-site scripting through project README

**Evidence:** `app/Http/Controllers/ProjectController.php:129` converts uploaded Markdown with default options; `resources/views/projects/show.blade.php:233` renders the result as raw HTML.

An uploaded `README.md` containing this harmless marker was rendered, and Chromium reported `window.auditMarker === 1`:

```html
<img src="missing-audit-image" onerror="window.auditMarker=1">
```

An unrelated verified reader's response also contained the executable attribute. A malicious project owner or editor can therefore run script in the application origin when another permitted reader opens the project. This can allow actions using that reader's session, even with HttpOnly session cookies.

A literal `<script>` probe was escaped by the GitHub-flavored Markdown extension. That does not sanitize event-handler attributes, so blocking script tags alone is insufficient.

**Change:** explicitly strip or escape raw HTML, disallow unsafe link schemes, and impose Markdown size/nesting limits. If rich HTML is necessary, sanitize with an allowlist before raw output. Consider a compatible Content Security Policy as an additional control. CommonMark documents these security settings in its [security guidance](https://commonmark.thephpleague.com/2.x/security/).

**Acceptance:** a reader can view normal Markdown, but image event handlers, unsafe URLs, and other active HTML cannot execute.

### S02 — High: vulnerable PDF.js version served outside dependency manifests

**Evidence:** `resources/views/projects/files/show.blade.php:94` and `:96` load PDF.js 3.11.174 and its worker from a CDN. The call at `:154` does not disable evaluation.

Mozilla's [CVE-2024-4367 advisory](https://github.com/mozilla/pdf.js/security/advisories/GHSA-wgrm-67xf-hhpq) identifies versions through 4.1.392 as affected and 4.2.67 as the first patched version. It describes attacker-controlled JavaScript execution in the hosting origin when a malicious PDF is opened with the default evaluation setting.

**Change:** replace both library and worker with a reviewed, maintained release and adapt the integration to its API. Setting `isEvalSupported: false` is the advisory's interim workaround. Track this asset in the dependency inventory: Composer and npm audits do not inspect hardcoded CDN URLs.

**Acceptance:** library and worker versions match, the version is outside the affected range, and PDF preview still works. The review verified exposure by version and configuration, not by executing a malicious PDF.

### S03 — High: private projects leak through “My Projects” search

**Evidence:** `app/Http/Controllers/ProjectController.php:15–20` adds an ungrouped `orWhere('description', ...)` to the current user's project relationship.

With an unrelated user's private project description containing a known marker, searching `/projects?q=...` displayed that project's name in the requesting user's list. The description branch escapes the intended owner constraint. This is an authorization/query-logic defect, not SQL injection.

**Change:** group every search alternative inside a nested condition under the owner relationship. Preserve that grouping whenever visibility, ownership, or team restrictions precede an OR expression.

**Acceptance:** search returns only owned projects in this list, including when another private project matches the description branch or the search contains wildcard characters.

### S04 — High: private checkpoint metadata leaks to followers

**Evidence:** `app/Http/Controllers/FeedController.php:25` selects activity by followed user IDs without checking the reader's project access. `resources/views/feed/index.blade.php` renders checkpoint titles and project metadata.

A follower without access to a private project could see its checkpoint title and project name. Private file download remained forbidden, but metadata itself can contain sensitive information.

**Change:** give project-related activity an explicit project association and apply current visibility/access checks when reading a feed. Check permission changes too: making a project private or removing a collaborator must affect previously recorded activities. Public contribution counts need a deliberate privacy policy; the public profile currently aggregates all activity, but its unused `recentActivities` variable was not evidence of public title disclosure.

**Acceptance:** unrelated followers never see private titles or project names; authorized collaborators see only what the chosen policy permits.

### S05 — High: folder references can cross project boundaries

**Evidence:** global `exists:project_folders,id` checks at `CheckpointController.php:58`, `FileController.php:181`, and `FolderController.php:20` do not constrain the referenced folder to the route's project. Folder recursion and breadcrumbs follow those references.

The upload, file-move, and folder-create endpoints accepted a folder ID owned by another account. A deletion probe also showed that deleting a folder removes file records from another project if such records reference that folder. Parent references can expose another project's folder names through breadcrumbs and make directories unreachable in their own project tree.

This does not establish arbitrary access to another user's file contents. It establishes broken tenant relationships and deletion traversal across projects; the damage depends on how those invalid relationships are constructed.

**Change:** scope folder existence to the project, scope child/file traversal defensively, and enforce the same-project invariant in the data model where feasible. Audit existing data for foreign project references before cleanup. Add consistent nested binding for project/checkpoint/comment resources.

**Acceptance:** foreign parent/folder IDs are rejected before writes, and deletion never traverses records belonging to another project.

### S06 — High: quota can be reclaimed while stored content remains live

**Evidence:** `CheckpointController.php:169–195` retains a physical file when another version references it, but still subtracts the deleted checkpoint's original size from the current actor's storage counter. Restore creates additional references with zero checkpoint size. `FileController.php:62–86` also reclaims quota from the actor rather than the account charged for each stored object.

Two reproduced examples:

1. A 5-byte file had a second version referencing the same path, as restore does. Deleting its original checkpoint retained the physical file but reduced accounted storage from 5 bytes to zero.
2. An editor deleted a file originally uploaded by its owner. The editor's unrelated storage counter fell from 20 to 15 bytes, while the owner's charge remained.

Repeated use can make quota counters disagree with live disk usage. Conversely, some deletion paths leave users charged for unreachable or orphaned data.

**Change:** define one charge owner for each stored blob, distinguish authorship from billing ownership, and reclaim bytes only when the last live reference is removed. Make quota reservation/checking atomic. A reconciliation operation must use the same ownership and reference rules.

**Acceptance:** restore and deletion cannot create free retained bytes or credit an unrelated account; counters reconcile to the documented physical/logical accounting model.

### S07 — Medium: OTP attempts, resend limits, and email identity are incomplete

**Evidence:** `routes/auth.php` does not throttle OTP verification. Twenty wrong guesses were accepted without a 429 response. `OtpVerificationController.php:77–87` counts recent token rows and then deletes them, so that history cannot enforce its intended three-resend limit. Four immediate resends produced four emails. A separate route throttle of six per minute does exist.

OTP values are stored in plaintext and associated with user ID rather than the particular email address being verified. An isolated state-change probe showed that an old pending code verified a newly assigned email. That probe changed the email in its fixture; a realistic related path is legacy signed-link verification, which does not clear the outstanding OTP, followed by profile email change, which also does not clear it.

**Change:** use an attempt counter/limiter keyed to account and source, a resend limiter independent of token rows, and tokens bound to the intended email. Invalidate tokens on identity change and on either successful verification path. Store a keyed digest or equivalent protected verifier rather than plaintext. Send a fresh verification message after changing email. Prefer one coherent verification workflow.

**Acceptance:** wrong guesses are capped, the documented resend window is enforced, expired/old-email codes fail, and email-change recovery works. Valid and expired-code behavior passed the corresponding probes.

### S08 — Medium: private project starring lacks authorization

**Evidence:** `ProjectController.php:219` mutates star records without `hasAccess()` or an equivalent policy. An unrelated account could star a private project and receive a 200 response and its star count.

**Change:** authorize access before star mutation; avoid revealing existence through mutation responses. Serialize or make toggling idempotent so concurrent requests cannot desynchronize the pivot and cached count.

**Acceptance:** an unrelated user cannot star or unstar a private project, and the count matches the pivot records.

### S09 — Medium: exported ZIPs contain traversal paths

**Evidence:** `FolderController.php` permits `.` and `..` with its filename regex. `ZipController.php` concatenates folder names directly into ZIP entry paths.

The browser created a folder named `..`, uploaded a synthetic text file into it, and downloaded a ZIP. Inspection, without extraction, found entries `../` and `../notes.txt`.

This is unsafe archive construction. Actual writes outside an extraction directory depend on the recipient's extraction tool; no server filesystem traversal was demonstrated.

**Change:** reject dot segments and path separators, normalize and validate archive entry paths, and reject absolute/traversing paths at export time even if old database rows contain them.

**Acceptance:** every exported path stays within the archive's logical project root.

### S10 — Medium: expensive operations and file lifecycle lack bounds

**Code risks:** README rendering reads the whole stored file without the text viewer's 512 KB cap; diff extraction reads complete files and parses Office documents synchronously; ZIP exports run synchronously without the configured download limiter. The editor endpoint lacks a specific content-size limit or throttle. Upload quota checking happens before the database transaction, without a reservation or locked recheck.

Local filesystem write/copy/delete failures can return `false` because the disk has `throw => false`, while callers generally proceed. Database transactions do not roll back filesystem writes. Forking can leave physical copies after quota-triggered rollback, or create version rows for missing source objects. These are concrete reliability/resource risks, not reproduced high-volume denial-of-service attacks.

**Change:** add operation size/time limits and targeted rate limits; reserve quota atomically; check storage outcomes; stage writes and compensate on failure; move large exports/parsing to bounded jobs when the hosting environment can support workers. Explicitly design for untrusted Office/archive input.

### S11 — Medium maintenance priority: frontend dependency advisory chain

No JavaScript lockfile is committed. A fresh resolution of `package.json` reported high-severity entries for `braces`, `chokidar`, `micromatch`, `fast-glob`, and `tailwindcss`, all linked to the same underlying recursive-pattern issue. These are five affected package entries, not five independently demonstrated production exploits.

The [upstream braces report](https://github.com/micromatch/braces/issues/70) describes stack exhaustion from deeply nested patterns. In this repository the affected chain is build tooling; the review found no endpoint passing uploaded content to that Node process. Prioritize dependency maintenance, lock the selected graph, and assess build/CI input exposure. Do not run an automatic breaking upgrade without checking the frontend strategy.

### Controls that worked in the tested scenarios

Guest access to protected pages redirected to login, unverified accounts were redirected to OTP, unrelated users could not download private files, project/file mismatches returned 404, foreign file-version IDs were rejected, viewer collaborators could not edit, and non-admin accounts could not access the admin dashboard. Notification lookup was restricted to the current user. Oversized upload validation and expired OTP rejection passed.

Passwords use the framework hashing facilities. User uploads are stored on the private local disk rather than in an executable web-root directory; avatars have separate image validation. No direct server-side code execution or SQL-injection exploit was demonstrated. A lightweight tracked-file scan found no obvious private-key/API-token patterns, and `.env` is not tracked; this was not an exhaustive secret-history audit.

Cross-site POST rejection was tested outside PHPUnit, which normally bypasses request-forgery middleware. A same-origin browser POST without a token reached validation, which is expected under Laravel 13's origin-aware protection, not proof of a CSRF bypass. See the framework's [request-forgery documentation](https://laravel.com/framework/docs/13.x/csrf).

## Functional and data-integrity findings

| ID / priority | Finding and evidence | Recommended change / acceptance |
| --- | --- | --- |
| F01 / P0 | Fresh MariaDB 11.4 migration fails when `2026_10_04_170427_alter_project_files_for_versioning_table.php:82` drops `project_files`, already referenced by `file_versions`. Error 1451 was reproduced using Laravel's actual migration command. | Design a migration sequence that preserves/rebuilds constraints safely; prove clean install and upgrade on the supported MySQL-family engine. A failed MySQL DDL migration can leave partial schema state, so provide a recovery procedure too. |
| F02 / P0 | The same migration writes `_tmp_version_id` at line 28 without creating that column. A fixture with one legacy file fails before conversion completes. Its rollback also recreates old columns without restoring the old file data. | Define and test a populated-data conversion, not just empty tables. Preserve file pointers and document whether rollback is recoverable or requires a backup. |
| F03 / P1 | Slugs are unique per owner, but routes bind globally by slug. Two users creating “Homework” get the same address; the second owner's page resolved to the first private project and returned 403. | Use owner+slug routing, globally unique slugs, or stable IDs. Enforce the chosen route identity in the database and handle concurrent creation. |
| F04 / P1 | Checkpoint viewing always fails at `CheckpointController.php:150`: it loads a `files` relationship that the model does not define. Browser and HTTP probe returned 500. | Load the actual version/file relationships and verify the detail page with and without files/comments. |
| F05 / P1 | Diff fails at `FileController.php:128`: `Jfcherng\Diff\Diff` is absent in installed version 7.0.1. The next call also passes an object to `renderArray(array)`. | Use the installed package's `Differ` and matching rendering API; test ordinary text and Office extraction failures and cap inputs. |
| F06 / P1 | Comment POST and DELETE fail because their methods omit the parent route argument. For example, `CommentController::store(Request, Checkpoint)` receives the project slug where it expects a checkpoint. Both paths were reproduced. | Include/bind the project, verify child membership, then apply `hasAccess()` and author/owner deletion permissions. Fix the view's nonexistent `comments.destroy` route name to the actual nested route. |
| F07 / P1 | Collaborator role/removal forms pass username, but `User` binds by ID. The generated UI requests returned 404. A numeric username could resolve to a different user ID. | Pass the model/ID consistently or explicitly bind that route by username. Assert that the affected collaborator is the one the UI displays. |
| F08 / P1 | Newly registered accounts have no username, while project/explore/feed templates require it to build profile URLs. A project page for a persisted account without a username returned 500. The sidebar's ID fallback also does not match username-only profile lookup. | Assign or require a unique username during onboarding, or support a stable ID-based fallback throughout. Test first-use pages before the user has edited settings. |
| F09 / P1 | Explore still searches `subject_tag` after a migration drops it (`ExploreController.php:25`). SQLite allowed the probe because quoted missing-column handling can mask this; equivalent MariaDB SQL returned error 1054. | Search the current tag relationship. Test this query on the supported production database; SQLite passing is not evidence of MySQL compatibility. |
| F10 / P1 | Project/folder/account deletion cascades database records without coordinated blob cleanup. The project-delete probe left its file on disk and its quota charged. Folder deletion also deletes historical records. | Route deletion through a shared lifecycle operation, reclaim actual charge owners, and clean avatars/blobs according to a documented retention policy. Preserve recoverable history if checkpoints promise recovery. |
| F11 / P1 | Restore only iterates surviving file records and versions. Once file deletion removes that history, an earlier checkpoint cannot bring it back. Current names/folders are not immutable checkpoint state. | Store complete snapshot membership and path metadata, including deletion/tombstone behavior. Verify upload → rename/move/delete → restore reproduces the earlier tree. |
| F12 / P1 | `schoolshare:recalculate-storage` checks the public disk and nonexistent `$file->path`, while content is in private `FileVersion.storage_path`. It threw a TypeError with real file fixtures. Accounts encountered before an error can already have been reset. | Do not use this command as a repair tool yet. Reimplement reconciliation using the chosen blob/reference ownership model; add a dry-run and mismatch report. |
| F13 / P1 | Admin user list fails on missing `intl`; enabling that installed extension reveals nonexistent `admin.users.update-plan`. The form also sends PUT but the route expects PATCH. User detail loads nonexistent `upgradeRequests`; its view is absent too. | Document `intl`, fix route name/method and relationship/view together, then exercise a complete admin read/change flow. |
| F14 / P1 | Default plan is `student`, config defines `free_plan`, admin accepts `free/pro/custom`, and custom has no limits configuration. Students currently fall back to 3 GB/15 projects instead of `free_plan`'s 1 GB/15. `.env` storage limits do not drive these helpers. | Choose canonical plan keys and one limit source. Migrate old values safely; test each plan and unknown-plan behavior. |
| F15 / P2 | `version_number` is never incremented by upload/edit/restore, and per-version MIME is neither a column nor fillable. Browser editing showed two “Version 1” labels. Historical viewer selection uses the current file's MIME. | Persist immutable version metadata, allocate numbers atomically, and render historical content according to that version. |
| F16 / P2 | Old checkpoint preview/download links omit `version_id`, so they open the latest file. No historical checkpoint ZIP route exists, despite README/API claims. | Pass the selected version on history links; implement historical export or narrow the advertised feature. |
| F17 / P2 | Editor permissions vary between backend and UI: checkpoint operations allow editors, folder creation only allows owner, several action buttons only appear for owner, and private-project comment code excludes collaborators even after routing is fixed. | Define and enforce a role matrix, and render controls from those same abilities. |
| F18 / P2 | `UpgradeController` is not registered in the routes; its checkout references missing `upgrade.store`. Approval/rejection lacks a pending-state guard/atomic transition; billing cycle is recorded but expiry/renewal is absent. | Decide whether paid plans are manual contact-only or an in-app request workflow. For the latter, wire routes and make state transitions idempotent and auditable. Do not present stored billing-cycle labels as implemented recurring billing. |
| F19 / P2 | Collaborator maximums and several configured size/rate/cache settings are not enforced or used. Upload validation hardcodes 100 MB; creation/count checks and star/follow toggles are vulnerable to concurrent races. | Remove misleading knobs or connect them to real enforcement. Add database constraints, locks or atomic updates where the invariant needs them. |
| F20 / P2 | `unique_file_per_folder` and folder uniqueness include nullable folder/parent IDs, so root rows can still duplicate under database NULL semantics. Empty/non-Latin slug generation also needs a stable fallback. | Represent root identity in a way that supports uniqueness on the chosen engine; test root duplicates, concurrent uploads, and names that slug to empty strings. |
| F21 / P2 | Synchronous invitation/OTP mail happens after records/access changes. SMTP failure can leave a committed account/invitation but a failed response; retrying an already-attached collaborator does not resend. | Make delivery retryable after commit, give users accurate delivery state, and provide safe resend behavior. Current mail tests use fakes; real SMTP remains unverified. |
| F22 / P2 | Deleting an editor account cascades checkpoints authored by that user, including checkpoints in someone else's project. File versions can retain null checkpoint IDs while views require checkpoint links. | Preserve project history independently of author account lifetime, anonymize attribution when appropriate, and handle deleted/null authors/checkpoints in presentation. This dependency is a source-inspection finding. |

The original registration test expects `/dashboard`, but the implemented OTP flow correctly redirects to `/otp/verify`; update that stale expectation. The original profile test fails because its factory-created in-memory model does not hydrate database defaults such as `storage_used_bytes`. Mirror essential defaults or refresh the fixture. Real browser registration/verification successfully reached the dashboard, so the factory failure should not be described as proof that real registration is universally broken.

## Architecture recommendations

### Keep the Laravel monolith

Blade, Laravel sessions, relational metadata, and a private file disk are a reasonable starting architecture for this product. A SPA, microservices, or Kubernetes would not resolve the defects above. The main issue is inconsistent domain rules across controllers and views.

### Separate four concepts that are currently mixed together

| Concept | Meaning and required invariant |
| --- | --- |
| Stored blob | Immutable bytes with size, storage key, integrity hash and charge owner; deleted only when no retained reference needs them |
| File identity | A logical file within a project, independent of the path it had at a past checkpoint |
| File version | Immutable content/type/sequence metadata with attribution; must reference an existing blob |
| Checkpoint snapshot | The project's exact file membership, names and folder paths at that point; restoration reproduces the tree, including prior deletions/moves |

The current checkpoint upload records changed files, while restore reconstructs a partial historical state from surviving files. That delta-based implementation can work only if changes and deletions remain immutable and reconstructable. Current destructive deletion breaks that contract. Choose either explicit snapshot manifests or a fully retained change log; document and test the choice.

Introduce a small set of shared domain operations for saving a version, restoring a checkpoint, reserving/releasing storage, deleting a project, and building an export. These are natural reuse boundaries because uploads, browser edits, forks, restores, and deletions currently implement overlapping rules differently. Controllers should handle the request, invoke authorization and the operation, and return the result.

### Make authorization a single contract

Use policies or equivalent shared abilities for project access, file edits, checkpoint restore/delete, comments, collaborator management and starring. Resolve nested resources under their parent project. Test the owner/editor/viewer/unrelated/unverified/guest/admin matrix, then derive UI visibility from those abilities. Explicitly decide whether editors may permanently delete shared history; the helper named `authorizeOwner` currently permits editors.

Activity needs the same project boundary. A polymorphic subject alone makes visibility joins, deleted subjects and historical privacy changes difficult. Keeping `project_id` alongside attribution would simplify permission filtering and query indexing.

### Make data and file operations recoverable

Reserve quota inside a transaction using a locked recheck or atomic conditional update. Stage new blobs, verify writes, commit metadata, and clean up staged objects on failure. Delete physical blobs after commit through an idempotent operation that checks live references again. Keep a reconciliation report for orphaned objects, dangling pointers and counter mismatches.

Back up database metadata and private files as one recoverable system. A database-only backup cannot restore student content; a disk-only backup cannot recover permissions/version relationships. Restore drills should prove both. Retention must explicitly distinguish user-visible deletion, recoverable history and final physical deletion.

### Scale the operations that need it

File/version histories, shared projects, all-folder lists and checkpoint comments are not consistently bounded. Blade templates execute counts and relationship queries; folder/ZIP recursion and storage deletion add more queries per object. Eager-load the specific relationships needed, paginate large histories, and inspect query plans with representative data before adding indexes. Candidate lookup paths include blob/storage references, file+checkpoint history, project+folder lists and admin request status.

Large ZIP export and document parsing should have concurrency, size and timeout limits. Use jobs for durable/retryable work when workers can be operated reliably. If cPanel is the initial target, document the synchronous limits instead of assuming a worker exists. Local storage is viable for one application node; multiple web nodes need shared/object storage and shared coordination before horizontal scaling.

### Choose one frontend delivery strategy

The active layouts use Bootstrap and remote assets; the repository also contains Vite/Tailwind/Alpine infrastructure that is not included with `@vite` in the current views. Tailwind 3 and a Tailwind 4 Vite adapter are both declared. There are unused Breeze partials/components alongside the custom Bootstrap screens.

Prefer one supported asset pipeline. A build can bundle pinned browser dependencies, including PDF.js and CodeMirror, make security auditing repeatable, and reduce CDN availability/privacy dependencies. If retaining CDN delivery, maintain an explicit external-asset inventory and compatible integrity/security controls. Remove unused scaffold only after confirming no view/component references it.

Office preview currently sends a file URL to Google Docs Viewer, but every file download route requires login and verified email, even for a public project. Google does not possess that session. Do not solve this by exposing private storage broadly: choose server-side conversion, an explicitly public-only viewer, or a narrowly scoped, time-limited retrieval mechanism consistent with the intended privacy policy.

## Documentation: keep, rewrite, merge, or archive

Most document categories are useful; the problem is that several describe a planned system rather than the implemented one. Do not delete them wholesale. Reduce duplication and make each file have a clear audience and source of truth.

| Document | Decision | Required changes |
| --- | --- | --- |
| `README.md` | Kept and rewritten | States the locked Laravel/PHP requirements, verified local SQLite path, actual private storage model, feature limits, recovery prerequisites, and links to the authoritative documents. |
| `SELF_HOSTING.md` | Kept as the authoritative deployment guide | Replaces the duplicate production guide; blocks MySQL/MariaDB deployment until the migration gate is resolved and documents configuration, backup/recovery, permissions, and smoke checks. |
| `PRODUCTION_GUIDE.md` | Retired | Its useful operational content is consolidated in `SELF_HOSTING.md`; unsupported production-readiness claims were removed. |
| `docs/ARCHITECTURE.md` | Kept and rewritten | Describes the actual Laravel 13 runtime, local-disk lifecycle ledger, access model, current data records, operation bounds, external browser assets, and known limits. |
| `docs/API.md` | Replaced by an accurate web-route reference | Records current web routes, methods, middleware, nesting, and response conventions; it explicitly says no public REST API exists. |
| `SECURITY.md` | Kept and rewritten | Preserves reporting contacts and states verified controls, scope, unresolved deployment/OTP/dependency limits, and security status without absolute claims. |
| `ROADMAP.md` | Retired at maintainer request | Open work remains in the review's delivery order and remediation status rather than an inaccurate standalone roadmap. |
| `CHANGELOG.md` | Kept and updated | Records the Groups 1–3 remediation, documentation correction, roadmap retirement, and remaining release blockers without claiming a production release. |
| `CONTRIBUTING.md` | Kept and rewritten | Aligns local setup, storage, tests, migration gate, and contribution expectations with the present application. |
| `CODE_OF_CONDUCT.md` | Keep for a public contributor community; optional for a closed solo project | It is community governance, not an application specification. Verify contact/enforcement commitments can be maintained. |
| `LICENSE.md` | Kept; legal decision still required | The third-party notice table now identifies `jfcherng/php-diff` as BSD-3-Clause and PHPWord as LGPL-3.0, and no longer links to a missing notice file. `composer.json` still says MIT while this document defines SCL, and ZIP attribution remains promised but unimplemented. |
| `AGENTS.md` | Keep if AI-assisted maintenance continues | It is tooling guidance, not product documentation. The initial bootstrap was replaced by generated Laravel guidance during this review, as instructed. Keep repository rules realistic and version-aligned. |
| `CLAUDE.md` | Optional, depending on Claude use | Avoid maintaining two diverging copies of project policy; share/synchronize common rules. Keep tool-specific instructions only where needed. |
| `resources/branding/SCHOOLSHARE_BY_ETHIONEXT.txt` | Keep if the attribution requirement remains | It is an export asset rather than another guide. The downloaded ZIP included files but not this notice. Include it consistently with the documented branding mode. |

The “open source” description also conflicts with SCL's explicit non-commercial restrictions. The [Open Source Initiative definition](https://opensource.org/osd) excludes restrictions on business use. Choose accurate product/license terminology and align the manifest, README, public pages and license text; this review does not decide the licensing model or provide an enforceability opinion.

### Documents that are missing or should be consolidated

| Need | Minimum useful content |
| --- | --- |
| Product/domain contract | Define snapshot versus changed-file semantics; owner/editor/viewer powers; public versus signed-in visibility; who pays for shared uploads; deletion/restore/fork behavior; canonical plan limits. This can be a concise section of the architecture document. |
| Operations runbook | Deploy/rollback, required PHP/Node/extensions, mail failure recovery, admin provisioning, worker/scheduler requirements, logging/alerting, backup/restore drill, disk/temporary-file cleanup and supported database versions. Merge with self-hosting if that keeps it maintainable. |
| Test/release checklist | Reproducible test setup, production-engine migrations, permission matrix, historical recovery, quota reconciliation, dependency checks and browser smoke flow. Link from contributing rather than duplicating it. |
| Privacy/data-handling explanation | Clarify storage, public/private behavior, collaboration, external viewer/avatar/CDN requests, deletion/retention, and support access. Terms contain brief privacy statements, but not a complete data-handling explanation. Establish the intended policy before making promises. |
| Third-party notices | Add the already-referenced notices inventory using actual installed licenses and external browser assets. |

A public REST API specification, Kubernetes guide, microservices design, and large speculative design documents are not needed for the current application. Keep future ideas separate from the manual describing today's behavior.

## Delivery order and release gate

| Stage | Work | Evidence required to proceed |
| --- | --- | --- |
| P0: release blockers | README sanitization, PDF.js replacement, private search/feed isolation, folder/archive constraints, quota/reference ownership, fresh and populated migrations | Regression cases fail before fixes and pass afterward; clean install and upgrade both succeed on supported database engines; retained blobs remain correctly charged. |
| P1: product contract | Global route identity, onboarding usernames, checkpoint/diff/comments, collaborator UI binding, history/deletion/reconciliation, OTP/email change, admin pages | A new account can complete the core workflow; owner/editor/viewer boundaries hold; restoring a moved/deleted file reproduces a prior checkpoint. |
| P2: operability and consistency | Plan keys/settings, historical links/export, retryable mail, dependency lock/inventory, bounded operations, accurate docs | Deployment from written instructions works on a clean environment; backup restore and disk reconciliation are demonstrated; advertised features match tested behavior. |
| Later | Query optimization, object storage, chunked uploads, subscription automation, richer integrations | Add only when measured demand or a committed product requirement justifies them. |

Minimum regression coverage should include create/upload/edit/view/download/diff/restore/delete, duplicate owner slugs, foreign IDs, current and historical permissions, failed storage writes, concurrent quota reservation, account deletion after collaboration, expired/old-email OTPs, admin state transitions, and clean/populated migrations on the intended database. Add CI so those checks run consistently; no CI workflow was present in this repository.

## Review artifacts and setup effects

Temporary evidence is available in this workstation session at `/tmp/SchoolShareAuditTest.php`, `/tmp/schoolshare-baseline.xml`, `/tmp/schoolshare-audit-final.xml`, `/tmp/schoolshare-migration-probe.php`, and `/tmp/schoolshare-browser-jVGpjg/`. These are local diagnostic artifacts, not a committed regression suite, and may be removed by temporary-directory cleanup. The report above contains the relevant results independently of them.

As required by the supplied repository instructions, PHP/Composer were checked, Laravel Boost 2.10.1 was installed as a development dependency, and its installer was run. This changed `composer.json`, `composer.lock`, `AGENTS.md`, `CLAUDE.md`, and generated `.claude/`, `.mcp.json`, and `boost.json`. Codex-specific skill/MCP installation could not write to the workspace's protected `.agents`/`.codex` directories. The review used the generated Laravel/testing guidance and command-line tools; that setup limitation did not stop the code/browser review.

During the original review, no application fixes, production deploys, live-data migrations, messages to third parties, or edits to existing product/operations documentation were made. Frontend installation/build occurred in a temporary copy. Only review-owned test services were used. Those test servers and containers were stopped, and their synthetic container database volumes removed; existing application data was untouched. Temporary evidence files remain available at the paths above. Subsequent application changes are listed in the remediation progress section.

## Project rating

This score evaluates the current delivered system, including correctness, security and operability. It is not a rating of the idea or the effort invested. A polished screen cannot offset private-data leaks, broken recovery or an installation that fails on the documented database.

| Area | Score / 10 | Weight | Reason |
| --- | --- | --- | --- |
| Security | 3 | 20% | Working framework protections, but reproduced XSS, metadata isolation and quota defects |
| Functional correctness / data integrity | 4 | 25% | Upload/edit basics work; checkpoint/diff/comment/collaborator/history behavior needs repair |
| Architecture / maintainability | 6 | 15% | Suitable monolith and understandable models; critical domain rules need central ownership |
| Testing / verification | 3 | 15% | Existing tests are mostly auth scaffolding and miss the core project/version/security contracts; no CI |
| Deployment / operations | 3 | 10% | Fresh MariaDB and populated upgrades fail; recovery/cleanup guidance is incomplete |
| Documentation accuracy | 3 | 10% | Useful document categories, but substantial drift and unsupported completion claims |
| Interface / product clarity | 8 | 5% | Clear concept, coherent presentation, successful basic browser flow and tested responsive dashboard |

Weighted result: **3.95/10**, rounded overall rating: **4/10**.
