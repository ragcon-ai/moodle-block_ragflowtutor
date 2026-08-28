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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the Tutor block's file-manager helper logic.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(manager::class)]
final class manager_test extends \advanced_testcase {
    /**
     * Invoke a protected static method of manager via reflection.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    private static function call(string $method, array $args) {
        $m = new \ReflectionMethod(manager::class, $method);
        $m->setAccessible(true);
        return $m->invokeArgs(null, $args);
    }

    /**
     * run_to_status(): DONE or any parsed chunks = green; FAIL/CANCEL = red; otherwise yellow.
     *
     * @return void
     */
    public function test_run_to_status(): void {
        $this->assertSame('green', self::call('run_to_status', ['DONE', 0]));
        $this->assertSame('green', self::call('run_to_status', ['done', 5]));
        $this->assertSame('green', self::call('run_to_status', ['RUNNING', 3]), 'any chunks present = green');
        $this->assertSame('red', self::call('run_to_status', ['FAIL', 0]));
        $this->assertSame('red', self::call('run_to_status', ['CANCEL', 0]));
        $this->assertSame('yellow', self::call('run_to_status', ['UNSTART', 0]));
        $this->assertSame('yellow', self::call('run_to_status', ['RUNNING', 0]));
    }

    /**
     * manageable(): only a Moodle-managed "This course" knowledge base (kbid set + datasource thiscourse)
     * offers file management.
     *
     * @return void
     */
    public function test_manageable(): void {
        $mk = fn(string $ds, string $kbid): \stdClass => (object) ['datasource' => $ds, 'kbid' => $kbid];
        $this->assertTrue(self::call('manageable', [$mk('thiscourse', 'kb1')]));
        $this->assertFalse(self::call('manageable', [$mk('thiscourse', '')]), 'no kb = not manageable');
        $this->assertFalse(self::call('manageable', [$mk('wholekb', 'kb1')]), 'whole KB = not manageable');
        $this->assertFalse(self::call('manageable', [$mk('thismoodle', 'kb1')]));
        $this->assertFalse(self::call('manageable', [$mk('external', 'kb1')]));
    }

    /**
     * locked_after_creation(): the block is locked once it carries a chatid or a kbid; a fresh, unbound
     * block (or empty/whitespace values) is not.
     *
     * @return void
     */
    public function test_locked_after_creation(): void {
        $this->assertFalse(manager::locked_after_creation(new \stdClass()), 'fresh block = not locked');
        $this->assertFalse(manager::locked_after_creation((object) ['chatid' => '', 'kbid' => '']));
        $this->assertFalse(manager::locked_after_creation((object) ['chatid' => '   ']), 'whitespace only');
        $this->assertTrue(manager::locked_after_creation((object) ['chatid' => 'c1']), 'assistant bound');
        $this->assertTrue(manager::locked_after_creation((object) ['kbid' => 'k1']), 'own KB bound');
        $this->assertTrue(manager::locked_after_creation((object) ['chatid' => 'c1', 'kbid' => 'k1']));
    }

    /**
     * upload_limit_bytes(): the stored MB setting is converted to bytes; 0 means unlimited (0).
     *
     * @return void
     */
    public function test_upload_limit_bytes(): void {
        $this->resetAfterTest();
        set_config('uploadlimit', 50, 'block_ragflowtutor');
        $this->assertSame(50 * 1024 * 1024, manager::upload_limit_bytes());
        set_config('uploadlimit', 0, 'block_ragflowtutor');
        $this->assertSame(0, manager::upload_limit_bytes(), '0 MB = unlimited');
    }

    /**
     * effective_upload_limit_bytes(): with no block limit it falls back to Moodle's max; with a block limit
     * it never exceeds the smaller of the block limit and Moodle's max.
     *
     * @return void
     */
    public function test_effective_upload_limit_bytes(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->maxbytes = 100 * 1024 * 1024;

        // No block limit -> Moodle's configured maximum.
        set_config('uploadlimit', 0, 'block_ragflowtutor');
        $this->assertSame(
            (int) get_max_upload_file_size($CFG->maxbytes),
            manager::effective_upload_limit_bytes()
        );

        // A small block limit (1 MB) is the binding one (below Moodle's max).
        set_config('uploadlimit', 1, 'block_ragflowtutor');
        $this->assertSame(1 * 1024 * 1024, manager::effective_upload_limit_bytes());
    }

    /**
     * status_message(): the status-dot hover text follows the assistant/KB state — a missing assistant or
     * (for a Moodle KB) a missing/linking knowledge base each win, otherwise a "ready" summary.
     *
     * @return void
     */
    public function test_status_message(): void {
        $base = ['chatstatus' => 'green', 'kbstatus' => 'green', 'chatname' => 'Asst', 'kbname' => 'KB', 'files' => []];
        $ready = (object) ['assistant' => 'Asst', 'kb' => 'KB', 'count' => 0];

        $this->assertSame(
            get_string('status:chatmissing', 'block_ragflowtutor'),
            self::call('status_message', [array_merge($base, ['chatstatus' => 'red']), true])
        );
        $this->assertSame(
            get_string('status:kbmissing', 'block_ragflowtutor'),
            self::call('status_message', [array_merge($base, ['kbstatus' => 'red']), true])
        );
        $this->assertSame(
            get_string('status:linking', 'block_ragflowtutor'),
            self::call('status_message', [array_merge($base, ['kbstatus' => 'yellow']), true])
        );
        $this->assertSame(
            get_string('status:ready', 'block_ragflowtutor', $ready),
            self::call('status_message', [$base, true])
        );
        $this->assertSame(
            get_string('status:readysimple', 'block_ragflowtutor', $ready),
            self::call('status_message', [$base, false])
        );
    }
}
