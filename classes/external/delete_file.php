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

namespace block_ragflowtutor\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * AJAX: delete a document from a Tutor block's Moodle knowledge base (manage-files only).
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_file extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blockinstanceid' => new external_value(PARAM_INT, 'The Tutor block instance id.'),
            'docid' => new external_value(PARAM_RAW, 'The RAGflow document id to delete.'),
        ]);
    }

    /**
     * Delete the document.
     *
     * @param int $blockinstanceid
     * @param string $docid
     * @return array
     */
    public static function execute(int $blockinstanceid, string $docid): array {
        [
            'blockinstanceid' => $blockinstanceid,
            'docid' => $docid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'blockinstanceid' => $blockinstanceid,
            'docid' => $docid,
        ]);
        $context = \context_block::instance($blockinstanceid, IGNORE_MISSING);
        if (!$context) {
            throw new \invalid_parameter_exception('unknown block instance.');
        }
        self::validate_context($context);
        return \block_ragflowtutor\manager::delete($blockinstanceid, $docid);
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Whether the deletion succeeded.'),
            'error' => new external_value(PARAM_TEXT, 'Error message on failure.'),
        ]);
    }
}
