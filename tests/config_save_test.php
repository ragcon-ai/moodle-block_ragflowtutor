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
 * The block's instance_config_save keeps a configured knowledge base / assistant binding, so editing a
 * bound block can never switch the assistant or drop a block-owned knowledge base.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config_save_test extends \advanced_testcase {
    /**
     * Once a block is bound to a KB/assistant, saving its config keeps chatid/kbid/seeddocid/datasource,
     * even when the posted data tries to change the assistant.
     *
     * @covers \block_ragflowtutor::instance_config_save
     * @return void
     */
    public function test_binding_is_kept_on_edit(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $record = $this->getDataGenerator()->create_block('ragflowtutor', (object) [
            'parentcontextid' => $context->id,
            'pagetypepattern' => 'course-view-*',
            'defaultregion' => 'side-pre',
        ]);
        $block = block_instance('ragflowtutor', $record);

        // A configured, block-owned ("This course") knowledge base.
        $block->config = (object) [
            'chatid' => 'chat-1',
            'kbid' => 'kb-1',
            'seeddocid' => 'seed-1',
            'datasource' => 'thiscourse',
            'coursemetadatafield' => 'course_id',
        ];

        // A save that (before the fix) would switch the assistant and drop the KB binding.
        $block->instance_config_save((object) [
            'chatid' => 'chat-OTHER',
            'greeting' => 'Hi',
            'systeminstruction' => '',
            'includesources' => 1,
            'serveviaproxy' => 1,
            'extraparams' => '',
        ]);

        $saved = $DB->get_record('block_instances', ['id' => $record->id], 'configdata');
        $cfg = unserialize(base64_decode($saved->configdata), ['allowed_classes' => ['stdClass']]);

        $this->assertSame('chat-1', $cfg->chatid, 'the assistant binding is kept');
        $this->assertSame('kb-1', $cfg->kbid, 'the block-owned knowledge base id is kept');
        $this->assertSame('seed-1', $cfg->seeddocid, 'the seed document id is kept');
        $this->assertSame('thiscourse', $cfg->datasource, 'the document source is kept');
    }
}
