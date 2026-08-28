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

namespace block_ragflowtutor\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy provider. The block stores no personal data in Moodle itself, but when a knowledge base is
 * managed from the block it sends the uploaded documents and their provenance (the creating user's name,
 * the course name and the Moodle site URL) to the configured external RAGflow service — that transfer is
 * declared here. The chat conversation is handled by the RAGflow AI provider (aiprovider_ragflow), which
 * declares its own privacy metadata.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * Describe the data this block transmits to the external RAGflow service.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('ragflow', [
            'documents' => 'privacy:metadata:ragflow:documents',
            'creatorname' => 'privacy:metadata:ragflow:creatorname',
        ], 'privacy:metadata:ragflow');
        return $collection;
    }
}
