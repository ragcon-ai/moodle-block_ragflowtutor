# Tests – block_ragflowtutor

**Plugin version:** `2026082401` (release `0.6.9`) — update this line whenever the tests or the plugin
version change.

PHPUnit tests for this plugin. They run automatically in the bundled **moodle-plugin-ci** GitHub Actions
workflow; to run them locally, use `vendor/bin/phpunit` from a configured Moodle root (see the
[Moodle PHPUnit docs](https://moodledev.io/general/development/tools/phpunit)).

This file records **what the tests verify**, in **execution order** (PHPUnit runs the methods top-to-bottom
as defined in each class). Keep it in sync when tests are added, reordered or changed.

## Coverage

### `manager_test.php` — file-manager helper logic (`\block_ragflowtutor\manager`)

| # | Test | Verifies |
|---|---|---|
| 1 | `test_run_to_status` | `run_to_status()` maps a RAGflow document run state to a traffic light: DONE or any parsed chunks = green, FAIL/CANCEL = red, otherwise (UNSTART/RUNNING with no chunks) yellow. |
| 2 | `test_manageable` | `manageable()` allows file management **only** for a Moodle-managed "This course" knowledge base (kbid set + datasource `thiscourse`); every other source (whole KB, this Moodle, external, or no kbid) is not manageable. |
| 3 | `test_upload_limit_bytes` | `upload_limit_bytes()` converts the stored MB setting to bytes; `0` means unlimited (`0`). |
| 4 | `test_effective_upload_limit_bytes` | `effective_upload_limit_bytes()` falls back to Moodle's maximum when the block sets no limit, and otherwise never exceeds the smaller of the block limit and Moodle's maximum. |
| 5 | `test_status_message` | `status_message()` derives the status-dot hover text: a missing assistant or (for a Moodle KB) a missing/linking knowledge base each take precedence, otherwise a "ready" summary. |

### `behat/tutor_block.feature` — acceptance (`@block_ragflowtutor @javascript`)

Run with **moodle-plugin-ci** (the bundled CI runs Behat automatically) or `vendor/bin/behat` from a
configured Moodle (see the [Moodle Behat docs](https://moodledev.io/general/development/tools/behat)).

| # | Scenario | Verifies |
|---|---|---|
| 1 | The block prompts a site admin to configure a knowledge base | Adding the **RAGflow Tutor** block as an admin shows the not-configured hint that points at the block settings (*"in this block's settings"*, no *"Ask a site administrator"*); no RAGflow call on this path. |
| 2 | A trainer without the KB capability is directed to a site administrator | A trainer (`editingteacher`, has `addinstance`/`editcontent` but not `editkb`/`createkb`) who adds the block sees the *"Ask a site administrator to choose a knowledge base"* hint and **not** the block-settings wording — the message matches what the role can actually do. |

## Deliberately not covered here (needs integration / a running RAGflow)

- The RAGflow-backed manager methods (`status`, `upload`, `delete`, `reparse`, `download_url`,
  `owns_document`) call the live RAGflow API and are not unit-tested.
- The external services and the AMD file-manager UI — better suited to Behat.
- The chat drawer itself lives in the shared provider (`aiprovider_ragflow`) and is covered there.
