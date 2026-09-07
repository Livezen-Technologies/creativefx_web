<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * A one-seat session that exists only for the length of a concurrency test.
 *
 * Its own row rather than a real one from the catalogue: a run that dies half
 * way must not leave a published class holding phantom seats, and a test that
 * mutates seeded data is a test somebody will eventually stop trusting.
 *
 *   php spark seats:fixture create [locked|naive]   -> prints the session id
 *   php spark seats:fixture state <id>              -> "consistent" or why not
 *   php spark seats:fixture destroy <id>
 *
 * Development only. It writes a session nobody should be able to book.
 */
class SeatsFixture extends BaseCommand
{
    protected $group       = 'Commerce';
    protected $name        = 'seats:fixture';
    protected $description = 'Create, inspect and remove the one-seat session the concurrency check uses.';
    protected $usage       = 'seats:fixture [create|state|destroy] [sessionId]';

    public function run(array $params)
    {
        if (ENVIRONMENT === 'production') {
            CLI::error('seats:fixture is a test fixture and is disabled in production.');

            return;
        }

        $action = strtolower((string) ($params[0] ?? ''));
        $db     = db_connect();

        switch ($action) {
            case 'create':
                $course = $db->table('courses')->select('id')->orderBy('id', 'ASC')->get()->getRowArray();
                if ($course === null) {
                    CLI::error('No courses. Seed the catalogue first.');

                    return;
                }

                $now = date('Y-m-d H:i:s');
                $db->table('course_sessions')->insert([
                    'course_id'   => (int) $course['id'],
                    'mode'        => 'LIVE_ONLINE',
                    'language'    => 'en',
                    'start_date'  => date('Y-m-d', strtotime('+60 days')),
                    'end_date'    => date('Y-m-d', strtotime('+60 days')),
                    'timezone'    => 'Asia/Colombo',
                    'daily_start' => '09:00:00',
                    'daily_end'   => '16:00:00',
                    'seats_total' => 1,
                    'min_to_run'  => 1,
                    'status'      => 'open',
                    // Never listed. A fixture that appears in the schedule is a
                    // class somebody can find and try to buy.
                    'is_private'  => 1,
                    'notes'       => 'Concurrency fixture — safe to delete.',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                CLI::write((string) $db->insertID());

                return;

            case 'state':
                $id = (int) ($params[1] ?? 0);
                $s  = $db->table('course_sessions')->where('id', $id)->get()->getRowArray();
                if ($s === null) {
                    CLI::write('missing');

                    return;
                }

                $held = (int) ($db->table('seat_holds')
                    ->select('COALESCE(SUM(qty), 0) AS held', false)
                    ->where('session_id', $id)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->get()->getRowArray()['held'] ?? 0);

                // Two questions, not one. The cached count has to match the
                // holds that exist, AND the holds must not exceed the seats —
                // an oversell that also updated the cache faithfully is still
                // an oversell.
                if ($held > (int) $s['seats_total']) {
                    CLI::write("oversold: {$held} holds on {$s['seats_total']} seat(s)");

                    return;
                }
                if ((int) $s['seats_reserved'] !== $held) {
                    CLI::write("cache drift: seats_reserved={$s['seats_reserved']} but {$held} hold(s) exist");

                    return;
                }

                CLI::write('consistent');

                return;

            case 'destroy':
                $id = (int) ($params[1] ?? 0);
                $db->table('seat_holds')->where('session_id', $id)->delete();
                $db->table('session_prices')->where('session_id', $id)->delete();
                $db->table('course_sessions')->where('id', $id)->where('is_private', 1)->delete();
                CLI::write('removed');

                return;
        }

        CLI::error('Unknown action. Use create, state or destroy.');
    }
}
