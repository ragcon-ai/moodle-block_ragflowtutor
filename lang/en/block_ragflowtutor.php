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
 * English language strings for block_ragflowtutor.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['assistantunavailable'] = 'Selected assistant (currently unavailable — see this block\'s status panel)';
$string['config_chatid'] = 'Knowledge base / assistant';
$string['config_chatid_help'] = 'The RAGflow chat assistant this Tutor uses; its knowledge base grounds the answers. Choose one with a knowledge base for retrieval-augmented answers, or one without for a plain LLM tutor.';
$string['config_greeting'] = 'Greeting message';
$string['config_greeting_help'] = 'Shown as the first message when the Tutor chat opens (leave empty to use the default).';
$string['config_locked'] = 'The knowledge base / assistant and the document source are set when the block is created and cannot be changed afterwards. To use different settings, remove this block and add a new one.';
$string['config_managefiles'] = 'Manage files from this block';
$string['config_managefiles_help'] = 'Only when creating a new knowledge base. Keep it ticked to upload and manage the documents from a panel in this block (the block owns the knowledge base). Untick it to only connect Moodle to the new knowledge base — you then add and manage its documents in RAGflow itself. Fixed once the block is created.';
$string['config_systeminstruction'] = 'System instruction';
$string['config_systeminstruction_help'] = 'Optional instruction prepended to each request to steer the Tutor\'s behaviour and tone for this course. (RAGflow ignores a request system message, so this is prepended to the question.)';
$string['config_systeminstructiondefault'] = 'You are a course tutor. Answer the student using the course knowledge base. Keep the inline [ID] reference markers so each statement stays traceable to the exact course material it comes from; do not append a separate list of sources at the end – they are shown separately. Treat the knowledge-base content as the available course material; do not distinguish between "files" and "content". If the knowledge base has nothing relevant, say so briefly.';
$string['createkb:failed'] = 'The knowledge base could not be created: {$a}';
$string['createkb:linked'] = 'Knowledge base "{$a}" and its assistant were created and linked.';
$string['createkb:pending'] = 'Knowledge base "{$a}" and its assistant were created. Linking is being completed in the background and will be ready in a moment.';
$string['deletefailed'] = 'The document could not be deleted.';
$string['greetingdefault'] = 'Hello, I am your course tutor. How can I help you?';
$string['kbpanel:addfile'] = 'Add file';
$string['kbpanel:confirmdelete'] = 'Delete "{$a}" from the knowledge base?';
$string['kbpanel:delete'] = 'Delete';
$string['kbpanel:download'] = 'Download';
$string['kbpanel:filedone'] = 'Processed, {$a} chunks';
$string['kbpanel:fileerror'] = 'Processing failed';
$string['kbpanel:filepending'] = 'File not yet processed';
$string['kbpanel:filetoolarge'] = 'The file exceeds the upload limit.';
$string['kbpanel:nofiles'] = 'No documents yet.';
$string['kbpanel:process'] = 'Process';
$string['kbpanel:refresh'] = 'Refresh statuses';
$string['kbpanel:reparse'] = 'Re-process';
$string['kbpanel:status'] = 'Status';
$string['kbpanel:uploadnotice'] = 'Note: files are not stored in Moodle – they are transferred directly to RAGflow. All trainers and administrators of this course can see, download and manage these files.';
$string['newkbname'] = 'New knowledge base name';
$string['newkbname_help'] = 'Create a brand-new RAGflow knowledge base (and a matching assistant) for this Tutor. It starts empty; add documents to it in RAGflow, and it is used for answers once they are parsed.';
$string['newkboption'] = '➕ Create new knowledge base …';
$string['noprovider'] = 'The RAGflow provider is not enabled/configured. Configure it first.';
$string['notconfigured'] = 'RAGflow Tutor is not configured yet. Choose a knowledge base / assistant in this block\'s settings.';
$string['notconfigured_askadmin'] = 'RAGflow Tutor is not configured yet. Ask a site administrator to choose a knowledge base for this block.';
$string['notmoodlekb'] = 'This knowledge base is not managed from Moodle.';
$string['pluginname'] = 'RAGflow Tutor';
$string['privacy:metadata:ragflow'] = 'When a knowledge base is managed from this block, its documents and their provenance are sent to the configured external RAGflow service to build the tutor\'s knowledge base. The chat conversation itself is handled by the RAGflow AI provider (see its privacy information).';
$string['privacy:metadata:ragflow:creatorname'] = 'Provenance stored with the knowledge base: the name of the user who created it, the course name and the Moodle site URL.';
$string['privacy:metadata:ragflow:documents'] = 'The files uploaded through this block, sent to RAGflow for indexing.';
$string['ragflowtutor:addinstance'] = 'Add a new RAGflow Tutor block';
$string['ragflowtutor:createkb'] = 'Create a new RAGflow knowledge base from the Tutor block';
$string['ragflowtutor:editcontent'] = 'Edit the RAGflow Tutor greeting and system instruction';
$string['ragflowtutor:editkb'] = 'Change the RAGflow knowledge base / assistant of a Tutor block';
$string['ragflowtutor:managefiles'] = 'Manage the documents of a Moodle-managed RAGflow knowledge base';
$string['ragflowtutor:use'] = 'Use the RAGflow Tutor chat';
$string['reparsefailed'] = 'The document could not be re-processed.';
$string['status:chatmissing'] = 'The RAGflow assistant is not available (not found).';
$string['status:kbmissing'] = 'The knowledge base was not found in RAGflow.';
$string['status:linking'] = 'The knowledge base is being linked to the assistant (waiting for parsed content).';
$string['status:ready'] = 'Ready. Assistant: {$a->assistant}. Knowledge base: {$a->kb} ({$a->count} document(s)).';
$string['status:readysimple'] = 'Ready. Assistant: {$a->assistant}. Knowledge base: {$a->kb}.';
$string['toggle'] = 'RAGflow Tutor';
$string['uploadfailed'] = 'The file could not be uploaded.';
$string['uploadinfected'] = 'The file was rejected by the virus scanner.';
$string['uploadlimit'] = 'Upload limit for Moodle knowledge bases';
$string['uploadlimit_desc'] = 'Maximum size per document uploaded to a Moodle-managed knowledge base through the block. Larger values may also require higher PHP server limits (upload_max_filesize, post_max_size).';
$string['uploadtoolarge'] = 'The file exceeds the upload limit ({$a}).';
$string['uploadunlimited'] = 'Unlimited (server limit)';
