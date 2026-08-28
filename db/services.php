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
 * External (AJAX) services for block_ragflowtutor.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_ragflowtutor_get_status' => [
        'classname' => 'block_ragflowtutor\external\get_status',
        'methodname' => 'execute',
        'description' => 'Knowledge-base / assistant status and document list for a Tutor block.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/ragflowtutor:managefiles',
    ],
    'block_ragflowtutor_delete_file' => [
        'classname' => 'block_ragflowtutor\external\delete_file',
        'methodname' => 'execute',
        'description' => 'Delete a document from a Tutor block Moodle knowledge base.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/ragflowtutor:managefiles',
    ],
    'block_ragflowtutor_reparse_file' => [
        'classname' => 'block_ragflowtutor\external\reparse_file',
        'methodname' => 'execute',
        'description' => 'Re-parse a document in a Tutor block Moodle knowledge base.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/ragflowtutor:managefiles',
    ],
    'block_ragflowtutor_download_url' => [
        'classname' => 'block_ragflowtutor\external\download_url',
        'methodname' => 'execute',
        'description' => 'Mint a short-lived signed download URL for a document at click time.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/ragflowtutor:managefiles',
    ],
];
