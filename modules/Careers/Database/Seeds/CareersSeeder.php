<?php

namespace Modules\Careers\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Sample vacancies across the blueprint's job categories so the careers portal
 * is browsable on day one. Upserts by slug — never deletes — so HR's edits in
 * the admin panel and received applications are preserved across deploys.
 * HR can edit or close these in Admin → Jobs.
 */
class CareersSeeder extends Seeder
{
    public function run(): void
    {
        $j   = static fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE);
        $now = date('Y-m-d H:i:s');

        $jobs = [
            [
                'slug' => 'merchandiser-colombo',
                'title' => $j(['en' => 'Merchandiser', 'es' => 'Comercializador']),
                'description' => $j(['en' => "Own the order book for a portfolio of international brands — from costing and sampling through production follow-up to delivery. You will coordinate daily with our design studio, partner factories and customers to keep every style on time and on quality."]),
                'department' => 'Merchandising', 'country' => 'Sri Lanka', 'location' => 'Colombo (Head Office)',
                'employment_type' => 'full-time', 'experience' => '2–4 years in apparel merchandising',
                'qualifications' => $j(['Degree or diploma in apparel/textile management or related field', 'Experience working with international fashion brands', 'Strong costing and critical-path management skills']),
                'skills' => $j(['Merchandising', 'Costing', 'Critical path', 'Communication', 'MS Excel / SAP']),
                'salary_range' => null, 'status' => 'open', 'posted_at' => $now,
            ],
            [
                'slug' => 'production-executive-trincomalee',
                'title' => $j(['en' => 'Production Executive', 'es' => 'Ejecutivo de Producción']),
                'description' => $j(['en' => 'Drive daily production performance at our LEED Gold-certified Trincomalee plant. Plan lines, track efficiency and quality KPIs, and work with QA and industrial engineering to hit delivery targets responsibly.']),
                'department' => 'Production', 'country' => 'Sri Lanka', 'location' => 'Trincomalee',
                'employment_type' => 'full-time', 'experience' => '3+ years in apparel production',
                'qualifications' => $j(['Degree/diploma in production or industrial engineering', 'Hands-on knowledge of sewing operations and line balancing']),
                'skills' => $j(['Line planning', 'Lean manufacturing', 'Quality management', 'Team leadership']),
                'salary_range' => null, 'status' => 'open', 'posted_at' => $now,
            ],
            [
                'slug' => 'sustainability-executive',
                'title' => $j(['en' => 'Sustainability Executive', 'es' => 'Ejecutivo de Sostenibilidad']),
                'description' => $j(['en' => "Help deliver our Better Tomorrow 2028 roadmap: emissions, water and waste programmes, Higg/Worldly verification, biodiversity projects and ESG reporting across our facilities and partner factories."]),
                'department' => 'Sustainability', 'country' => 'Sri Lanka', 'location' => 'Colombo',
                'employment_type' => 'full-time', 'experience' => '1–3 years in sustainability/ESG',
                'qualifications' => $j(['Degree in environmental science, engineering or related field', 'Familiarity with Higg FEM, GHG accounting or ISO 14064 an advantage']),
                'skills' => $j(['ESG reporting', 'Data analysis', 'Stakeholder engagement', 'Project management']),
                'salary_range' => null, 'status' => 'open', 'posted_at' => $now,
            ],
            [
                'slug' => 'hr-executive-colombo',
                'title' => $j(['en' => 'Human Resources Executive', 'es' => 'Ejecutivo de RR. HH.']),
                'description' => $j(['en' => 'Support the full employee lifecycle at our Colombo head office — recruitment, onboarding, engagement and learning & development for a 250+ team.']),
                'department' => 'HR', 'country' => 'Sri Lanka', 'location' => 'Colombo (Head Office)',
                'employment_type' => 'full-time', 'experience' => '1–3 years in HR',
                'qualifications' => $j(['Degree/diploma in HR management', 'Great Place to Work culture champion']),
                'skills' => $j(['Recruitment', 'Onboarding', 'HRIS', 'Employee engagement']),
                'salary_range' => null, 'status' => 'open', 'posted_at' => $now,
            ],
        ];

        $table = $this->db->table('jobs');
        foreach ($jobs as $job) {
            $exists = $table->select('id')->where('slug', $job['slug'])->get()->getRowArray();
            $table->resetQuery();
            if ($exists === null) {
                $this->db->table('jobs')->insert($job + ['created_at' => $now, 'updated_at' => $now]);
            }
            // Existing rows are left untouched — HR owns them after first seed.
        }
    }
}
