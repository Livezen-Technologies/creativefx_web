<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Core\Libraries\Scheduler;

/**
 * What cron actually calls.
 *
 * One entry in the crontab, running every hour, rather than one per task: the
 * scheduler decides what is due, so adding a task never means editing the
 * server's crontab again — which is the step that gets forgotten.
 */
class RunScheduledTasks extends BaseCommand
{
    protected $group       = 'Scheduling';
    protected $name        = 'tasks:run';
    protected $description = 'Runs every scheduled task that is due.';
    protected $usage       = 'tasks:run [task_key]';

    public function run(array $params)
    {
        $scheduler = new Scheduler();

        // A named task runs whether or not it is due, which is what somebody
        // debugging one at 2am actually wants.
        if (isset($params[0])) {
            $result = $scheduler->run($params[0]);
            CLI::write($params[0] . ': ' . $result['status'] . ' — ' . $result['message'],
                $result['status'] === 'failed' ? 'red' : 'green');

            return $result['status'] === 'failed' ? EXIT_ERROR : EXIT_SUCCESS;
        }

        $failed = 0;
        foreach ($scheduler->runDue() as $key => $line) {
            $colour = str_starts_with($line, 'failed') ? 'red' : (str_starts_with($line, 'ok') ? 'green' : 'dark_gray');
            CLI::write(str_pad($key, 20) . $line, $colour);
            $failed += (int) str_starts_with($line, 'failed');
        }

        return $failed > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
