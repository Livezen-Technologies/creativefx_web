<?php

namespace Modules\Site\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The editorial pages: about, why us, corporate, the FAQ, the policies and
 * contact.
 *
 * These are CMS pages rather than templates, so an editor can rewrite the
 * refund policy on a Tuesday afternoon without a deployment. Each one is
 * authored as a JSON file under Seeds/data/pages and is turned into a page,
 * one section, and a block per entry in its `blocks` array.
 *
 * Idempotence, the house rule: a page whose `is_custom` flag is set has been
 * edited in the console, and is skipped entirely — page row, sections and
 * blocks. That flag is what stops a release undoing somebody's work, and it
 * has to be checked BEFORE the page row is upserted, not after: checking it
 * afterwards still overwrites the title, the meta description and the status,
 * which is most of what an editor actually changes.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $dir = rtrim(__DIR__, '/') . '/data/pages';

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (! is_array($data) || empty($data['slug'])) {
                log_message('error', 'Page seed file is not valid JSON: {f}', ['f' => $file]);

                continue;
            }

            $this->seedPage($data);
        }
    }

    private function seedPage(array $data): void
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->db->table('pages')->where('slug', $data['slug'])->get()->getRowArray();

        // Checked first. See the class comment for why "after the upsert" is
        // the wrong place and quietly loses an editor's title.
        if ($existing !== null && (int) ($existing['is_custom'] ?? 0) === 1) {
            return;
        }

        $fields = [
            'slug'             => $data['slug'],
            'title'            => $this->map($data['title'] ?? ''),
            'meta_title'       => $this->map($data['seo']['title'] ?? ($data['title'] ?? '')),
            'meta_description' => $this->map($data['seo']['description'] ?? ($data['summary'] ?? '')),
            'meta_keywords'    => $data['seo']['keywords'] ?? null,
            'template'         => 'default',
            'is_home'          => 0,
            'status'           => 'published',
            'updated_at'       => $now,
        ];

        if ($existing !== null) {
            $pageId = (int) $existing['id'];
            $this->db->table('pages')->where('id', $pageId)->update($fields);
        } else {
            $this->db->table('pages')->insert($fields + ['created_at' => $now]);
            $pageId = (int) $this->db->insertID();
        }

        // Sections and blocks are replaced wholesale. They are an ordered
        // structure an editor rearranges as a whole; merging by position
        // produces the worst of both — a block deleted upstream lingering in
        // the middle of the page.
        $sectionIds = array_column(
            $this->db->table('page_sections')->select('id')->where('page_id', $pageId)->get()->getResultArray(),
            'id'
        );
        if ($sectionIds !== []) {
            $this->db->table('page_blocks')->whereIn('section_id', $sectionIds)->delete();
        }
        $this->db->table('page_sections')->where('page_id', $pageId)->delete();

        $this->db->table('page_sections')->insert([
            'key'        => 'main',
            'page_id'    => $pageId,
            'type'       => 'content',
            'sort_order' => 1,
            'status'     => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sectionId = (int) $this->db->insertID();

        $sort = 0;
        foreach ($data['blocks'] ?? [] as $block) {
            $type = (string) ($block['type'] ?? 'richtext');
            unset($block['type']);

            $this->db->table('page_blocks')->insert([
                'section_id' => $sectionId,
                'type'       => $type,
                // The payload is the block minus its type, with every string
                // wrapped as a locale map so t_field() resolves it and the
                // Translation Manager can add Sinhala later without a schema
                // change.
                'content'    => json_encode($this->localise($block), JSON_UNESCAPED_UNICODE),
                'sort_order' => ++$sort,
                'status'     => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Wrap every string in a structure as a locale map, recursively.
     *
     * A block's payload is arbitrarily shaped — a heading, a list of cards, a
     * list of steps — and the block partials resolve every reader-facing value
     * through t_field(). t_field() passes a plain string through unchanged, so
     * this is not strictly required for the site to render; it is required for
     * the Translation Manager to have somewhere to put the Sinhala.
     *
     * Keys that are not prose are left alone: a URL, an icon name or a boolean
     * is not translatable, and wrapping one produces a locale map an editor is
     * then invited to translate.
     */
    private function localise(array $node): array
    {
        static $plain = ['url', 'href', 'icon', 'button_url', 'image', 'slug', 'type', 'value', 'code'];

        $out = [];
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $out[$key] = $this->localise($value);
            } elseif (is_string($value) && ! in_array($key, $plain, true) && $value !== '') {
                $out[$key] = ['en' => $value];
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function map(string $value): string
    {
        return json_encode($value === '' ? [] : ['en' => $value], JSON_UNESCAPED_UNICODE);
    }
}
