<?php

namespace Modules\Auth\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Who can do what.
 *
 * The roles a training business actually has, which is not the same list as a
 * publishing site's: the person who schedules classes, the person who chases
 * corporate quotes and the person who reconciles payments are three different
 * people with three different reasons to be in the console, and one shared
 * login is how a refund gets issued by somebody who was only meant to be
 * marking a register.
 *
 * `learner` is the important addition, and it is deliberately at the bottom.
 * Every self-registering customer gets it, so it must never be a role the admin
 * console accepts — see AdminAuthFilter, which allows only the staff roles
 * listed in STAFF. Getting that the wrong way round would mean anybody who
 * bought a course could sign into the back office.
 */
class RoleSeeder extends Seeder
{
    /**
     * The roles that may reach /admin at all.
     *
     * Named here, beside the definitions, rather than in the filter — the list
     * only means anything next to the roles it is drawn from, and a second copy
     * somewhere else is a second thing to forget to update.
     */
    public const STAFF = [
        'super-admin',
        'content-manager',
        'scheduler',
        'sales',
        'finance',
        'instructor',
        'moderator',
        'translator',
        'viewer',
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $roles = [
            ['Administrator', 'super-admin', 'Full rights over all content, users, privileges, configuration and system settings'],
            ['Content Manager', 'content-manager', 'Courses, pages, posts, resources and SEO. No access to financial data'],
            ['Scheduler', 'scheduler', 'Sessions, venues, instructor assignment, attendance and transfer requests'],
            ['Sales', 'sales', 'Leads, quotes, corporate accounts, rate cards and private sessions'],
            ['Finance', 'finance', 'Orders, payments, refunds, invoices and revenue reporting'],
            ['Instructor', 'instructor', 'Their own sessions: rosters, attendance and materials. No editorial or financial rights'],
            ['Moderator', 'moderator', 'Reviews and public comments. Cannot edit site content'],
            ['Translator', 'translator', 'Sinhala variants of existing records only; cannot alter the English source or the structure'],
            ['Viewer', 'viewer', 'Read-only access to the console'],
            // Everybody who buys a course. Has no console access at all — the
            // account area is a separate session (LearnerAuth) behind a
            // separate filter, and this role exists so that "is this person a
            // customer?" is a question the database can answer.
            ['Learner', 'learner', 'A customer: their own enrolments, progress, certificates and invoices. No console access'],
            ['Corporate manager', 'corporate-manager', 'Their own company account: seats, attendees, completion reports and consolidated invoices'],
        ];

        $rows = [];
        foreach ($roles as [$name, $slug, $desc]) {
            $rows[] = [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $desc,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        $this->db->table('roles')->ignore(true)->insertBatch($rows);
    }
}
