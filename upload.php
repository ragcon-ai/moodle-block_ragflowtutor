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
 * Multipart upload endpoint: streams a document into a Tutor block's Moodle knowledge base and starts
 * parsing. Used by the block's file manager (fetch + FormData, so large files avoid base64 bloat).
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$blockinstanceid = required_param('blockinstanceid', PARAM_INT);

$context = context_block::instance($blockinstanceid, IGNORE_MISSING);
if (!$context) {
    throw new \invalid_parameter_exception('unknown block instance.');
}
$PAGE->set_context($context);

// Capability is enforced again inside manager::upload(); check here too for an early, clean error.
require_capability('block/ragflowtutor:managefiles', $context);

header('Content-Type: application/json; charset=utf-8');

$sendjson = function (array $data) {
    echo json_encode($data);
    die;
};

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    $sendjson(['ok' => false, 'error' => get_string('uploadfailed', 'block_ragflowtutor')]);
}
$file = $_FILES['file'];
if (!empty($file['error']) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    $sendjson(['ok' => false, 'error' => get_string('uploadfailed', 'block_ragflowtutor')]);
}

$limit = \block_ragflowtutor\manager::effective_upload_limit_bytes();
if ($limit > 0 && (int) $file['size'] > $limit) {
    $sendjson(['ok' => false, 'error' => get_string('uploadtoolarge', 'block_ragflowtutor', display_size($limit))]);
}

$result = \block_ragflowtutor\manager::upload($blockinstanceid, $file['tmp_name'], (string) $file['name']);
$sendjson($result);
