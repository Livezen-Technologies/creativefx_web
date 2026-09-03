<?php

namespace Modules\Careers\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Starting vacancies, so the Vacancies section (Clause 3.9 G) is browsable and
 * reviewable from day one.
 *
 * Upserts by slug and never deletes, so posts the Administration Division has
 * edited or closed in the console — and the applications already received
 * against them — survive a release.
 *
 * These are drafted against the Authority's own grades. They carry a closing
 * date relative to the seed run, which is what exercises the automatic expiry
 * Clause 3.9 G asks for: a post whose closing date passes drops off the listing
 * on the day, without anybody remembering to unpublish it.
 */
class CareersSeeder extends Seeder
{
    public function run(): void
    {
        helper('norlanka');
        $now = date('Y-m-d H:i:s');

        foreach ($this->jobs() as $job) {
            $exists = $this->db->table('jobs')->where('slug', $job['slug'])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $this->db->table('jobs')->insert([
                'slug'            => $job['slug'],
                'title'           => $this->loc($job['title']),
                'description'     => $this->loc($job['description']),
                'department'      => $job['department'],
                'country'         => 'Sri Lanka',
                'location'        => $job['location'],
                'employment_type' => 'full-time',
                'experience'      => $job['experience'],
                'qualifications'  => json_encode($job['qualifications'], JSON_UNESCAPED_UNICODE),
                'skills'          => json_encode($job['skills'], JSON_UNESCAPED_UNICODE),
                'salary_range'    => $job['salary'],
                'status'          => 'open',
                'posted_at'       => $now,
                'closes_at'       => date('Y-m-d H:i:s', strtotime('+' . $job['closes'] . ' days')),
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    private function loc(string $en): string
    {
        return json_encode(content_locales(['en' => $en]), JSON_UNESCAPED_UNICODE);
    }

    private function jobs(): array
    {
        return [
            [
                'slug' => 'tea-inspector',
                'title' => 'Tea Inspector',
                'description' => 'The Authority’s point of contact with the tea smallholder. A Tea Inspector knows the holdings in their range, inspects blocks for subsidy and certifies the work at each stage, advises on field practice, and keeps the register of holdings accurate. Posts are advertised for named ranges across the tea-growing districts; the ranges being filled in this round are stated in the recruitment notice.',
                'department' => 'Extension & Training',
                'location' => 'Tea-growing districts (range to be assigned)',
                'experience' => 'As stated in the scheme of recruitment',
                'qualifications' => [
                    'National Diploma in Agriculture, or an equivalent qualification recognised by the Authority',
                    'Sri Lankan citizenship, and the age limits stated in the recruitment notice',
                    'Willingness to be posted to any tea-growing district',
                    'A valid motorcycle licence is an advantage',
                ],
                'skills' => ['Field extension', 'Tea agronomy', 'Record keeping', 'Working with smallholder communities'],
                'salary' => 'On the Authority’s salary scale, as stated in the recruitment notice',
                'closes' => 30,
            ],
            [
                'slug' => 'extension-officer',
                'title' => 'Extension Officer',
                'description' => 'Delivers the Authority’s technical advisory service in the field: advice on planting material, pruning, plucking rounds, nutrition, pest and disease management and leaf quality; support to societies in the range; and the field inspections on which subsidy instalments are released.',
                'department' => 'Extension & Training',
                'location' => 'Tea-growing districts',
                'experience' => 'As stated in the scheme of recruitment',
                'qualifications' => [
                    'Degree or National Diploma in Agriculture or a related field',
                    'Sri Lankan citizenship, and the age limits stated in the recruitment notice',
                    'Fluency in Sinhala or Tamil, with a working knowledge of the other and of English',
                ],
                'skills' => ['Extension advisory', 'Soil and crop management', 'Community engagement', 'Reporting'],
                'salary' => 'On the Authority’s salary scale',
                'closes' => 30,
            ],
            [
                'slug' => 'management-assistant',
                'title' => 'Management Assistant (Technical / Non-Technical)',
                'description' => 'Administrative and clerical support at Head Office and the regional offices: correspondence, registers, subsidy files, procurement paperwork and the day-to-day running of an office that the public walks into.',
                'department' => 'Administration',
                'location' => 'Head Office, Battaramulla and regional offices',
                'experience' => 'As stated in the scheme of recruitment',
                'qualifications' => [
                    'Six passes at G.C.E. (Ordinary Level) including Sinhala or Tamil, English and Mathematics',
                    'Three passes at G.C.E. (Advanced Level) in one sitting',
                    'Sri Lankan citizenship, and the age limits stated in the recruitment notice',
                ],
                'skills' => ['Office administration', 'Record keeping', 'Correspondence', 'Computer literacy'],
                'salary' => 'On the Authority’s salary scale',
                'closes' => 21,
            ],
            [
                'slug' => 'ict-officer',
                'title' => 'Information and Communication Technology Officer',
                'description' => 'Runs the Authority’s systems and registers, maintains this website and its content, supports the regional offices, and safeguards the data on which entitlements and allocations are calculated.',
                'department' => 'Information & Communication Technology',
                'location' => 'Head Office, Battaramulla',
                'experience' => 'Three years in a comparable role',
                'qualifications' => [
                    'Degree in Information Technology, Computer Science or a related field',
                    'Experience administering a web application and a relational database in production',
                    'Working knowledge of information security practice',
                ],
                'skills' => ['Systems administration', 'Web content management', 'Databases', 'Information security', 'User support'],
                'salary' => 'On the Authority’s salary scale',
                'closes' => 45,
            ],
        ];
    }
}
