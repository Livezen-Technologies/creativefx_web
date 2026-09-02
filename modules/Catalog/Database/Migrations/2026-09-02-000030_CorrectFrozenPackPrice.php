<?php

namespace Modules\Catalog\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The 1kg frozen pack was seeded at LKR 2,250.00. The figure was never taken
 * from the shop — it was carried over when the catalogue was written, and the
 * crawler that should have caught it was silently discarding every page after
 * its first, so the shop listing was never actually read. magiccorn.lk sells
 * the pack at LKR 1,100.00, reduced from 1,200.00.
 *
 * A wrong price on a published product page is worth correcting even though
 * the row is otherwise the merchandiser's to own, so this rewrites the Price
 * spec — and only that spec, leaving every other field alone. It is skipped if
 * the figure has already been changed to anything else.
 */
class CorrectFrozenPackPrice extends Migration
{
    private const SLUG  = '1kg-frozen-sweet-corn-pack';
    private const WRONG = 'LKR 2,250.00';
    private const RIGHT = 'LKR 1,100.00';

    public function up(): void
    {
        $this->reprice(self::WRONG, self::RIGHT);
    }

    public function down(): void
    {
        $this->reprice(self::RIGHT, self::WRONG);
    }

    private function reprice(string $from, string $to): void
    {
        $row = $this->db->table('products')
            ->select('id, specs')
            ->where('slug', self::SLUG)
            ->get()
            ->getRowArray();

        if ($row === null) {
            return;
        }

        $specs = json_decode((string) $row['specs'], true);
        if (! is_array($specs)) {
            return;
        }

        $changed = false;
        foreach ($specs as $i => $spec) {
            if (($spec['label'] ?? '') === 'Price' && ($spec['value'] ?? '') === $from) {
                $specs[$i]['value'] = $to;
                $changed            = true;
            }
        }

        if (! $changed) {
            return;
        }

        $this->db->table('products')->where('id', $row['id'])->update([
            'specs'      => json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
