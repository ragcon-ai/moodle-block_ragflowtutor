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
 * Per-instance configuration form for the RAGflow Tutor block. Fields are shown by capability: the
 * knowledge base / assistant and document-source settings are site-admin (or editkb/createkb), while
 * the greeting and system instruction are editable by trainers (editcontent).
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ragflowtutor_edit_form extends block_edit_form {
    /** @var string Sentinel value for the "create a new knowledge base" option in the assistant select. */
    const NEWKB = '__new__';

    /**
     * Add the block-specific settings.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform): void {
        $ctx = $this->block->context;
        $isadmin = is_siteadmin();
        $cancreate = $isadmin || has_capability('block/ragflowtutor:createkb', $ctx);
        $cankb = $isadmin || $cancreate || has_capability('block/ragflowtutor:editkb', $ctx);
        $cancontent = $isadmin || has_capability('block/ragflowtutor:editcontent', $ctx);
        $config = $this->block->config ?? new stdClass();
        $p = 'aiprovider_ragflow';
        // Once the block is bound to a knowledge base / assistant, that binding and the document source are
        // fixed (a change would silently break retrieval), so both are shown read-only on edit.
        $configured = \block_ragflowtutor\manager::locked_after_creation($config);

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Knowledge base / assistant selection (admin, editkb or createkb).
        if ($cankb) {
            [$prov, $conf] = self::provider();
            if (!$prov) {
                $mform->addElement(
                    'static',
                    'noprovider',
                    '',
                    get_string('noprovider', 'block_ragflowtutor')
                );
            } else if ($configured) {
                // Locked after creation: the bound knowledge base / assistant (and, for admins, the document
                // source) are shown read-only. instance_config_save keeps the stored binding regardless of
                // what is posted, so no hidden control is needed here.
                $current = trim((string) ($config->chatid ?? ''));
                $mform->addElement(
                    'static',
                    'chatidsummary',
                    get_string('config_chatid', 'block_ragflowtutor'),
                    self::assistant_label($conf, $current)
                );
                $mform->addElement('static', 'lockednotice', '', get_string('config_locked', 'block_ragflowtutor'));
                if ($isadmin) {
                    $ds = (string) ($config->datasource ?? 'wholekb');
                    $mform->addElement(
                        'static',
                        'datasourcesummary',
                        get_string('datasource', $p),
                        get_string('datasource:summary:' . $ds, $p)
                    );
                }
            } else {
                $current = trim((string) ($config->chatid ?? ''));
                // The first option lets an authorised user create a brand-new knowledge base; picking it
                // reveals the name field below (and the KB + assistant are created on save).
                $options = [];
                if ($cancreate) {
                    $options[self::NEWKB] = get_string('newkboption', 'block_ragflowtutor');
                } else {
                    $options[''] = get_string('choosedots');
                }
                if (class_exists('\aiprovider_ragflow\helper')) {
                    foreach (
                        \aiprovider_ragflow\helper::get_chats_detailed(
                            (string) ($conf['baseurl'] ?? ''),
                            (string) ($conf['apikey'] ?? '')
                        ) as $id => $chat
                    ) {
                        $options[$id] = ($chat->kb > 0)
                            ? get_string('chatkblabel', $p, (object) ['name' => $chat->name, 'count' => $chat->kb])
                            : get_string('chatnokblabel', $p, $chat->name);
                    }
                }
                if ($current !== '' && !isset($options[$current])) {
                    $status = \aiprovider_ragflow\local\health\checker::instance()->check_assistant($current);
                    $options[$current] = \aiprovider_ragflow\local\health\checker::stale_option_label($status);
                }
                $mform->addElement(
                    'select',
                    'config_chatid',
                    get_string('config_chatid', 'block_ragflowtutor'),
                    $options
                );
                $mform->setDefault('config_chatid', $current !== '' ? $current : array_key_first($options));
                $mform->addHelpButton('config_chatid', 'config_chatid', 'block_ragflowtutor');

                // Records the auto-created KB id for later lazy-binding to the assistant.
                $mform->addElement('hidden', 'config_kbid', trim((string) ($config->kbid ?? '')));
                $mform->setType('config_kbid', PARAM_ALPHANUMEXT);

                // Name for a brand-new knowledge base; shown only when "create new" is selected. It is a
                // config_ field so it reaches instance_config_save (which creates the KB but never stores
                // the name itself).
                if ($cancreate) {
                    $mform->addElement(
                        'text',
                        'config_newkbname',
                        get_string('newkbname', 'block_ragflowtutor'),
                        ['size' => 40]
                    );
                    $mform->setType('config_newkbname', PARAM_TEXT);
                    $mform->addHelpButton('config_newkbname', 'newkbname', 'block_ragflowtutor');
                    $mform->hideIf('config_newkbname', 'config_chatid', 'neq', self::NEWKB);
                }
            }
        }

        // Greeting + system instruction (trainers via editcontent).
        if ($cancontent) {
            $mform->addElement(
                'text',
                'config_greeting',
                get_string('config_greeting', 'block_ragflowtutor'),
                ['size' => 50]
            );
            $mform->setType('config_greeting', PARAM_TEXT);
            $mform->addHelpButton('config_greeting', 'config_greeting', 'block_ragflowtutor');

            $mform->addElement(
                'textarea',
                'config_systeminstruction',
                get_string('config_systeminstruction', 'block_ragflowtutor'),
                ['rows' => 4, 'cols' => 50]
            );
            $mform->setType('config_systeminstruction', PARAM_TEXT);
            $mform->setDefault('config_systeminstruction', get_string('config_systeminstructiondefault', 'block_ragflowtutor'));
            $mform->addHelpButton('config_systeminstruction', 'config_systeminstruction', 'block_ragflowtutor');
        }

        // Document source + citation settings (site admin only). The document source is fixed at creation;
        // once the block is bound it is shown read-only above (next to the knowledge base / assistant), so
        // only the not-yet-configured document-source controls live here.
        if ($isadmin) {
            if (!$configured) {
                // Document source is chosen via two clearer, context-dependent controls:
                // - creating a NEW knowledge base -> "manage files from this block" (block owns the KB) vs
                // connect-only (documents are managed in RAGflow);
                // - connecting to an EXISTING knowledge base -> the metadata filter (none / Moodle Connector /
                // external sharing).
                // New knowledge base: manage its files from this block, or connect only.
                $mform->addElement(
                    'advcheckbox',
                    'config_managefiles',
                    get_string('config_managefiles', 'block_ragflowtutor')
                );
                $mform->setType('config_managefiles', PARAM_INT);
                $mform->setDefault('config_managefiles', 1);
                $mform->addHelpButton('config_managefiles', 'config_managefiles', 'block_ragflowtutor');
                $mform->hideIf('config_managefiles', 'config_chatid', 'neq', self::NEWKB);

                // Existing knowledge base: metadata filtering.
                $mform->addElement('select', 'config_metadatafilter', get_string('metadatafilter', $p), [
                    'none' => get_string('metadatafilter:none', $p),
                    'thismoodle' => get_string('metadatafilter:thismoodle', $p),
                    'external' => get_string('metadatafilter:external', $p),
                ]);
                $mform->setType('config_metadatafilter', PARAM_ALPHA);
                $mform->setDefault('config_metadatafilter', 'none');
                $mform->addHelpButton('config_metadatafilter', 'metadatafilter', $p);
                $mform->hideIf('config_metadatafilter', 'config_chatid', 'eq', self::NEWKB);

                // Course metadata field: only for the Moodle Connector filter on an existing KB.
                $mform->addElement(
                    'text',
                    'config_coursemetadatafield',
                    get_string('coursemetadatafield', $p),
                    ['size' => 30]
                );
                $mform->setType('config_coursemetadatafield', PARAM_ALPHANUMEXT);
                $mform->setDefault('config_coursemetadatafield', 'course_id');
                $mform->addHelpButton('config_coursemetadatafield', 'coursemetadatafield', $p);
                $mform->hideIf('config_coursemetadatafield', 'config_chatid', 'eq', self::NEWKB);
                $mform->hideIf('config_coursemetadatafield', 'config_metadatafilter', 'neq', 'thismoodle');
            }

            $mform->addElement('advcheckbox', 'config_includesources', get_string('includesources', $p));
            $mform->setType('config_includesources', PARAM_INT);
            $mform->addHelpButton('config_includesources', 'includesources', $p);

            $mform->addElement('advcheckbox', 'config_serveviaproxy', get_string('serveviaproxy', $p));
            $mform->setType('config_serveviaproxy', PARAM_INT);
            $mform->addHelpButton('config_serveviaproxy', 'serveviaproxy', $p);
            $mform->hideIf('config_serveviaproxy', 'config_includesources', 'eq', 0);

            $mform->addElement(
                'textarea',
                'config_extraparams',
                get_string('extraparams', $p),
                ['rows' => 3, 'cols' => 50]
            );
            $mform->setType('config_extraparams', PARAM_TEXT);
            $mform->addHelpButton('config_extraparams', 'extraparams', $p);
        }
    }

    /**
     * The read-only label for the bound knowledge base / assistant: its name (and knowledge-base document
     * count), or the suite's shared reference-checker "stale" label when it can no longer be resolved (the
     * assistant was deleted, or RAGflow is unreachable).
     *
     * @param array $conf The provider config (baseurl / apikey).
     * @param string $chatid
     * @return string
     */
    private static function assistant_label(array $conf, string $chatid): string {
        $p = 'aiprovider_ragflow';
        if ($chatid === '') {
            return get_string('choosedots');
        }
        $base = (string) ($conf['baseurl'] ?? '');
        $key = (string) ($conf['apikey'] ?? '');
        if ($base !== '' && $key !== '' && class_exists('\aiprovider_ragflow\helper')) {
            $chats = \aiprovider_ragflow\helper::get_chats_detailed($base, $key);
            if (isset($chats[$chatid])) {
                $chat = $chats[$chatid];
                return ($chat->kb > 0)
                    ? get_string('chatkblabel', $p, (object) ['name' => $chat->name, 'count' => $chat->kb])
                    : get_string('chatnokblabel', $p, $chat->name);
            }
        }
        // Not in the live list (or RAGflow not configured): the binding is still fixed, so show a neutral
        // read-only label and point to the block's own status panel — no blocking health probe on render.
        return get_string('assistantunavailable', 'block_ragflowtutor');
    }

    /**
     * Validate: reject invalid extra-params JSON.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['config_extraparams'])) {
            json_decode($data['config_extraparams']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['config_extraparams'] = get_string('invalidjson', 'aiprovider_ragflow');
            }
        }
        // Creating a new knowledge base: require a name and ensure it is not already taken (RAGflow does
        // not enforce unique names). The KB + assistant are created on save in the block class.
        if (($data['config_chatid'] ?? '') === self::NEWKB) {
            $name = trim((string) ($data['config_newkbname'] ?? ''));
            if ($name === '') {
                $errors['config_newkbname'] = get_string('createkb:emptyname', 'aiprovider_ragflow');
            } else {
                [$prov, $conf] = self::provider();
                if ($prov && class_exists('\aiprovider_ragflow\helper')) {
                    $base = (string) ($conf['baseurl'] ?? '');
                    $key = (string) ($conf['apikey'] ?? '');
                    if (
                        \aiprovider_ragflow\helper::dataset_name_exists($base, $key, $name)
                            || \aiprovider_ragflow\helper::chat_name_exists($base, $key, $name)
                    ) {
                        $errors['config_newkbname'] = get_string('createkb:nameexists', 'aiprovider_ragflow', s($name));
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * The enabled RAGflow provider instance and its decoded config, or [null, []].
     *
     * @return array [stdClass|null, array]
     */
    protected static function provider(): array {
        global $DB;
        $prov = $DB->get_record_select(
            'ai_providers',
            'provider = :p AND enabled = 1',
            ['p' => 'aiprovider_ragflow\\provider'],
            '*',
            IGNORE_MULTIPLE
        );
        if (!$prov) {
            return [null, []];
        }
        return [$prov, json_decode($prov->config, true) ?: []];
    }
}
