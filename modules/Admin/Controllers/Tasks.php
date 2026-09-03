<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Scheduler;

/**
 * Scheduled tasks: what runs, when it last ran, and whether it worked.
 *
 * Written for whoever notices that something has not happened, who is not
 * necessarily the person with shell access — so the last result is a sentence
 * on the page rather than a line in a log file, and there is a button to run a
 * task by hand and watch what it says.
 */
class Tasks extends BaseController
{
    public function __construct()
    {
        helper(['admin', 'url']);
    }

    public function index()
    {
        if (! admin_can('settings.view')) {
            return redirect()->to(site_url('admin'))->with('error', 'You do not have permission to view scheduled tasks.');
        }

        return view('Modules\Admin\Views\tasks', [
            'title'   => 'Scheduled tasks',
            'active'  => 'tasks',
            'tasks'   => (new Scheduler())->all(),
            // The exact line to paste into a crontab, with this install's own
            // paths in it. A generic example is one an admin has to translate,
            // and translating it wrong is why cron silently never runs.
            'command' => '0 * * * * cd ' . ROOTPATH . ' && ' . (PHP_BINARY ?: 'php') . ' spark tasks:run >> ' . WRITEPATH . 'logs/cron.log 2>&1',
        ]);
    }

    public function run(string $key)
    {
        if (! admin_can('settings.manage')) {
            return redirect()->to(site_url('admin/tasks'))->with('error', 'You do not have permission to run tasks.');
        }

        $result   = (new Scheduler())->run($key);
        $redirect = redirect()->to(site_url('admin/tasks'));

        return match ($result['status']) {
            'ok'     => $redirect->with('message', 'Ran: ' . $result['message']),
            'skipped' => $redirect->with('message', $result['message']),
            default  => $redirect->with('error', 'Failed: ' . $result['message']),
        };
    }

    public function toggle(string $key)
    {
        if (! admin_can('settings.manage')) {
            return redirect()->to(site_url('admin/tasks'))->with('error', 'You do not have permission to change tasks.');
        }

        $scheduler = new Scheduler();
        $tasks     = $scheduler->all();
        if (! isset($tasks[$key])) {
            return redirect()->to(site_url('admin/tasks'))->with('error', 'Unknown task.');
        }

        $enabled = (int) $tasks[$key]['enabled'] !== 1;
        $scheduler->setEnabled($key, $enabled);

        return redirect()->to(site_url('admin/tasks'))
            ->with('message', $tasks[$key]['label'] . ($enabled ? ' will run on schedule.' : ' will not run until it is turned back on.'));
    }
}
