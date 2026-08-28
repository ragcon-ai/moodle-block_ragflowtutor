# RAGflow Tutor (block_ragflowtutor) #

A course **AI tutor** as a Moodle **block**. Placed in a course, it shows a chat drawer that answers from a
[RAGflow](https://ragflow.io/) knowledge base, scoped to the current course. Each block instance has its own
configuration and reuses the shared chat engine of the **RAGflow AI provider** (`aiprovider_ragflow`), so
credentials are configured once and shared across the suite.

## Features ##

* **Per-instance configuration**: each Tutor block chooses its own **knowledge base / assistant**,
  **greeting**, **system instruction** and source options.
* **Role-based editing**: the knowledge base and document source are for site admins (or the matching
  capabilities); trainers can edit the **greeting and system instruction** without touching admin settings.
* **Create a new knowledge base inline**: admins (or trainers with the capability) can create a brand-new
  RAGflow knowledge base and assistant straight from the block, then upload and manage its documents in the
  block itself.
* **Fixed after creation**: once the block is bound, the **knowledge base / assistant and the document
  source are shown read-only** — changing them would break retrieval, so a different setup means adding a
  new block.
* **Course-scoped answers** with optional source links; the conversation lives in the browser.
* Stores **no personal data of its own**.

## Requirements ##

* **Moodle 5.0–5.2** (core AI subsystem).
* The **RAGflow AI provider** (`aiprovider_ragflow`) installed and enabled — it supplies the shared chat
  engine, the credentials and knowledge-base creation. This block declares a dependency on it.
* **External service (RAGflow), version 0.25 or later:** a reachable [RAGflow](https://ragflow.io/) instance
  and a **RAGflow API key**, configured once in the AI provider. RAGflow can be **self-hosted or hosted by
  RAGcon**. Without a configured RAGflow tenant the block installs but cannot answer or manage files.

## Installation ##

1. Copy the plugin to `blocks/ragflowtutor` in the Moodle tree (**Moodle 5.1+**: `public/blocks/ragflowtutor`).
2. Complete the installation via *Site administration → Notifications* or `php admin/cli/upgrade.php`.
3. In a course, turn editing on → *Add a block* → **RAGflow Tutor**, then configure it (gear → *Configure*).

## Usage ##

Open the **RAGflow Tutor** drawer on the course page, type a question and send it. The answer is grounded in
the configured knowledge base for that course, with source links when *Include sources* is enabled.

## Documentation ##

Full setup and usage documentation: <https://docs.ragcon.ai/moodle-ragflow/plugins/tutor/>

## Privacy and GDPR ##

* Implements the **Moodle Privacy API**: the block stores **no personal data of its own**.
* A user's question — and any documents you upload to the block's knowledge base — is sent to RAGflow
  through the **RAGflow AI provider** (`aiprovider_ragflow`), which owns the data-processing and GDPR
  handling — see that plugin's *Privacy* section. RAGflow can be **self-hosted or hosted by RAGcon**, so the
  processing location is under the operator's control.

## Issues & Contributing ##

* Issues and feature requests: <https://github.com/ragcon-ai/moodle-block_ragflowtutor/issues>

  Please include your **RAGflow version**, **Moodle version**, **plugin version** and the **exact steps to
  reproduce**.
* Pull requests are welcome. The plugin stays **GPLv3**; by contributing you agree your changes are licensed
  under the same terms.

## Support ##

Professional support and web hosting for RAGflow + Moodle are available from **RAGcon GmbH** —
<https://www.ragcon.ai/en> (www.ragcon.ai).

## Community ##

* Moodle — <https://moodle.org>
* RAGflow — <https://ragflow.io>

## Changelog ##

### 0.7.0 ###

* **First public release (beta).** A per-course AI tutor as a Moodle block: a course-scoped chat drawer
  backed by a RAGflow knowledge base, per-instance configuration, role-based editing for trainers, inline
  knowledge-base creation with in-block document management, and source links.

## Acknowledgements ##

This plugin integrates two independent software projects:

* **Moodle** — software by Moodle Pty Ltd, released under the GNU GPL v3 or later
  (<https://github.com/moodle/moodle>). *The word Moodle and associated Moodle logos are trademarks or
  registered trademarks of Moodle Pty Ltd or its related affiliates.*
* **RAGflow** — open-source software by InfiniFlow Inc., released under the Apache License 2.0
  (<https://ragflow.io> · <https://github.com/infiniflow/ragflow>).

This plugin is an independent integration and is not affiliated with or endorsed by Moodle Pty Ltd or
InfiniFlow Inc.

## Development ##

This plugin is part of the Moodle RAGflow suite, developed with the help of a range of AI tools under the
professional supervision of the RAGcon GmbH team — pairing fast, AI-assisted development with human review,
automated testing and security checks before every release.

## License ##

Copyright 2026 RAGcon GmbH <info@ragcon.ai>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU
General Public License as published by the Free Software Foundation, either version 3 of the License,
or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.

The full licence text is in `LICENSE`, or at <https://www.gnu.org/licenses/gpl-3.0.html>.
