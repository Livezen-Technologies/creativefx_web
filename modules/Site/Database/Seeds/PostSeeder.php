<?php

namespace Modules\Site\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The launch articles.
 *
 * Organic search is the whole growth plan for this business, and an empty blog
 * is a plan with nothing in it. These six are written against the clusters the
 * content strategy names — Adobe, AI and careers — and each one ends pointing
 * at a course, which is what turns a reader into an enquiry.
 *
 * Upsert by slug, and stop at a post somebody has edited: a row whose
 * `updated_at` differs from its `created_at` has been touched in the console,
 * and a release must not rewrite it. That is the same test the rest of the
 * platform uses for content it does not own.
 */
class PostSeeder extends Seeder
{
    /** The article categories, which are created only while none exist. */
    private const CATEGORIES = [
        'adobe'  => 'Adobe Creative Cloud',
        'ai'     => 'AI and generative AI',
        'career' => 'Careers and portfolios',
    ];

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::CATEGORIES as $slug => $name) {
            $exists = $this->db->table('news_categories')->where('slug', $slug)->countAllResults();
            if ($exists === 0) {
                $this->db->table('news_categories')->insert([
                    'slug'       => $slug,
                    'name'       => json_encode(['en' => $name], JSON_UNESCAPED_UNICODE),
                    'sort_order' => 0,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $categories = array_column(
            $this->db->table('news_categories')->select('id, slug')->get()->getResultArray(),
            'id',
            'slug'
        );

        $dir = rtrim(__DIR__, '/') . '/data/posts';

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (! is_array($data) || empty($data['slug'])) {
                log_message('error', 'Post seed file is not valid JSON: {f}', ['f' => $file]);

                continue;
            }

            $existing = $this->db->table('news_posts')->where('slug', $data['slug'])->get()->getRowArray();

            // Touched in the console. `updated_at !== created_at` is the test
            // the rest of the platform uses, and it needs no extra column.
            if ($existing !== null && $existing['updated_at'] !== $existing['created_at']) {
                continue;
            }

            $fields = [
                'category_id'      => $categories[$data['category'] ?? 'adobe'] ?? null,
                'slug'             => $data['slug'],
                'title'            => $this->map($data['title'] ?? ''),
                'excerpt'          => $this->map($data['excerpt'] ?? ''),
                'body'             => $this->map($data['body_html'] ?? ''),
                'tags'             => json_encode($data['tags'] ?? [], JSON_UNESCAPED_UNICODE),
                'author'           => (string) setting('site_name', 'MyLearnPlus'),
                'meta_title'       => $this->map($data['seo']['title'] ?? ''),
                'meta_description' => $this->map($data['seo']['description'] ?? ''),
                'status'           => 'published',
                // Published on the day the site was seeded rather than a fixed
                // date in the file: six articles all stamped with the same
                // historical afternoon reads as an import, which is what it is,
                // and a future date would hide them from the listing entirely.
                'published_at'     => $now,
                'updated_at'       => $now,
            ];

            if ($existing !== null) {
                $this->db->table('news_posts')->where('id', (int) $existing['id'])->update($fields);
            } else {
                $this->db->table('news_posts')->insert($fields + ['created_at' => $now]);
            }
        }
    }

    private function map(string $value): string
    {
        return json_encode($value === '' ? [] : ['en' => $value], JSON_UNESCAPED_UNICODE);
    }
}
