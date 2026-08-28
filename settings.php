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
 * Global settings for block_ragflowtutor.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Upload limit for documents added to a Moodle-managed knowledge base through the block. Stored in MB;
    // 0 = unlimited (still bounded by the server's PHP upload limits and RAGflow).
    $settings->add(new admin_setting_configselect(
        'block_ragflowtutor/uploadlimit',
        get_string('uploadlimit', 'block_ragflowtutor'),
        get_string('uploadlimit_desc', 'block_ragflowtutor'),
        50,
        [
            50 => '50 MB',
            500 => '500 MB',
            0 => get_string('uploadunlimited', 'block_ragflowtutor'),
        ]
    ));

    // Write a slim usage/error entry to the Moodle standard log per request (opt-in; see help).
    $settings->add(new admin_setting_configcheckbox(
        'block_ragflowtutor/logtomoodle',
        get_string('logtomoodle', 'aiprovider_ragflow'),
        get_string('logtomoodle_desc', 'aiprovider_ragflow'),
        0
    ));
}
