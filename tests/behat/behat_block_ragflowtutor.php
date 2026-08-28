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

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat step definitions for the RAGflow Tutor block.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_ragflowtutor extends behat_base {
    /**
     * Add a Tutor block to a course that is already bound to a knowledge base / assistant, so its edit form
     * shows the locked (read-only) presentation without needing a live RAGflow.
     *
     * @Given /^a configured RAGflow Tutor block exists in course "(?P<shortname_string>(?:[^"]|\\")*)"$/
     * @param string $shortname
     */
    public function a_configured_ragflow_tutor_block_exists_in_course(string $shortname): void {
        global $DB;
        // The edit form's locked view needs an enabled RAGflow AI-provider instance (otherwise it shows the
        // "no provider" notice). An empty base URL / key keeps it offline without any HTTP call: name
        // resolution then falls back to the shared reference checker's read-only "stale" label.
        if (!$DB->record_exists('ai_providers', ['provider' => 'aiprovider_ragflow\\provider', 'enabled' => 1])) {
            $now = time();
            $DB->insert_record('ai_providers', (object) [
                'name' => 'RAGflow (behat)',
                'provider' => 'aiprovider_ragflow\\provider',
                'enabled' => 1,
                'config' => json_encode(['baseurl' => '', 'apikey' => '']),
                'usermodified' => get_admin()->id,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
        $context = \context_course::instance($courseid);
        $config = (object) ['chatid' => 'chat-behat', 'kbid' => '', 'datasource' => 'wholekb'];
        $now = time();
        $id = $DB->insert_record('block_instances', (object) [
            'blockname' => 'ragflowtutor',
            'parentcontextid' => $context->id,
            'showinsubcontexts' => 0,
            'requiredbytheme' => 0,
            'pagetypepattern' => 'course-view-*',
            'subpagepattern' => null,
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize($config)),
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        \context_block::instance($id);
    }
}
