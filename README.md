# UTM APEL Management System

A web application for administering **APEL** — *Accreditation of Prior Experiential
Learning* — at Universiti Teknologi Malaysia. APEL lets people qualify for university
study, or claim credit for individual courses, on the strength of work experience and
prior learning rather than conventional academic transcripts.

The system carries an application from submission through payment, evaluator
assessment and final decision, across three roles, on two distinct tracks.

**Final Year Project** · Laravel 12 · MongoDB · PHP 8.2

[![tests](https://github.com/Fri3ndlyP4nda/FYP-PROJECT/actions/workflows/tests.yml/badge.svg)](https://github.com/Fri3ndlyP4nda/FYP-PROJECT/actions/workflows/tests.yml)

---

## The two tracks

| | **APEL A** | **APEL C** |
|---|---|---|
| Purpose | Admission to a programme | Credit transfer for one course |
| Entry rule | Age ≥ 30, Malaysian IC, at least a Diploma | At least a Diploma, 3 years' relevant experience |
| Assessment | Evaluator review of the application | A graded assessment — written paper or portfolio |
| Outcome | Admitted / not admitted | Credit hours awarded / refused |

APEL C additionally passes through an **academic advisor** who recommends the
candidate before any fee is payable, and its assessment is marked against four Course
Learning Outcomes (CLOs). A candidate passes only by reaching at least half marks on
**every** CLO — not by a total, so a strong showing in three cannot carry a fourth.

## Roles

| Role | Can do |
|---|---|
| **Student** | Submit and edit a draft, upload a payment receipt, sit an assessment or submit a portfolio, appeal a rejection, print their own portfolio |
| **Evaluator** | Review applications assigned to them, publish an assessment paper, grade submissions. Up to two evaluators may be assigned to one application |
| **Admin** | Verify payment, assign evaluators, record the advisor decision, finalise admission or credit, run reports, manage staff accounts |

---

## Architecture

The workflow is the interesting part of this codebase, so it is modelled explicitly
rather than left implicit in controller conditionals.

```
app/Domain/Apel/
├── ApelStage.php              every stage an application can reach — and only those
├── StageMachine.php           the one place a stage may change; illegal moves throw
├── NextAction.php             whose turn it is, and what they must do
├── Eligibility.php            the APEL A entry rules, in one place
└── IllegalStageTransition.php
```

**Why a stage machine.** Position in the workflow was originally spread across four
fields written independently by eleven code paths — `status`, `review_stage`,
`credit_status`, `payment_status`. They disagreed in practice: submitting an answer
set `status` to *Awaiting Final Decision* while `credit_status` said
*submitted_for_grading*, so the admin queue showed ungraded work as ready to decide.
Verifying a payment overwrote whatever stage had actually been reached.

Every transition now goes through `StageMachine::transition()`, which rejects an
illegal move instead of silently corrupting the record, writes the audit entry once,
and derives the four legacy fields from the stage so they cannot contradict it.

### APEL A

```
draft → submitted → payment_due → payment_submitted → payment_verified
      → evaluator_assigned → under_review → [partially_reviewed]
      → awaiting_decision → approved | rejected
                                        └→ appeal_submitted → appeal_under_review
```

### APEL C

```
draft → submitted → advisor_review → advisor_approved → payment_due
      → payment_submitted → payment_verified → evaluator_assigned
      → assessment_set → submitted_for_grading → under_review
      → [partially_reviewed] → awaiting_decision → approved | rejected

advisor_review → advisor_rejected   (terminal — no fee is ever taken)
```

`partially_reviewed` exists for the two-evaluator case: one has reported, the other
has not, and the application must not advance until both have.

### Core models

`Application` is the spine — 123 edges in the code graph. `User` (67) carries the
three roles. `AssessmentSubmission` and `AssessmentPaper` hold the APEL C assessment;
`ActivityLog` is the audit trail; `Course` and `Programme` are reference data.

### Uploaded documents

Every uploaded file — payment receipts, portfolio evidence, answer scripts, assessment
papers — lives on a **private disk with no public URL**. Reads go through
`SecureFileController`, which re-applies the same ownership rule that guards the page:
the candidate, either assigned evaluator, or an administrator. Nothing is served by
URL alone.

---

## Running it

**Requires** PHP 8.2+, Composer, Node 18+, and a reachable MongoDB (local or Atlas).

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
# Set DB_URI and DB_DATABASE in .env to point at your MongoDB

php artisan migrate          # indexes + the stage backfill
php artisan db:seed          # demo accounts, programmes, courses
php artisan storage:link

php artisan serve
```

Mail is queued, so a worker must be running for notifications to send:

```bash
php artisan queue:work
```

`composer dev` starts the server, a queue worker, the log tailer and Vite together.

### Demo accounts

Created by `database/seeders/UserSeeder.php`. Passwords are read from the
environment (`SEED_STUDENT_PASSWORD` and friends) so a deployed instance never
inherits these; the defaults exist so a fresh clone is usable immediately.

| Role | Email | Password |
|---|---|---|
| Student | `student@apel.test` | `ApelStudent2026` |
| Evaluator | `evaluator@apel.test` | `ApelEvaluator2026` |
| Admin | `admin@apel.test` | `ApelAdmin2026` |

The `.test` domain cannot receive mail, so password reset will not deliver to these
addresses — use a real one to exercise that flow.

Two-factor authentication is **disabled by default** (`APEL_2FA_ENABLED=false`).
The flow is intact, not removed: set it to `true` to require an emailed one-time code.

---

## Tests

```bash
php artisan test
```

**183 tests, 1779 assertions.** They run against a real MongoDB, not sqlite — every
model in this application pins `$connection = 'mongodb'`, and the `unique:users,email`
rule resolves against the *default* connection, so an in-memory sqlite database gave
them nothing to talk to.

Point `phpunit.xml` at a local mongod (it defaults to `mongodb://127.0.0.1:27017`,
database `apel_testing`). The suite truncates that database between tests and refuses
to run against any database whose name does not contain `testing`, so it cannot touch
real applicant data.

| Suite | Covers |
|---|---|
| `RoleBoundaryTest` | Every protected route against unauthenticated and wrong-role callers; IDOR — student A cannot reach student B's application by any route |
| `SecureFileAccessTest` | Document authorization, including path traversal and paths belonging to a different application |
| `AuthFlowTest` | Login, captcha, throttling, password reset, no user enumeration |
| `ApelAWorkflowTest` / `ApelCWorkflowTest` | Both tracks end to end, plus each workflow guard |
| `ApelStageTest` | The stage machine in isolation — legal moves, terminal states, the progress rail |
| `EligibilityTest` | Entry rules and their boundaries |
| `DashboardRenderTest` | Every screen rendered at every stage, for each role - plus the specific defects that hid behind stale status strings |
| `ScalePerformanceTest` | Renders each screen against two data sets an order of magnitude apart and asserts the query count barely moves - the absolute number does not matter, the slope does |
| `ConcurrencyTest` | Two people acting on one application at once: the losing writer is refused, and the winner's audit entry survives |

Writing this suite surfaced defects that were live in the application, including one
that made every stage read throw — the whole workflow returned 500 and nothing had
caught it.

`DashboardRenderTest` was added while rebuilding the interface, and pins a family of
bugs worth naming. `StageMachine` writes the legacy `status` field as
`$stage->label($type)`, and six separate screens still compared it against the strings
the application used *before* the stage machine existed. Each comparison silently
failed:

- Both student progress trackers reported step 1 of 5 for every application, an
  approved one included — all 19 stages fell through to a `default` arm.
- `str_contains('Not approved', 'approved')` is `true`, so a **rejected** APEL A
  application was counted as **Approved** on the candidate's own list.
- The APEL C advisor form was gated on `'Under Advisor Review'` while the stage writes
  `"Advisor review"`, so it never rendered — leaving no way for the registry to record
  an advisor decision, and no way for an APEL C application to pass its second step.

None of these threw. Each test here renders a real screen at a real stage, which is
the only thing that catches a comparison that is merely wrong.

---

## Load and concurrency

The two questions worth asking of any system that more than one person uses.

**Does a screen's cost grow with the data behind it?** `ScalePerformanceTest`
renders each one against two data sets an order of magnitude apart and compares
the query counts. It found the account list issuing one query per evaluator - 4
users cost 7 queries, 34 cost 37 - and both printed reports resolving a user
inside the row loop. All flat now.

**What is genuinely unbounded?** Most lists are not: one candidate's
applications, one evaluator's assignments, the staff roll. Two were. The
registry queue loaded every non-draft application ever submitted, and the
account list every account. Live applications are bounded by how much work is
actually in flight; closed ones and registered candidates only accumulate. Both
screens now load a bounded slice and say so, with a true total beside it - a
list that quietly omits rows is worse than one that admits a limit, because the
reader cannot tell "not here" from "not shown".

**Two people, one application.** `StageMachine::transition()` was a
read-check-write: both officers read the same stage, both found their move
legal, both wrote, and the second silently overwrote the first - taking the
first one's audit entry with it, since each appended to the copy of
`stage_history` it had read. The write is now conditional on the stage still
being what was read, so exactly one of two racing writers can win and the loser
is told rather than ignored.

## Security

Applicant records contain Malaysian IC numbers and identity documents, so the
authorization model is the part of this system that most needs to be right.

- **Ownership is enforced inside the query**, not checked after the fetch — a forged
  id returns 404 rather than someone else's record.
- **No mass assignment.** Every write is an explicit field map, so a student cannot
  set `final_decision`, `payment_status` or `role` by posting them.
- **Rate limiting** on login, 2FA, registration and password reset, keyed on identity
  *and* IP — a shared campus NAT is not one bucket, so one student's failed attempts
  cannot lock out everyone else.
- **One-time codes** use `random_int()`, are stored hashed, and are compared in
  constant time.
- **Password reset** does not reveal whether an address has an account.
- **Security headers** on every response: a content security policy,
  `frame-ancestors 'none'`, `nosniff`, a referrer policy that does not leak
  internal paths carrying application ids, and HSTS over HTTPS only. Every other
  control here runs on the server, which means none of them help once something
  is executing inside the user's own page.
- **Session cookies** are `SameSite=Strict`, secure outside local, and encrypted.
  Under `lax` the session still travels on a top-level navigation from another
  origin, so a link an attacker gets an administrator to click arrives
  authenticated.
- **Authentication events are recorded** - successes, failures, sign-out,
  two-factor failures, both ends of a password reset - with the address
  attempted and the source IP. Rate limiting that refuses an attack and leaves
  no trace is the difference between a system that resisted one and a system
  where nobody can tell there was one. A value that is not a valid email address
  is logged as malformed rather than verbatim, because people type their
  password into the email field.
- **Credentials are never flashed back to a form.** `withInput()` with no
  arguments flashes every submitted field, and Laravel only strips passwords
  from the automatic flash a validation failure performs.
- **Uploaded documents** are never served by URL alone (see above). Reaching that state
  needed both halves: `SecureFileController` was written and tested, but no view had
  ever linked to it — every document link in the interface was still a public
  `storage/` URL, and 121 files including payment receipts and IC-bearing evidence
  were fetchable with no session. `php artisan apel:secure-documents` moves any such
  file onto the private disk (`--dry-run` first; it verifies each copy before removing
  the original) and every link now goes through the authorised route.

---

## Known limitations

Stated plainly rather than left to be discovered:

- **Not load tested.** Correctness is covered by the suite; behaviour under
  concurrent load is not measured.
- **Eligibility rules are simplified** against real MQA/APEL policy. In particular the
  system does not model whether prior learning is *relevant to the specific programme*
  — it checks level, age and duration only.
- **Assessment deadlines are stored without a timezone**, and the application runs in
  UTC. A deadline set in Malaysia (UTC+8) is enforced eight hours late.
- **A qualification naming two levels** takes the higher one, so "Postgraduate
  Certificate" reads as a certificate. Whether it should outrank a Diploma is a policy
  question, not a bug.
- **No end-to-end browser tests.** Controller behaviour is covered through HTTP; the
  JavaScript in the larger forms is not exercised by an automated browser.
- **`app/Models/TestItem.php` is unused** — left from early development.

---

## Licence

Coursework submitted for assessment at Universiti Teknologi Malaysia.
