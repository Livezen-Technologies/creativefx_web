<?php

namespace Modules\Careers\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Two vacancies were withdrawn from the careers page: the India senior
 * merchandiser and the digital-core software engineer.
 *
 * CareersSeeder only ever inserts — it never rewrites or removes an existing
 * row, because HR owns these records once they are live — so dropping the two
 * entries from the seeder keeps them out of fresh installs but leaves the rows
 * already in the database untouched. This closes them.
 *
 * Closed rather than deleted on purpose: job_applications carries an ON DELETE
 * CASCADE foreign key to jobs, so removing the rows would take any applications
 * received for these roles with them. JobModel filters the careers listing and
 * the detail page on status = 'open', so closing takes both roles off the site
 * while HR keeps the postings and their applicants in Admin → Jobs.
 */
class CloseWithdrawnVacancies extends Migration
{
    private const WITHDRAWN = [
        'merchandiser-tirupur',
        'software-engineer-digital',
    ];

    public function up(): void
    {
        $this->db->table('jobs')
            ->whereIn('slug', self::WITHDRAWN)
            ->update(['status' => 'closed', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        $this->db->table('jobs')
            ->whereIn('slug', self::WITHDRAWN)
            ->update(['status' => 'open', 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
