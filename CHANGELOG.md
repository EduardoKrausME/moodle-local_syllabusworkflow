# Changelog

This changelog records only the two latest **published** releases of `local_syllabusworkflow`, verified on October 24, 2026 (America/Sao_Paulo). Each entry is based on its Git release tag compared with the immediately preceding published tag.

**Post-release note:** The October 24, 2026, 21:52 BRT (`2026-10-25T00:52:49Z`) `Build` commit updated `amd/build/copylink.min.js` **after** release `1.0.3` was published at 21:47 BRT. That compiled-asset change is not included in the published `1.0.3` tag, although the related `amd/src/copylink.js` source changes are. The release summaries below exclude post-release commits.

## [1.0.3] - 2026-10-24

### Fixed
- Reworked the `amd/src/copylink.js` syllabus-plan URL copy handler to use a Moodle-compatible AMD `define(["core/notification"], ...)` module with a returned `init` function, instead of ES-module `import`/`export` syntax.
- Retained the click-triggered clipboard copy behavior and Moodle notification handling for clipboard errors, while standardizing the JavaScript source to double-quoted strings.
- **Distribution limitation:** The `amd/build/copylink.min.js` compiled output was updated only in a later, post-release commit and is therefore not part of this release tag. The source fix alone does not establish that the published built JavaScript asset contains the same fix.

### Maintenance and code quality
- Added Moodle GPL license and template identification comment blocks to the dashboard, version-diff, export, models, plan, and reporting Mustache templates.
- Standardized Moodle license boilerplate links across PHP entry points, domain/service classes, privacy, events, forms, scheduled tasks, language files and tests.
- Replaced `core_text::strlen()` with native `strlen()` in the syllabus payload field length checks in `classes/local/schema.php`.
- Added Git-ref-scoped release-workflow concurrency configuration in the historical `.github/workflows/release.yml`, without cancelling an in-progress release.
- Updated `version.php` metadata from plugin version `2026101500`, release `1.0.2`, to `2026102400`, release `1.0.3`.

**Full comparison:** [1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-local_syllabusworkflow/compare/1.0.2...1.0.3)

## [1.0.2] - 2026-10-15

### Assets
- Added the plugin icon at `pix/icon.png`.

### Release metadata
- Updated `version.php` metadata from plugin version `2026101401`, release `1.0.1`, to `2026101500`, release `1.0.2`.
- No application logic files changed between the two published tags.

**Full comparison:** [1.0.1...1.0.2](https://github.com/EduardoKrausME/moodle-local_syllabusworkflow/compare/1.0.1...1.0.2)
