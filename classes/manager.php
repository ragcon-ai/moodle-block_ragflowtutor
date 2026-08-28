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

namespace block_ragflowtutor;

/**
 * Knowledge-base panel + file management for a Tutor block instance: resolves the block's RAGflow config,
 * enforces the manage-files capability, and talks to RAGflow via the provider helper.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * The enabled RAGflow provider: [providerid, baseurl, apikey], or [0, '', ''] if none.
     *
     * @return array
     */
    public static function provider(): array {
        global $DB;
        $prov = $DB->get_record_select(
            'ai_providers',
            'provider = :p AND enabled = 1',
            ['p' => 'aiprovider_ragflow\\provider'],
            '*',
            IGNORE_MULTIPLE
        );
        if (!$prov) {
            return [0, '', ''];
        }
        $conf = json_decode($prov->config, true) ?: [];
        return [(int) $prov->id, rtrim((string) ($conf['baseurl'] ?? ''), '/'), (string) ($conf['apikey'] ?? '')];
    }

    /**
     * Resolve a Tutor block instance, validate access and require the manage-files capability. Returns
     * [\context $context, \stdClass $config].
     *
     * @param int $blockinstanceid
     * @return array
     */
    public static function require_manage(int $blockinstanceid): array {
        global $DB;
        $record = $DB->get_record('block_instances', ['id' => $blockinstanceid, 'blockname' => 'ragflowtutor']);
        if (!$record) {
            throw new \invalid_parameter_exception('unknown block instance.');
        }
        $context = \context_block::instance($record->id);
        require_capability('block/ragflowtutor:managefiles', $context);
        // Block config is a Moodle-written stdClass; restrict unserialize to stdClass so a tampered
        // configdata cannot trigger PHP object-injection gadget chains.
        $config = ($record->configdata !== '')
            ? unserialize(base64_decode($record->configdata), ['allowed_classes' => ['stdClass']])
            : new \stdClass();
        if (!is_object($config)) {
            $config = new \stdClass();
        }
        return [$context, $config];
    }

    /**
     * Build the KB/chat status + file list payload for a block instance.
     *
     * @param int $blockinstanceid
     * @return array
     */
    public static function status(int $blockinstanceid): array {
        [$context, $config] = self::require_manage($blockinstanceid);
        unset($context);
        [$providerid, $base, $key] = self::provider();

        $chatid = trim((string) ($config->chatid ?? ''));
        $kbid = trim((string) ($config->kbid ?? ''));
        $seeddocid = trim((string) ($config->seeddocid ?? ''));
        // File management is only offered for a "This course" knowledge base (managed from Moodle).
        $ismoodlekb = ($kbid !== '' && (string) ($config->datasource ?? '') === 'thiscourse');

        $result = [
            'ismoodlekb' => $ismoodlekb,
            'kbname' => '',
            'kbstatus' => 'red',
            'chatname' => '',
            'chatstatus' => 'red',
            'files' => [],
        ];
        if ($base === '' || $key === '' || $chatid === '') {
            return $result;
        }

        // Chat / assistant.
        $chats = \aiprovider_ragflow\helper::get_chats_detailed($base, $key);
        if (isset($chats[$chatid])) {
            $result['chatname'] = $chats[$chatid]->name;
            $result['chatstatus'] = 'green';
        }

        // Knowledge base.
        $dsmap = \aiprovider_ragflow\helper::datasets_cached($providerid, $base, $key);
        $bound = \aiprovider_ragflow\helper::get_chat_datasets($base, $key, $chatid);
        if ($ismoodlekb) {
            if (isset($dsmap[$kbid])) {
                $result['kbname'] = $dsmap[$kbid];
                $result['kbstatus'] = in_array($kbid, $bound, true) ? 'green' : 'yellow';
            } else {
                $result['kbstatus'] = 'red';
            }
        } else {
            $result['kbstatus'] = !empty($bound) ? 'green' : 'red';
            $result['kbname'] = (!empty($bound) && isset($dsmap[$bound[0]])) ? $dsmap[$bound[0]] : '';
        }

        // Documents (Moodle KB only), excluding the internal seed file. The download URL is minted on click
        // (block_ragflowtutor_download_url) so the signed token lives only seconds – files carry just the id.
        if ($ismoodlekb && isset($dsmap[$kbid])) {
            foreach (\aiprovider_ragflow\helper::list_documents($base, $key, $kbid) as $doc) {
                if ($doc->id === $seeddocid) {
                    continue;
                }
                $result['files'][] = [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'status' => self::run_to_status($doc->run, $doc->chunkcount),
                    'processing' => (\core_text::strtoupper(trim($doc->run)) === 'RUNNING'),
                    'chunkcount' => $doc->chunkcount,
                    'message' => $doc->message,
                ];
            }
        }

        $result['statusmessage'] = self::status_message($result, $ismoodlekb);
        return $result;
    }

    /**
     * A human-readable status summary for the KB/assistant status dot hover, derived from what RAGflow
     * returned (assistant / knowledge-base names, document count, or the reason it is not ready).
     *
     * @param array $r The partial status result.
     * @param bool $ismoodlekb
     * @return string
     */
    protected static function status_message(array $r, bool $ismoodlekb): string {
        if ($r['chatstatus'] === 'red') {
            return get_string('status:chatmissing', 'block_ragflowtutor');
        }
        if ($ismoodlekb && $r['kbstatus'] === 'red') {
            return get_string('status:kbmissing', 'block_ragflowtutor');
        }
        if ($ismoodlekb && $r['kbstatus'] === 'yellow') {
            return get_string('status:linking', 'block_ragflowtutor');
        }
        $a = (object) [
            'assistant' => ($r['chatname'] !== '') ? $r['chatname'] : '—',
            'kb' => ($r['kbname'] !== '') ? $r['kbname'] : '—',
            'count' => count($r['files']),
        ];
        return $ismoodlekb
            ? get_string('status:ready', 'block_ragflowtutor', $a)
            : get_string('status:readysimple', 'block_ragflowtutor', $a);
    }

    /**
     * Upload a file into the block's Moodle knowledge base and start parsing.
     *
     * @param int $blockinstanceid
     * @param string $tmppath Local path of the uploaded file.
     * @param string $filename Original file name.
     * @return array {ok: bool, error: string, docid: string}
     */
    public static function upload(int $blockinstanceid, string $tmppath, string $filename): array {
        [$context, $config] = self::require_manage($blockinstanceid);
        unset($context);
        $kbid = trim((string) ($config->kbid ?? ''));
        if (!self::manageable($config)) {
            return ['ok' => false, 'error' => get_string('notmoodlekb', 'block_ragflowtutor'), 'docid' => ''];
        }
        [$providerid, $base, $key] = self::provider();
        unset($providerid);
        if ($base === '' || $key === '') {
            return ['ok' => false, 'error' => get_string('noprovider', 'block_ragflowtutor'), 'docid' => ''];
        }
        // Moodle antivirus scan (no-op when no antivirus plugin is configured; rejects infected files).
        try {
            \core\antivirus\manager::scan_file($tmppath, $filename, false);
        } catch (\core\antivirus\scanner_exception $e) {
            return ['ok' => false, 'error' => get_string('uploadinfected', 'block_ragflowtutor'), 'docid' => ''];
        }
        $content = @file_get_contents($tmppath);
        if ($content === false) {
            return ['ok' => false, 'error' => get_string('uploadfailed', 'block_ragflowtutor'), 'docid' => ''];
        }
        $docid = \aiprovider_ragflow\helper::upload_text_document($base, $key, $kbid, clean_filename($filename), $content);
        if ($docid === '') {
            return ['ok' => false, 'error' => get_string('uploadfailed', 'block_ragflowtutor'), 'docid' => ''];
        }
        \aiprovider_ragflow\helper::parse_documents($base, $key, $kbid, [$docid]);
        return ['ok' => true, 'error' => '', 'docid' => $docid];
    }

    /**
     * Delete a document from the block's Moodle knowledge base.
     *
     * @param int $blockinstanceid
     * @param string $docid
     * @return array {ok: bool, error: string}
     */
    public static function delete(int $blockinstanceid, string $docid): array {
        [$context, $config] = self::require_manage($blockinstanceid);
        unset($context);
        $kbid = trim((string) ($config->kbid ?? ''));
        $seeddocid = trim((string) ($config->seeddocid ?? ''));
        if (!self::manageable($config)) {
            return ['ok' => false, 'error' => get_string('notmoodlekb', 'block_ragflowtutor')];
        }
        if ($docid === $seeddocid || trim($docid) === '') {
            // Never expose/delete the internal seed marker.
            return ['ok' => false, 'error' => get_string('deletefailed', 'block_ragflowtutor')];
        }
        [$providerid, $base, $key] = self::provider();
        unset($providerid);
        if (!self::owns_document($base, $key, $kbid, $docid)) {
            // The client-supplied document id must belong to this block's own knowledge base.
            return ['ok' => false, 'error' => get_string('deletefailed', 'block_ragflowtutor')];
        }
        $ok = \aiprovider_ragflow\helper::delete_document($base, $key, $kbid, $docid);
        return ['ok' => $ok, 'error' => $ok ? '' : get_string('deletefailed', 'block_ragflowtutor')];
    }

    /**
     * Re-trigger parsing of a document in the block's Moodle knowledge base.
     *
     * @param int $blockinstanceid
     * @param string $docid
     * @return array {ok: bool, error: string}
     */
    public static function reparse(int $blockinstanceid, string $docid): array {
        [$context, $config] = self::require_manage($blockinstanceid);
        unset($context);
        $kbid = trim((string) ($config->kbid ?? ''));
        $seeddocid = trim((string) ($config->seeddocid ?? ''));
        if (!self::manageable($config)) {
            return ['ok' => false, 'error' => get_string('notmoodlekb', 'block_ragflowtutor')];
        }
        if ($docid === $seeddocid || trim($docid) === '') {
            return ['ok' => false, 'error' => get_string('reparsefailed', 'block_ragflowtutor')];
        }
        [$providerid, $base, $key] = self::provider();
        unset($providerid);
        if (!self::owns_document($base, $key, $kbid, $docid)) {
            return ['ok' => false, 'error' => get_string('reparsefailed', 'block_ragflowtutor')];
        }
        $ok = \aiprovider_ragflow\helper::parse_documents($base, $key, $kbid, [$docid]);
        return ['ok' => $ok, 'error' => $ok ? '' : get_string('reparsefailed', 'block_ragflowtutor')];
    }

    /**
     * Mint a short-lived signed proxy download URL for a document in the block's Moodle knowledge base, at
     * click time. Authorised by the managefiles capability (require_manage) and by the document belonging to
     * the block's own knowledge base – so the signed token only needs a very short lifetime.
     *
     * @param int $blockinstanceid
     * @param string $docid
     * @return array {url: string}
     */
    public static function download_url(int $blockinstanceid, string $docid): array {
        global $USER;
        [$context, $config] = self::require_manage($blockinstanceid);
        unset($context);
        $kbid = trim((string) ($config->kbid ?? ''));
        $seeddocid = trim((string) ($config->seeddocid ?? ''));
        if (!self::manageable($config) || trim($docid) === '' || $docid === $seeddocid) {
            return ['url' => ''];
        }
        [$providerid, $base, $key] = self::provider();
        if (!self::owns_document($base, $key, $kbid, $docid)) {
            return ['url' => ''];
        }
        return ['url' => \aiprovider_ragflow\helper::proxy_url((int) $providerid, $kbid, $docid, (int) $USER->id)];
    }

    /**
     * Whether a document id belongs to the given knowledge base (do not trust a client-supplied id / the
     * backend to scope the action to the dataset).
     *
     * @param string $base
     * @param string $key
     * @param string $kbid
     * @param string $docid
     * @return bool
     */
    protected static function owns_document(string $base, string $key, string $kbid, string $docid): bool {
        foreach (\aiprovider_ragflow\helper::list_documents($base, $key, $kbid) as $doc) {
            if ($doc->id === $docid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether a block config allows file management: a Moodle-managed "This course" knowledge base.
     *
     * @param \stdClass $config
     * @return bool
     */
    protected static function manageable(\stdClass $config): bool {
        return trim((string) ($config->kbid ?? '')) !== ''
            && (string) ($config->datasource ?? '') === 'thiscourse';
    }

    /**
     * Whether the block is already bound to a knowledge base / assistant. Once it is, the knowledge base /
     * assistant and the document source are fixed: changing them would silently break retrieval, so the edit
     * form shows them read-only and instance_config_save keeps the stored binding.
     *
     * @param \stdClass $config
     * @return bool
     */
    public static function locked_after_creation(\stdClass $config): bool {
        return trim((string) ($config->chatid ?? '')) !== ''
            || trim((string) ($config->kbid ?? '')) !== '';
    }

    /**
     * The configured upload limit in bytes (0 = unlimited apart from server limits).
     *
     * @return int
     */
    public static function upload_limit_bytes(): int {
        $mb = (int) get_config('block_ragflowtutor', 'uploadlimit');
        return ($mb > 0) ? $mb * 1024 * 1024 : 0;
    }

    /**
     * The effective per-file upload ceiling in bytes: the smaller of the block setting and Moodle's own
     * configured maximum upload size (which already reflects the PHP server limits). 0 = no fixed ceiling.
     *
     * @return int
     */
    public static function effective_upload_limit_bytes(): int {
        global $CFG;
        $block = self::upload_limit_bytes();
        $moodle = (int) get_max_upload_file_size($CFG->maxbytes);
        if ($block <= 0) {
            return ($moodle > 0) ? $moodle : 0;
        }
        if ($moodle <= 0) {
            return $block;
        }
        return min($block, $moodle);
    }

    /**
     * Map a RAGflow document parse state to a traffic-light status.
     *
     * @param string $run
     * @param int $chunkcount
     * @return string 'green'|'yellow'|'red'
     */
    protected static function run_to_status(string $run, int $chunkcount): string {
        $run = \core_text::strtoupper(trim($run));
        if ($run === 'DONE' || $chunkcount > 0) {
            return 'green';
        }
        if ($run === 'FAIL' || $run === 'CANCEL') {
            return 'red';
        }
        return 'yellow';
    }
}
