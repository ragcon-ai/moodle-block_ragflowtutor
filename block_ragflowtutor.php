<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RAGflow Tutor chat block.
 *
 * Renders the shared RAGflow chat drawer on a course/activity page, driven by this block instance's own
 * configuration (its RAGflow assistant/knowledge base, greeting, system instruction, document source,
 * sources and proxy). The chat engine lives in the aiprovider_ragflow provider; this block is the
 * placement + per-instance configuration.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ragflowtutor extends block_base {
    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ragflowtutor');
    }

    /**
     * Render the Tutor chat drawer (shared engine in the provider).
     *
     * @return \stdClass
     */
    public function get_content() {
        global $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }
        // The shared chat engine lives in the RAGflow provider.
        if (!class_exists('\aiprovider_ragflow\output\chat')) {
            return $this->content;
        }

        $chatid = trim((string) ($this->config->chatid ?? ''));
        if ($chatid === '') {
            // Not configured yet: point each viewer at the step their own role can take. Those who may
            // choose/create the knowledge base go to the block settings; anyone else who may edit the
            // block but not the KB (e.g. trainers, who have no editkb/createkb) is directed to a site
            // administrator instead – the KB field is not shown to them. Ordinary users see nothing.
            $ctx = $this->context;
            $cankb = is_siteadmin()
                || has_capability('block/ragflowtutor:editkb', $ctx)
                || has_capability('block/ragflowtutor:createkb', $ctx);
            $key = '';
            if ($cankb) {
                $key = 'notconfigured';
            } else if (
                has_capability('block/ragflowtutor:addinstance', $ctx)
                || has_capability('block/ragflowtutor:editcontent', $ctx)
            ) {
                $key = 'notconfigured_askadmin';
            }
            if ($key !== '') {
                $this->content->text = \html_writer::div(
                    get_string($key, 'block_ragflowtutor'),
                    'text-muted small'
                );
            }
            return $this->content;
        }
        $text = '';

        // Managers / trainers see the knowledge-base panel (name + status lights) and, for a Moodle-managed
        // KB, the document manager. It sits in the block (no edit mode needed); students never see it.
        if (has_capability('block/ragflowtutor:managefiles', $this->context)) {
            $text .= $this->render_kb_panel();
        }

        // The Tutor chat drawer (anyone who may use it).
        if (has_capability('block/ragflowtutor:use', $this->page->context)) {
            $greeting = (string) ($this->config->greeting ?? '');
            if (trim($greeting) === '') {
                $greeting = get_string('greetingdefault', 'block_ragflowtutor');
            }
            $text .= \aiprovider_ragflow\output\chat::render_drawer([
                'userid' => (int) $USER->id,
                'contextid' => (int) $this->page->context->id,
                'blockinstanceid' => (int) $this->instance->id,
                'label' => get_string('toggle', 'block_ragflowtutor'),
                'greeting' => $greeting,
            ]);
        }

        $this->content->text = $text;
        return $this->content;
    }

    /**
     * The in-block knowledge-base panel shell (name + status lights + document manager). The AMD module
     * fills it via AJAX and polls while anything is still parsing.
     *
     * @return string
     */
    protected function render_kb_panel(): string {
        global $CFG;
        $this->page->requires->js_call_amd('block_ragflowtutor/manage', 'init', [
            (int) $this->instance->id,
            \block_ragflowtutor\manager::effective_upload_limit_bytes(),
            $CFG->wwwroot,
            sesskey(),
        ]);
        $out = \html_writer::start_div('rfdtutor-kbpanel', [
            'data-region' => 'rfdtutor-kbpanel',
            'data-blockinstanceid' => (int) $this->instance->id,
        ]);
        // Title = assistant name (filled by the AMD module from the status).
        $out .= \html_writer::div('', 'rfdtutor-kbpanel-title', ['data-region' => 'rfdtutor-title']);
        // Status line: label + traffic-light dot + refresh button.
        $refresh = \html_writer::tag(
            'button',
            '<i class="icon fa fa-sync" aria-hidden="true"></i>',
            [
                'type' => 'button',
                'data-action' => 'rfdtutor-refresh',
                'class' => 'btn btn-icon rfdtutor-refresh',
                'title' => get_string('kbpanel:refresh', 'block_ragflowtutor'),
                'aria-label' => get_string('kbpanel:refresh', 'block_ragflowtutor'),
            ]
        );
        $out .= \html_writer::div(
            \html_writer::tag('span', get_string('kbpanel:status', 'block_ragflowtutor'), ['class' => 'rfdtutor-status-label'])
                . \html_writer::span('', 'rfdtutor-statusdot', ['data-region' => 'rfdtutor-statusdot'])
                . $refresh,
            'rfdtutor-status'
        );
        $out .= \html_writer::div('', 'rfdtutor-files', ['data-region' => 'rfdtutor-files']);
        $out .= \html_writer::div('', 'rfdtutor-upload mt-2', ['data-region' => 'rfdtutor-upload', 'hidden' => 'hidden']);
        $out .= \html_writer::end_div();
        return $out;
    }

    /**
     * Show on real course and activity pages.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['course-view' => true, 'mod' => true, 'site' => false, 'my' => false];
    }

    /**
     * Only one Tutor per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * This block has a per-instance configuration form.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Has global (site-level) configuration (the upload limit for Moodle knowledge bases).
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Persist the instance configuration, but only the fields the current user is allowed to change, so a
     * trainer editing the greeting / system instruction cannot clear the admin-only knowledge-base and
     * source settings (which are not shown to them).
     *
     * @param stdClass $data
     * @param bool $nolongerused
     * @return void
     */
    public function instance_config_save($data, $nolongerused = false) {
        $ctx = $this->context;
        $isadmin = is_siteadmin();
        $cankb = $isadmin || has_capability('block/ragflowtutor:editkb', $ctx)
            || has_capability('block/ragflowtutor:createkb', $ctx);
        $cancontent = $isadmin || has_capability('block/ragflowtutor:editcontent', $ctx);

        // Start from the existing config so unshown fields survive.
        $merged = (object) (array) ($this->config ?? new stdClass());
        $createresult = null;

        if ($cankb) {
            $cancreate = $isadmin || has_capability('block/ragflowtutor:createkb', $ctx);
            $newname = trim((string) ($data->newkbname ?? ''));
            if (\block_ragflowtutor\manager::locked_after_creation($this->config ?? new stdClass())) {
                // Locked after creation: the knowledge base / assistant binding is fixed (the edit form
                // shows it read-only). Keep the stored values untouched — changing them, or clearing the
                // kbid/seeddocid of a block-owned knowledge base, would silently break retrieval / file
                // management.
                $merged->chatid = trim((string) ($this->config->chatid ?? ''));
                $merged->kbid = trim((string) ($this->config->kbid ?? ''));
                $merged->seeddocid = trim((string) ($this->config->seeddocid ?? ''));
            } else if ($cancreate && ($data->chatid ?? '') === '__new__' && $newname !== '') {
                // Create the new knowledge base (+ pure-LLM assistant); the KB is bound once it has
                // parsed content. The name was already validated for uniqueness in the edit form.
                $createresult = $this->create_kb($newname);
                $merged->chatid = $createresult['chatid'];
                $merged->kbid = $createresult['kbid'];
                $merged->seeddocid = $createresult['seeddocid'];
            } else {
                // First-time binding to an existing assistant: it already carries its own KB, so no
                // lazy-bind (kbid is only for a freshly auto-created KB paired with a new assistant).
                $merged->chatid = (($data->chatid ?? '') === '__new__') ? '' : trim((string) ($data->chatid ?? ''));
                $merged->kbid = '';
                $merged->seeddocid = '';
            }
        }
        if ($cancontent) {
            $merged->greeting = (string) ($data->greeting ?? '');
            $merged->systeminstruction = (string) ($data->systeminstruction ?? '');
        }
        if ($isadmin) {
            // Document source is fixed at creation. Derive it once from the two context controls of the
            // edit form (new KB -> "manage files" checkbox; existing KB -> metadata filter); afterwards the
            // form echoes the stored value via a hidden field, so keep it as-is. "Configured" = the block is
            // already bound to a KB/assistant (for a brand-new instance this very save is the binding one).
            $wasconfigured = trim((string) ($this->config->chatid ?? '')) !== ''
                || trim((string) ($this->config->kbid ?? '')) !== '';
            if ($wasconfigured) {
                $merged->datasource = (string) ($data->datasource ?? ($this->config->datasource ?? 'wholekb'));
            } else if (($data->chatid ?? '') === '__new__') {
                // New knowledge base: manage its files from this block (dedicated KB) or connect only.
                $merged->datasource = empty($data->managefiles) ? 'wholekb' : 'thiscourse';
            } else {
                // Existing knowledge base: map the metadata-filter choice to the stored source.
                $filter = (string) ($data->metadatafilter ?? 'none');
                $merged->datasource = ($filter === 'thismoodle') ? 'thismoodle'
                    : (($filter === 'external') ? 'external' : 'wholekb');
            }
            $merged->coursemetadatafield = trim((string) ($data->coursemetadatafield
                ?? ($this->config->coursemetadatafield ?? 'course_id')));
            $merged->includesources = empty($data->includesources) ? 0 : 1;
            $merged->serveviaproxy = empty($data->serveviaproxy) ? 0 : 1;
            $merged->extraparams = (string) ($data->extraparams ?? '');
        }

        parent::instance_config_save($merged, $nolongerused);

        // Report the KB creation + linking outcome to the user.
        if ($createresult !== null) {
            self::notify_kb_creation($createresult);
        }
    }

    /**
     * Show a success/error notification for a knowledge-base creation attempt.
     *
     * @param array $result The result from {@see create_kb()}.
     * @return void
     */
    protected static function notify_kb_creation(array $result): void {
        $name = s((string) $result['name']);
        switch ($result['status']) {
            case 'error':
                \core\notification::error(
                    get_string('createkb:failed', 'block_ragflowtutor', s((string) $result['message']))
                );
                break;
            case 'linked':
                \core\notification::success(get_string('createkb:linked', 'block_ragflowtutor', $name));
                break;
            case 'pending':
            default:
                // Seeded but the seed was not parsed within the wait budget; a background task finishes
                // the binding shortly.
                \core\notification::info(get_string('createkb:pending', 'block_ragflowtutor', $name));
                break;
        }
    }

    /** @var int Seconds to wait synchronously for the seed document to parse before deferring to a task. */
    const SEED_WAIT_SECONDS = 12;

    /**
     * Create a new RAGflow knowledge base (dataset) + a pure-LLM assistant (RAGflow tenant model defaults),
     * and link the assistant to the KB. RAGflow refuses to link an *empty* (unparsed) dataset, so we upload
     * a tiny README.md provenance file (course, block, creator, Moodle URL, timestamp), parse it, bind the
     * assistant, then clear the seed's chunks – leaving the README visible in RAGflow but out of retrieval.
     * The parse is awaited briefly; if it exceeds the budget, an ad-hoc task finishes the binding.
     *
     * @param string $name
     * @return array {chatid: string, kbid: string, status: 'error'|'pending'|'linked', message: string, name: string}
     */
    protected function create_kb(string $name): array {
        global $DB;
        $result = ['chatid' => '', 'kbid' => '', 'seeddocid' => '', 'status' => 'error', 'message' => '', 'name' => $name];
        if (!class_exists('\aiprovider_ragflow\helper')) {
            $result['message'] = 'provider not available';
            return $result;
        }
        $prov = $DB->get_record_select(
            'ai_providers',
            'provider = :p AND enabled = 1',
            ['p' => 'aiprovider_ragflow\\provider'],
            '*',
            IGNORE_MULTIPLE
        );
        if (!$prov) {
            $result['message'] = get_string('noprovider', 'block_ragflowtutor');
            return $result;
        }
        $conf = json_decode($prov->config, true) ?: [];
        $base = (string) ($conf['baseurl'] ?? '');
        $key = (string) ($conf['apikey'] ?? '');
        // The embedding model and chat LLM are left to the RAGflow tenant defaults (managed in RAGflow).
        $ds = \aiprovider_ragflow\helper::create_dataset($base, $key, $name);
        if ($ds['id'] === '') {
            $result['message'] = ($ds['error'] !== '') ? $ds['error'] : 'knowledge base creation failed';
            return $result;
        }
        $chat = \aiprovider_ragflow\helper::create_chat($base, $key, $name);
        if ($chat['id'] === '') {
            $result['message'] = ($chat['error'] !== '') ? $chat['error'] : 'assistant creation failed';
            return $result;
        }
        \cache::make('aiprovider_ragflow', 'datasets')->delete((int) $prov->id);
        $result['chatid'] = $chat['id'];
        $result['kbid'] = $ds['id'];

        // Seed a tiny README so the KB "owns a parsed file" and the assistant can be linked.
        $docid = \aiprovider_ragflow\helper::upload_text_document(
            $base,
            $key,
            $ds['id'],
            'README.md',
            $this->build_seed_readme()
        );
        if ($docid === '') {
            // Could not seed; fall back to the engine's lazy binder (links on the first chat once content
            // exists). Report as pending rather than failing the whole creation.
            $result['status'] = 'pending';
            return $result;
        }
        $result['seeddocid'] = $docid;
        \aiprovider_ragflow\helper::parse_documents($base, $key, $ds['id'], [$docid]);

        // Wait briefly for the parse, then bind + clear the seed chunks.
        $deadline = time() + self::SEED_WAIT_SECONDS;
        $status = 'pending';
        do {
            $status = \aiprovider_ragflow\helper::try_finish_seed($base, $key, $ds['id'], $chat['id'], $docid);
            if ($status !== 'pending') {
                break;
            }
            if (time() >= $deadline) {
                break;
            }
            sleep(2);
        } while (time() < $deadline);

        if ($status === 'pending') {
            // Defer completion to a background task (parse still running).
            $task = new \aiprovider_ragflow\task\finish_kb_binding();
            $task->set_custom_data([
                'providerid' => (int) $prov->id,
                'datasetid' => $ds['id'],
                'chatid' => $chat['id'],
                'docid' => $docid,
            ]);
            \core\task\manager::queue_adhoc_task($task);
        }
        $result['status'] = ($status === 'linked') ? 'linked' : 'pending';
        return $result;
    }

    /**
     * Build the small README.md provenance file uploaded into a newly created knowledge base.
     *
     * @return string
     */
    protected function build_seed_readme(): string {
        global $USER, $CFG;
        $coursename = '';
        $coursecontext = $this->context->get_course_context(false);
        if ($coursecontext) {
            $course = get_course($coursecontext->instanceid);
            $coursename = format_string($course->fullname, true, ['context' => $coursecontext]);
        }
        $lines = [
            '# RAGflow Tutor knowledge base',
            '',
            'Provenance marker created for a RAGflow Tutor block. Its content is not used for answers.',
            '',
            '- Moodle: ' . $CFG->wwwroot,
            '- Course: ' . ($coursename !== '' ? $coursename : '-'),
            '- Block instance ID: ' . (int) $this->instance->id,
            '- Created by: ' . fullname($USER),
            '- Created at: ' . date('c'),
            '',
        ];
        return implode("\n", $lines);
    }
}
