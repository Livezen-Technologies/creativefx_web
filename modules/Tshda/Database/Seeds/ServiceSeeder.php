<?php

namespace Modules\Tshda\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The service catalogue (Clause 3.9 D and E) and the stakeholder clusters the
 * home page groups them into (B.I).
 *
 * Upsert by slug, and only while the row still looks like the one this seeder
 * wrote: `updated_at` is compared against `created_at`, so a service an officer
 * has edited in the console is left alone and a release can still correct a
 * typo in one nobody has touched.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        helper('norlanka');
        $now = date('Y-m-d H:i:s');

        $order = 0;
        foreach ($this->services() as $slug => $def) {
            $row = [
                'slug'          => $slug,
                'area'          => $def['area'],
                'audience'      => $def['audience'],
                'title'         => $this->loc($def['title']),
                'summary'       => $this->loc($def['summary']),
                'eligibility'   => $this->loc($def['eligibility']),
                'process'       => json_encode(array_map(fn ($s) => content_locales(['en' => $s]), $def['process']), JSON_UNESCAPED_UNICODE),
                'documents'     => json_encode(array_map(fn ($s) => content_locales(['en' => $s]), $def['documents']), JSON_UNESCAPED_UNICODE),
                'fee'           => $this->loc($def['fee']),
                'duration'      => $this->loc($def['duration']),
                'division'      => $this->loc($def['division']),
                'contact_point' => $this->loc($def['contact']),
                'window_open'   => $def['open'] ?? 1,
                'icon'          => $def['icon'] ?? null,
                'sort_order'    => $order++,
                'status'        => 'published',
                'updated_at'    => $now,
            ];

            $existing = $this->db->table('services')->where('slug', $slug)->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('services')->insert($row + ['created_at' => $now]);
            } elseif (($existing['updated_at'] ?? '') === ($existing['created_at'] ?? '')) {
                $this->db->table('services')->where('slug', $slug)->update($row);
            }
        }
    }

    private function loc(string $en): string
    {
        return json_encode(content_locales(['en' => $en]), JSON_UNESCAPED_UNICODE);
    }

    private function services(): array
    {
        return [
            'replanting-subsidy' => [
                'area' => 'land', 'audience' => 'smallholder', 'icon' => 'sprout',
                'title' => 'Replanting subsidy',
                'summary' => 'A per-hectare grant towards uprooting old, low-yielding tea and replanting with recommended cultivars, paid in instalments against field inspection at each stage.',
                'eligibility' => 'Registered tea smallholders whose holding does not exceed 10 acres (4.05 ha), where the block to be replanted is at least 0.1 ha, is under tea of declining yield, and where the holding is registered with the Authority.',
                'process' => [
                    'Obtain and complete the replanting application from your Tea Inspector or download it below.',
                    'The Tea Inspector inspects the block and certifies its extent, age and condition.',
                    'The Regional Office recommends the application and forwards it to Head Office.',
                    'Approval is issued and the first instalment released on uprooting.',
                    'Further instalments follow planting, and the establishment of the young tea, each after inspection.',
                ],
                'documents' => [
                    'Completed replanting application form',
                    'Certified extract of the deed or title to the land',
                    'Grama Niladhari certificate confirming ownership or lawful possession',
                    'National Identity Card of the applicant',
                    'Smallholder registration number',
                ],
                'fee' => 'No application fee.',
                'duration' => 'Approval typically within 60 days of a complete application; instalments follow the field stages.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'Your Tea Inspector, or the Regional Manager of your district office.',
            ],
            'new-planting-subsidy' => [
                'area' => 'land', 'audience' => 'smallholder', 'icon' => 'seedling',
                'title' => 'New planting subsidy',
                'summary' => 'Assistance for bringing suitable new land under tea, within the extents and agro-ecological zones the Authority has approved for expansion.',
                'eligibility' => 'Registered smallholders with suitable, legally held land inside an approved tea-growing zone, where the block is not under forest, not on a slope steeper than the conservation guideline permits, and is not already under a subsidised crop.',
                'process' => [
                    'Discuss the block with your Tea Inspector before any clearing is done.',
                    'Submit the new planting application with proof of title.',
                    'A field inspection confirms suitability, soil and slope.',
                    'Approval is issued with the planting programme and conservation conditions attached.',
                    'Instalments are released against inspection at land preparation, planting and establishment.',
                ],
                'documents' => [
                    'Completed new planting application form',
                    'Certified extract of the deed or title',
                    'Grama Niladhari certificate',
                    'National Identity Card',
                    'Soil conservation plan where the slope requires one',
                ],
                'fee' => 'No application fee.',
                'duration' => 'Approval typically within 60 days of a complete application.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'Your Tea Inspector, or the Regional Manager of your district office.',
            ],
            'fertilizer-subsidy' => [
                'area' => 'land', 'audience' => 'smallholder', 'icon' => 'bag',
                'title' => 'Fertilizer assistance',
                'summary' => 'Allocation of subsidised fertilizer to registered smallholdings through the district offices and registered societies, issued against the published allocation for the season.',
                'eligibility' => 'Registered tea smallholders in good standing with an active holding record and a current soil-test or extension recommendation where one is required.',
                'process' => [
                    'Check the current issue notice for your district in the Announcements section.',
                    'Apply through your society or directly at the Regional Office within the stated window.',
                    'Your entitlement is calculated from the registered extent of the holding.',
                    'Collect the allocation from the issuing point named on the notice.',
                ],
                'documents' => [
                    'Smallholder registration number',
                    'National Identity Card',
                    'Society membership number, where applying through a society',
                ],
                'fee' => 'The subsidised price published in the issue notice for the season.',
                'duration' => 'Within the issue window stated on the notice.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'Your Regional Office, or the society you are registered with.',
            ],
            'soil-conservation' => [
                'area' => 'land', 'audience' => 'smallholder', 'icon' => 'terrace',
                'title' => 'Soil conservation assistance',
                'summary' => 'Technical guidance and assistance for terracing, drains, cover crops and other conservation measures on sloping smallholdings, so that replanted land holds its soil.',
                'eligibility' => 'Registered smallholders whose land requires conservation works, identified either at inspection or on the smallholder’s own request.',
                'process' => [
                    'Request an inspection through your Tea Inspector.',
                    'A conservation plan is prepared for the block.',
                    'Assistance is approved against the plan.',
                    'Works are inspected on completion.',
                ],
                'documents' => [
                    'Smallholder registration number',
                    'National Identity Card',
                ],
                'fee' => 'No application fee.',
                'duration' => 'Inspection within 30 days of request, in normal circumstances.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'Your Tea Inspector.',
            ],
            'extension-advisory' => [
                'area' => 'land', 'audience' => 'smallholder', 'icon' => 'advice',
                'title' => 'Extension and technical advisory',
                'summary' => 'Field advice on planting material, pruning, plucking rounds, pest and disease management and leaf quality, delivered through the Tea Inspector ranges that cover every tea-growing district.',
                'eligibility' => 'Open to every registered tea smallholder. Advice is free.',
                'process' => [
                    'Contact the Tea Inspector for your range — find them in the Staff Directory.',
                    'Describe the problem, or request a field visit.',
                    'The officer advises on site or refers the matter to a subject specialist.',
                ],
                'documents' => [
                    'Smallholder registration number, where the holding is registered',
                ],
                'fee' => 'Free of charge.',
                'duration' => 'Field visits are normally arranged within two weeks.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'Your Tea Inspector, listed in the Staff Directory by district.',
            ],
            'smallholder-registration' => [
                'area' => 'land', 'audience' => 'smallholder', 'icon' => 'id',
                'title' => 'Smallholder registration',
                'summary' => 'Registration of a tea smallholding with the Authority. Registration is the key to every other service — subsidies, fertilizer allocation and society membership all read from it.',
                'eligibility' => 'Any person lawfully in possession of land under tea not exceeding 10 acres (4.05 ha).',
                'process' => [
                    'Obtain the registration form from your Tea Inspector or the Regional Office.',
                    'The Tea Inspector verifies the extent and the particulars of the holding.',
                    'The Regional Office issues the registration number.',
                ],
                'documents' => [
                    'Completed registration form',
                    'Certified extract of the deed or title, or evidence of lawful possession',
                    'Grama Niladhari certificate',
                    'National Identity Card',
                ],
                'fee' => 'No fee.',
                'duration' => 'Normally within 30 days of verification.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'Your Regional Office.',
            ],
            'society-registration' => [
                'area' => 'societies', 'audience' => 'society', 'icon' => 'people',
                'title' => 'Tea Smallholder Development Society registration',
                'summary' => 'Formation and registration of a Tea Smallholder Development Society, the village-level body through which smallholders organise, receive inputs and access welfare programmes.',
                'eligibility' => 'A group of registered tea smallholders within one Tea Inspector range, meeting the minimum membership stated in the guidelines and adopting the model constitution.',
                'process' => [
                    'Convene a formation meeting with the Tea Inspector present.',
                    'Adopt the model constitution and elect office bearers.',
                    'Submit the application with the minutes and the member list.',
                    'The Regional Office verifies and forwards the application.',
                    'The registration certificate and society number are issued.',
                ],
                'documents' => [
                    'Completed society registration application',
                    'Minutes of the formation meeting',
                    'Member list with smallholder registration numbers',
                    'Adopted constitution',
                ],
                'fee' => 'As stated in the current guidelines.',
                'duration' => 'Normally within 90 days of a complete application.',
                'division' => 'Societies & Marketing',
                'contact' => 'The Regional Manager of your district office.',
            ],
            'tea-shakthi' => [
                'area' => 'societies', 'audience' => 'society', 'icon' => 'fund',
                'title' => 'Tea Shakthi programmes',
                'summary' => 'Member welfare, savings and enterprise programmes run with registered societies under the Tea Shakthi banner, including group input purchase and value-addition support.',
                'eligibility' => 'Registered Tea Smallholder Development Societies in good standing and their members.',
                'process' => [
                    'Your society applies through the Regional Office.',
                    'The programme officer assesses the society’s standing and proposal.',
                    'Approved programmes are scheduled and funds or inputs released.',
                ],
                'documents' => [
                    'Society registration certificate',
                    'Current member list',
                    'Programme proposal',
                ],
                'fee' => 'Varies by programme.',
                'duration' => 'By programme cycle.',
                'division' => 'Societies & Marketing',
                'contact' => 'The Regional Manager of your district office.',
            ],
            'hantana-training' => [
                'area' => 'training', 'audience' => 'smallholder', 'icon' => 'training',
                'title' => 'Training at the Hantana National Training Centre',
                'summary' => 'Residential and non-residential training programmes for smallholders, society office bearers and field staff, at the Authority’s national centre at Hantana, Kandy.',
                'eligibility' => 'Registered smallholders, society office bearers, and staff nominated by their division. Individual programmes may set their own criteria, which are stated with the programme.',
                'process' => [
                    'Choose a programme from the training calendar.',
                    'Apply online, or through your Regional Office.',
                    'The Centre confirms, waitlists or declines the application and notifies you.',
                    'Confirmed applicants receive joining instructions.',
                ],
                'documents' => [
                    'National Identity Card',
                    'Smallholder or society registration number, where applicable',
                ],
                'fee' => 'Stated against each programme in the calendar.',
                'duration' => 'By programme.',
                'division' => 'Extension & Training',
                'contact' => 'Hantana National Training Centre.',
            ],
            'nursery-registration' => [
                'area' => 'land', 'audience' => 'supplier', 'icon' => 'nursery',
                'title' => 'Tea nursery registration',
                'summary' => 'Registration and monitoring of commercial tea plant nurseries, so that planting material issued under subsidy comes from a source whose cultivars and standards have been verified.',
                'eligibility' => 'Nursery operators meeting the standards for site, mother bushes, cultivar purity and record keeping.',
                'process' => [
                    'Apply to the Regional Office covering the nursery site.',
                    'The nursery is inspected against the standard.',
                    'Registration is issued, and the nursery is monitored during the season.',
                ],
                'documents' => [
                    'Completed nursery registration application',
                    'Evidence of lawful possession of the site',
                    'Cultivar and mother-bush records',
                ],
                'fee' => 'As stated in the current guidelines.',
                'duration' => 'Normally within 45 days of inspection.',
                'division' => 'Land Development & Extension Services',
                'contact' => 'The Regional Manager of your district office.',
            ],
            'right-to-information' => [
                'area' => 'general', 'audience' => 'smallholder', 'icon' => 'info',
                'title' => 'Right to Information request',
                'summary' => 'A request for information held by the Authority under the Right to Information Act No. 12 of 2016, made to the Information Officer at Head Office.',
                'eligibility' => 'Any citizen of Sri Lanka.',
                'process' => [
                    'Submit the RTI request form to the Information Officer.',
                    'An acknowledgement is issued with a reference number.',
                    'The information is released, or the reason for refusal given, within the statutory period.',
                    'An appeal lies to the Designated Officer, and thereafter to the RTI Commission.',
                ],
                'documents' => [
                    'Completed RTI request form',
                    'Proof of citizenship where requested',
                ],
                'fee' => 'As prescribed under the Act; inspection of records is free.',
                'duration' => 'Within the statutory period of 14 days, extendable as the Act provides.',
                'division' => 'Administration',
                'contact' => 'Information Officer, Head Office.',
            ],
            'careers-at-tshda' => [
                'area' => 'general', 'audience' => 'jobseeker', 'icon' => 'briefcase',
                'title' => 'Recruitment and vacancies',
                'summary' => 'Applications for advertised posts at the Authority, from Tea Inspector and Extension Officer ranges to management, administration and technical grades.',
                'eligibility' => 'As stated in the individual vacancy notice.',
                'process' => [
                    'Read the vacancy notice and the scheme of recruitment.',
                    'Download and complete the application form.',
                    'Submit it by the closing date stated on the notice.',
                    'Shortlisted applicants are called for interview or examination.',
                ],
                'documents' => [
                    'Completed application form',
                    'Certified copies of educational and professional certificates',
                    'National Identity Card',
                    'Birth certificate',
                ],
                'fee' => 'As stated in the vacancy notice.',
                'duration' => 'By the recruitment schedule in the notice.',
                'division' => 'Administration',
                'contact' => 'Administration Division, Head Office.',
            ],
        ];
    }
}
