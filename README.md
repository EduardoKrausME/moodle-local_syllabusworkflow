# Syllabus Workflow (`local_syllabusworkflow`)

Institutional course syllabus preparation, approval and publication within Moodle.

## Prerequisites

- Moodle 4.5 LTS (minimum) or Moodle 5.x; PHP version supported by Moodle.
- An installation where Moodle scheduled tasks, messages and locks are configured.
- Administrative access for capability configuration and reviewer role assignments.

## Workflow

Draft → Submitted → Under review → Approved → Published → Archived.
Reviewers may instead transition Submitted/Under review → Changes requested; the teacher creates a **new draft revision** and submits again. An approved/published version never becomes editable: create a new revision via the dedicated action. The plan retains a pointer to the most recently published version, even when a new draft is being prepared.

`version_manager` uses Moodle's Lock API and delegated transactions for draft edits and revisions, plus a monotonic `lockversion` to reject edits from stale browser tabs. `approval_service` and `publication_service` lock the plan during transitions. Approval decisions, comments, assignments, state changes and publication records are stored separately from the document. State mutations are validated in the backend, never inferred from UI controls.

Reviewer assignments can be staged with one reviewer at each sequential stage. For each stage the administrator may specify a reviewer user ID and number of calendar days for the review deadline. An unassigned/placeholder reviewer must be specified when submitting. A manager can delegate an outstanding assignment; delegations are audited. This release does not support parallel quorum voting or escalations.

## Document structure and snapshots

`local_syllabusworkflow_tplversion` contains immutable versions of model JSON (`schemajson`, `workflowjson`, `identityjson`). `local_syllabusworkflow_version` contains syllabus data in a keyed JSON snapshot, a SHA-256 content digest and the frozen institutional identity, course name and author name. The model revision and syllabus revision counters are independent. Approved payload and captured metadata are preserved; changes in later Moodle course names do not rewrite historical versions. The plan's active institutional model is pinned when created.

There are 19 supported sections: course identification, discipline name and code, term, workload, teacher, syllabus, objectives (general and specific), competencies, contents, teaching methods, resources, assessment criteria and tools, schedule, basic and complementary bibliography and institutional notes. A model specifies order, required status, edit lock and guidance for each field. Fields are stored as plain text and are safely HTML-escaped in Mustache views and version comparisons. **HTML input is not interpreted**; Moodle HTML allowed-format rendering is not enabled in this edition. Drafts may be incomplete, but submission verifies all required fields.

## UI and routes

- `/local/syllabusworkflow/index.php?courseid=COURSE_ID` — course dashboard.
- `/local/syllabusworkflow/edit.php?courseid=COURSE_ID` — create and save a draft.
- `/local/syllabusworkflow/view.php?id=PLAN_ID` — current revision for staff, published revision for enrolled viewers.
- `/local/syllabusworkflow/compare.php?id=PLAN_ID` — compare two historical versions.
- `/local/syllabusworkflow/template.php` — institutional template creation/versioning (system managers).
- `/local/syllabusworkflow/report.php` — pending and approved plans (system managers).
- `/local/syllabusworkflow/export.php?id=PLAN_ID` — browser/printable HTML export or an HTML attachment (`download=1`), without an improvised PDF library.

The `view.php` URL may be added to a course as a URL resource **manually** by an authorized teacher. This plugin does **not** automatically update the course summary or content, and its course navigation link is added only when a user has syllabus capabilities.

## Security and privacy

Every plan lookup is scoped to the Moodle course context. Read access for non-staff is limited to the published revision. Mutations require a valid session, `sesskey`, and server-side capabilities. Backend decisions verify assigned reviewers, and self-approval requires an explicit exceptional capability. All documents are treated as plain text, not trusted HTML. Historic approvals and transitions contain immutable snapshots and append-only records.

The Privacy API declares stored data, exports authored academic records and deletes uncomplicated single-version unpublished drafts. Approved and shared academic records are retained for institutional accountability; institutions need a documented retention basis and deletion policy. Users' submissions must not contain unnecessary personal data.

Message notifications are sent via Moodle Messaging API, with a database-backed deduplication key. Review reminders run by the scheduled task. Notification text intentionally excludes private plan content.

## Technical architecture

`classes/local/` implements repository, schema validation, template manager, revision manager, transition validator, state machine, approval, audit, publication, diffing, messaging and course integration. `db/install.xml` includes models, model versions, plans, plan versions, assignments, decisions, transitions, publications, notices and audit entries. Every version's `payload` is JSON and versioned independently from its model's JSON schema.

UI pages use Moodle's Form API for the structured syllabus and template editors; Mustache renders the dashboards and snapshots. AMD source/build is included for a copy-link enhancement. Moodle DML provides database independence.

## Validation commands

The mandated project validator is https://github.com/EduardoKrausME/moodle-plugin-validate . Clone it into a sibling directory and follow `.chatgpt/INSTRUCTIONS.md`, then run:

```bash
python3 /absolute/moodle-plugin-validate/.chatgpt/run.py /absolute/local_syllabusworkflow
python3 /absolute/moodle-plugin-validate/.chatgpt/run.py --check /absolute/local_syllabusworkflow
find /absolute/local_syllabusworkflow -name '*.php' -print0 | xargs -0 -n1 php -l
moodle-plugin-ci phplint
moodle-plugin-ci validate
moodle-plugin-ci phpcs --max-warnings 0
moodle-plugin-ci mustache
moodle-plugin-ci grunt
moodle-plugin-ci phpunit --fail-on-warning
moodle-plugin-ci behat
```

Installation verification and role tests must also be performed on running Moodle 4.5 and 5.x sites before a production release. No successful test runs are claimed unless their execution logs are attached.

## Known boundaries

- No automatic conversion to PDF, automatic creation of URL activities, parallel voting quorum, advanced document attachments or digital signatures.
- HTML is treated as text deliberately; no rich HTML formatting is stored or rendered.
- Deadlines are in calendar days, with reminders at most once per *soon* and *late* phase per reviewer/assignment.
- Exports include HTML/print layout, not ODT/DOCX.

## License

GNU General Public License, version 3 or later. See `LICENSE`.
