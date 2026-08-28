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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * AJAX: knowledge-base / assistant status + document list for a Tutor block instance (manage-files only).
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_status extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blockinstanceid' => new external_value(PARAM_INT, 'The Tutor block instance id.'),
        ]);
    }

    /**
     * Return the status payload.
     *
     * @param int $blockinstanceid
     * @return array
     */
    public static function execute(int $blockinstanceid): array {
        ['blockinstanceid' => $blockinstanceid] = self::validate_parameters(
            self::execute_parameters(),
            ['blockinstanceid' => $blockinstanceid]
        );
        $context = \context_block::instance($blockinstanceid, IGNORE_MISSING);
        if (!$context) {
            throw new \invalid_parameter_exception('unknown block instance.');
        }
        self::validate_context($context);
        return \block_ragflowtutor\manager::status($blockinstanceid);
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ismoodlekb' => new external_value(PARAM_BOOL, 'Whether this KB is managed from Moodle.'),
            'kbname' => new external_value(PARAM_RAW, 'Knowledge base name.'),
            'kbstatus' => new external_value(PARAM_ALPHA, 'red | yellow | green.'),
            'chatname' => new external_value(PARAM_RAW, 'Assistant name.'),
            'chatstatus' => new external_value(PARAM_ALPHA, 'red | yellow | green.'),
            'statusmessage' => new external_value(PARAM_RAW, 'Status summary for the status-dot hover.', VALUE_DEFAULT, ''),
            'files' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_RAW, 'Document id.'),
                'name' => new external_value(PARAM_RAW, 'Document name.'),
                'status' => new external_value(PARAM_ALPHA, 'red | yellow | green.'),
                'processing' => new external_value(PARAM_BOOL, 'Whether a parse is currently running.', VALUE_DEFAULT, false),
                'chunkcount' => new external_value(PARAM_INT, 'Number of parsed chunks.', VALUE_DEFAULT, 0),
                'message' => new external_value(PARAM_RAW, 'RAGflow status/error message (for the tooltip).', VALUE_DEFAULT, ''),
            ]), 'Documents in the KB (seed excluded).', VALUE_DEFAULT, []),
        ]);
    }
}
