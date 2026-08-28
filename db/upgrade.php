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
 * Upgrade steps for block_ragflowtutor.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Apply the plugin upgrade steps.
 *
 * @param int $oldversion The currently installed version.
 * @return bool
 */
function xmldb_block_ragflowtutor_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026080810) {
        // Knowledge bases created through the block (kbid set) are now the "This course" source; migrate
        // existing instances so file management is offered for them.
        $rs = $DB->get_recordset('block_instances', ['blockname' => 'ragflowtutor']);
        foreach ($rs as $record) {
            if ($record->configdata === '') {
                continue;
            }
            // Restrict unserialize to the expected stdClass (block config) to avoid object injection.
            $config = unserialize(base64_decode($record->configdata), ['allowed_classes' => ['stdClass']]);
            if (!is_object($config)) {
                continue;
            }
            if (trim((string) ($config->kbid ?? '')) !== '' && (string) ($config->datasource ?? '') !== 'external') {
                $config->datasource = 'thiscourse';
                $DB->set_field('block_instances', 'configdata', base64_encode(serialize($config)), ['id' => $record->id]);
            }
        }
        $rs->close();
        upgrade_block_savepoint(true, 2026080810, 'ragflowtutor');
    }

    if ($oldversion < 2026082010) {
        // Tutor sources are now citation-based: they come from the model's own [ID:n] citations. The
        // previous default system instruction told the model NOT to add [ID] references, which suppressed
        // the citations the source list now depends on. Migrate any instance still carrying that exact old
        // default to the current default (which keeps the [ID] markers). Instances an admin has customised
        // are left untouched.
        $olddefault = 'You are a course tutor. Answer the student using the course knowledge base. Do NOT '
            . 'list sources or add [ID] references in your answer text – the sources are shown separately. '
            . 'Treat the knowledge-base content as the available course material; do not distinguish between '
            . '"files" and "content". If the knowledge base has nothing relevant, say so briefly.';
        $newdefault = get_string('config_systeminstructiondefault', 'block_ragflowtutor');
        $rs = $DB->get_recordset('block_instances', ['blockname' => 'ragflowtutor']);
        foreach ($rs as $record) {
            if ($record->configdata === '') {
                continue;
            }
            // Restrict unserialize to the expected stdClass (block config) to avoid object injection.
            $config = unserialize(base64_decode($record->configdata), ['allowed_classes' => ['stdClass']]);
            if (!is_object($config)) {
                continue;
            }
            if (trim((string) ($config->systeminstruction ?? '')) === trim($olddefault)) {
                $config->systeminstruction = $newdefault;
                $DB->set_field('block_instances', 'configdata', base64_encode(serialize($config)), ['id' => $record->id]);
            }
        }
        $rs->close();
        upgrade_block_savepoint(true, 2026082010, 'ragflowtutor');
    }

    if ($oldversion < 2026082011) {
        // Same migration as 2026082010, but matching the old default in every shipped language (the prior
        // step only matched English, so a German instance kept its citation-suppressing default). Match any
        // known old default and replace it with the current default (which keeps the [ID] markers).
        $olddefaults = [
            // English.
            'You are a course tutor. Answer the student using the course knowledge base. Do NOT list '
                . 'sources or add [ID] references in your answer text – the sources are shown separately. '
                . 'Treat the knowledge-base content as the available course material; do not distinguish '
                . 'between "files" and "content". If the knowledge base has nothing relevant, say so briefly.',
            // German.
            'Du bist ein Kurs-Tutor. Beantworte die Frage anhand der Kurs-Wissensbasis. Führe im Antworttext '
                . 'KEINE Quellenliste und keine [ID]-Verweise auf – die Quellen werden separat angezeigt. '
                . 'Behandle den Inhalt der Wissensbasis als das verfügbare Kursmaterial; unterscheide nicht '
                . 'zwischen „Dateien" und „Inhalt". Wenn die Wissensbasis nichts Relevantes enthält, sage '
                . 'das kurz.',
        ];
        $newdefault = get_string('config_systeminstructiondefault', 'block_ragflowtutor');
        $rs = $DB->get_recordset('block_instances', ['blockname' => 'ragflowtutor']);
        foreach ($rs as $record) {
            if ($record->configdata === '') {
                continue;
            }
            // Restrict unserialize to the expected stdClass (block config) to avoid object injection.
            $config = unserialize(base64_decode($record->configdata), ['allowed_classes' => ['stdClass']]);
            if (!is_object($config)) {
                continue;
            }
            if (in_array(trim((string) ($config->systeminstruction ?? '')), array_map('trim', $olddefaults), true)) {
                $config->systeminstruction = $newdefault;
                $DB->set_field('block_instances', 'configdata', base64_encode(serialize($config)), ['id' => $record->id]);
            }
        }
        $rs->close();
        upgrade_block_savepoint(true, 2026082011, 'ragflowtutor');
    }

    return true;
}
