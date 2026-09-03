<?php

namespace Modules\Admin\Libraries;

/**
 * What the dashboard shows, in what order, at what size — per administrator.
 *
 * The registry is code: a widget's data and markup are the developer's. The
 * arrangement is data, and it belongs to the person signed in, because the
 * manager who wants traffic first and the person who only checks the enquiry
 * inbox are both right about their own screen.
 *
 * A stored layout is merged with the registry rather than trusted wholesale.
 * That is what lets a widget added in a release appear for people who already
 * have a saved layout, and lets one be removed without leaving a dangling key
 * that renders nothing. Anyone's arrangement survives both.
 */
final class DashboardLayout
{
    /** Widths, as columns of the twelve-column row the dashboard lays out on. */
    public const SIZES = [
        'small'  => ['label' => 'Small', 'span' => 'xl:col-span-4'],
        'medium' => ['label' => 'Medium', 'span' => 'xl:col-span-6'],
        'large'  => ['label' => 'Large', 'span' => 'xl:col-span-8'],
        'full'   => ['label' => 'Full width', 'span' => 'xl:col-span-12'],
    ];

    /** key => [label, about, default size, default order] */
    private const WIDGETS = [
        'kpis'      => ['label' => 'Summary cards',       'about' => 'Messages, leads, rooms, locations, pages and media at a glance.', 'size' => 'full',   'order' => 0],
        'traffic'   => ['label' => 'Traffic chart',       'about' => 'Page views and visitors over the last fortnight.',                'size' => 'large',  'order' => 1],
        'top_pages' => ['label' => 'Most-viewed pages',   'about' => 'Which pages people actually read.',                               'size' => 'small',  'order' => 2],
        'referrers' => ['label' => 'Where they came from', 'about' => 'Search, social and direct.',                                     'size' => 'medium', 'order' => 3],
        'enquiries' => ['label' => 'Recent enquiries',    'about' => 'The latest booking requests and messages.',                       'size' => 'medium', 'order' => 4],
        'content'   => ['label' => 'Recently updated',    'about' => 'Pages and posts changed lately.',                                 'size' => 'medium', 'order' => 5],
        'site'      => ['label' => 'Site summary',        'about' => 'Counts of pages, rooms, locations and media storage.',            'size' => 'small',  'order' => 6],
    ];

    private int $userId;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId ?? (int) (session()->get('admin_user')['id'] ?? 0);
    }

    /** @return list<array{key:string,label:string,about:string,size:string,span:string,enabled:bool,order:int}> */
    public function widgets(): array
    {
        $saved = $this->stored();

        $out = [];
        foreach (self::WIDGETS as $key => $meta) {
            $pref = $saved[$key] ?? [];
            // A size that is no longer offered falls back to the default rather
            // than producing a widget with no width at all.
            $size = $pref['size'] ?? $meta['size'];
            $size = isset(self::SIZES[$size]) ? $size : $meta['size'];

            $out[] = [
                'key'     => $key,
                'label'   => $meta['label'],
                'about'   => $meta['about'],
                'size'    => $size,
                'span'    => self::SIZES[$size]['span'],
                'enabled' => (bool) ($pref['enabled'] ?? true),
                'order'   => (int) ($pref['order'] ?? $meta['order']),
            ];
        }

        usort($out, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp($a['key'], $b['key']));

        return $out;
    }

    /** Just the ones to draw, in order. */
    public function visible(): array
    {
        return array_values(array_filter($this->widgets(), static fn (array $w): bool => $w['enabled']));
    }

    public function setEnabled(string $key, bool $enabled): void
    {
        $this->update($key, ['enabled' => $enabled]);
    }

    public function setSize(string $key, string $size): void
    {
        if (isset(self::SIZES[$size])) {
            $this->update($key, ['size' => $size]);
        }
    }

    /**
     * Move one widget up or down.
     *
     * The whole order is renumbered from the current arrangement rather than
     * two positions being swapped. Swapping works only while the stored numbers
     * are contiguous, and they stop being contiguous the first time a widget is
     * added to the registry with an order another one already has.
     */
    public function move(string $key, string $direction): void
    {
        $widgets = $this->widgets();

        $index = null;
        foreach ($widgets as $i => $w) {
            if ($w['key'] === $key) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($target < 0 || $target >= count($widgets)) {
            return;
        }

        [$widgets[$index], $widgets[$target]] = [$widgets[$target], $widgets[$index]];

        $saved = $this->stored();
        foreach ($widgets as $i => $w) {
            $saved[$w['key']]          = $saved[$w['key']] ?? [];
            $saved[$w['key']]['order'] = $i;
        }
        $this->save($saved);
    }

    /** Back to the arrangement the site ships with. */
    public function reset(): void
    {
        $this->save([]);
    }

    // ---- Storage ------------------------------------------------------------

    private function stored(): array
    {
        if ($this->userId <= 0) {
            return [];
        }

        try {
            $row = db_connect()->table('user_preferences')
                ->where('user_id', $this->userId)->where('key', 'dashboard')
                ->get()->getRowArray();
        } catch (\Throwable $e) {
            return [];
        }

        $decoded = json_decode((string) ($row['value'] ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function update(string $key, array $changes): void
    {
        if (! isset(self::WIDGETS[$key])) {
            return;
        }

        $saved       = $this->stored();
        $saved[$key] = array_merge($saved[$key] ?? [], $changes);
        $this->save($saved);
    }

    private function save(array $layout): void
    {
        if ($this->userId <= 0) {
            return;
        }

        $table    = db_connect()->table('user_preferences');
        $now      = date('Y-m-d H:i:s');
        $existing = $table->where('user_id', $this->userId)->where('key', 'dashboard')->get()->getRowArray();
        $value    = json_encode($layout, JSON_UNESCAPED_UNICODE);

        if ($existing === null) {
            $table->insert(['user_id' => $this->userId, 'key' => 'dashboard', 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
        } else {
            $table->where('id', $existing['id'])->update(['value' => $value, 'updated_at' => $now]);
        }
    }
}
