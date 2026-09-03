<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Housekeeping that runs on a schedule, and a record of whether it did.
 *
 * The interesting column is `locked_at`. A cron entry that fires every five
 * minutes will happily start a second copy of a job the first copy has not
 * finished — two runs deleting the same rows, or two emails for one booking.
 * A row is claimed by stamping this, and only a task whose stamp is empty or
 * stale is picked up. Stale rather than merely set, because a process killed
 * mid-run would otherwise leave its task locked forever.
 *
 * `last_message` exists so a failure is legible without opening a log file:
 * whoever notices that something has not happened is not necessarily the person
 * with shell access.
 */
class CreateScheduledTasksTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('scheduled_tasks')) {
            return;
        }

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'key'           => ['type' => 'VARCHAR', 'constraint' => 64],
            'enabled'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'frequency'     => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'daily', 'comment' => 'hourly | daily | weekly | monthly'],
            'last_run_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_status'   => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'comment' => 'ok | failed | skipped'],
            'last_message'  => ['type' => 'TEXT', 'null' => true],
            'last_ms'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'locked_at'     => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Claimed by a running process; stale locks are reclaimed'],
            'runs'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'failures'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');
        $this->forge->createTable('scheduled_tasks');
    }

    public function down(): void
    {
        $this->forge->dropTable('scheduled_tasks', true);
    }
}
