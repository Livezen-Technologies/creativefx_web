<?php

namespace Modules\Core\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Core\Libraries\Maintenance as MaintenanceMode;

/**
 * The switch, from the server:
 *
 *   php spark maintenance on --until "18 Aug, 09:00"
 *   php spark maintenance status
 *   php spark maintenance off
 *
 * Same lever as Admin -> Maintenance; this one also works when the site
 * cannot be reached over HTTP at all.
 */
class Maintenance extends BaseCommand
{
    protected $group       = 'Norlanka';
    protected $name        = 'maintenance';
    protected $description = 'Take the public site offline for maintenance, or bring it back.';
    protected $usage       = 'maintenance [on|off|status] [options]';

    protected $arguments = [
        'action' => 'on, off, or status (default: status)',
    ];

    protected $options = [
        '--headline' => 'Headline shown on the offline page.',
        '--message'  => 'Body text shown on the offline page.',
        '--until'    => 'When the site is expected back, e.g. "18 Aug, 09:00".',
    ];

    public function run(array $params)
    {
        $action = strtolower((string) ($params[0] ?? 'status'));

        return match ($action) {
            'on', 'enable', 'down'  => $this->enable(),
            'off', 'disable', 'up'  => $this->disable(),
            'status', ''            => $this->status(),
            default                 => $this->unknown($action),
        };
    }

    private function enable(): int
    {
        foreach (['headline', 'message', 'until'] as $option) {
            $value = CLI::getOption($option);
            if (is_string($value) && $value !== '') {
                MaintenanceMode::put($option, $value);
            }
        }

        $flagged = MaintenanceMode::enable('cli');

        CLI::write('Maintenance mode is ON — the public site now answers 503.', 'yellow');

        if (! $flagged) {
            CLI::error('Could not write ' . MaintenanceMode::flagPath() . ' — the settings row is holding it on instead.');
            CLI::write('Fix the permissions on writable/ so the switch survives a database outage.');
        }

        $this->details();

        return EXIT_SUCCESS;
    }

    private function disable(): int
    {
        $cleared = MaintenanceMode::disable();

        if (! $cleared) {
            CLI::error('Could not remove ' . MaintenanceMode::flagPath() . ' — the site is still offline.');
            CLI::write('Delete that file by hand, then run this command again.');

            return EXIT_ERROR;
        }

        CLI::write('Maintenance mode is OFF — the public site is live.', 'green');

        return EXIT_SUCCESS;
    }

    private function status(): int
    {
        $state = MaintenanceMode::state();

        CLI::write('Site: ' . ($state['active']
            ? CLI::color('OFFLINE (503)', 'yellow')
            : CLI::color('live', 'green')));

        $this->details();

        return EXIT_SUCCESS;
    }

    /** The supporting lines shown under both `on` and `status`. */
    private function details(): void
    {
        helper('url');
        $state = MaintenanceMode::state();

        $levers = [];
        if ($state['env']) {
            $levers[] = 'maintenance.enabled in .env';
        }
        if ($state['flag']) {
            $levers[] = 'writable/maintenance.flag';
        }
        CLI::write('Held by: ' . ($levers === [] ? 'the settings table' : implode(' + ', $levers)));

        if ($state['since'] !== '') {
            CLI::write('Since:   ' . $state['since']);
        }
        if ($state['until'] !== '') {
            CLI::write('Until:   ' . $state['until']);
        }

        if ($state['active'] && $state['bypass_key'] !== '') {
            CLI::write('Preview: ' . base_url() . '?' . MaintenanceMode::BYPASS_QUERY . '=' . $state['bypass_key']);
        }

        CLI::write('Admin:   ' . site_url('admin/maintenance') . ' (always reachable)');
    }

    private function unknown(string $action): int
    {
        CLI::error('Unknown action "' . $action . '".');
        CLI::write('Usage: ' . $this->usage);

        return EXIT_ERROR;
    }
}
