<?php

namespace Modules\Tshda\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The Authority's offices, its senior management and the field structure —
 * Clause 3.9 E.d and J.b.
 *
 * Insert-only, keyed on the slug. Once the Authority's own ICT officer has
 * corrected a telephone number or moved an officer between ranges, that is the
 * record; a release has nothing useful to say about it and must not overwrite
 * it. Rows the seeder has never written are added, so a later release can ship
 * a new office without disturbing the ones already there.
 *
 * The office coordinates are the published town coordinates, not surveyed door
 * positions: they put each pin in the right place on the map at national zoom
 * and are meant to be corrected office by office in the console. Staff names
 * below are the post, not a person — TSHDA supplies the individuals during
 * content gathering, and inventing them here would put made-up officials on a
 * government contact page.
 */
class DirectorySeeder extends Seeder
{
    private const OFFICES = [
        // slug, name, kind, district, province, address, phone, lat, lng
        ['head-office', 'Head Office — Battaramulla', 'head', 'Colombo', 'Western',
            'No. 70, Parliament Road, Pelawatte, Battaramulla', '+94 117 909 021', 6.8964, 79.9187],
        ['galle', 'Regional Office — Galle', 'regional', 'Galle', 'Southern',
            'Regional Office, Galle', '', 6.0535, 80.2210],
        ['matara', 'Regional Office — Matara', 'regional', 'Matara', 'Southern',
            'Regional Office, Matara', '', 5.9549, 80.5550],
        ['ratnapura', 'Regional Office — Ratnapura', 'regional', 'Ratnapura', 'Sabaragamuwa',
            'Regional Office, Ratnapura', '', 6.6828, 80.3992],
        ['kegalle', 'Regional Office — Kegalle', 'regional', 'Kegalle', 'Sabaragamuwa',
            'Regional Office, Kegalle', '', 7.2513, 80.3464],
        ['kalutara', 'Regional Office — Kalutara', 'regional', 'Kalutara', 'Western',
            'Regional Office, Kalutara', '', 6.5854, 79.9607],
        ['kandy', 'Regional Office — Kandy', 'regional', 'Kandy', 'Central',
            'Regional Office, Kandy', '', 7.2906, 80.6337],
        ['nuwara-eliya', 'Regional Office — Nuwara Eliya', 'regional', 'Nuwara Eliya', 'Central',
            'Regional Office, Nuwara Eliya', '', 6.9497, 80.7891],
        ['bandarawela', 'Regional Office — Bandarawela (Uva)', 'regional', 'Badulla', 'Uva',
            'Regional Office, Bandarawela', '', 6.8323, 80.9866],
        ['hantana', 'Hantana National Training Centre', 'centre', 'Kandy', 'Central',
            'Hantana National Training Centre, Hantana, Kandy', '', 7.2560, 80.6180],
    ];

    /** office slug, designation, division, subject area, senior */
    private const STAFF = [
        ['head-office', 'Chairman', 'Board & Chairman’s Office', 'Policy and governance', 1],
        ['head-office', 'Director General', 'Director General’s Office', 'Executive direction', 1],
        ['head-office', 'Deputy Director General (Development)', 'Development', 'Land development, extension and subsidies', 1],
        ['head-office', 'Deputy Director General (Administration)', 'Administration', 'Human resources, establishments and welfare', 1],
        ['head-office', 'Director (Extension & Training)', 'Extension & Training', 'Extension services and officer training', 1],
        ['head-office', 'Director (Societies & Marketing)', 'Societies & Marketing', 'Tea Shakthi, societies and marketing', 1],
        ['head-office', 'Chief Accountant', 'Finance', 'Budget, payments and subsidy disbursement', 1],
        ['head-office', 'Chief Internal Auditor', 'Internal Audit', 'Audit and compliance', 1],
        ['head-office', 'Information & Communication Technology Officer', 'ICT', 'Website, systems and data', 0],
        ['head-office', 'Information Officer (Right to Information)', 'Administration', 'Right to Information requests', 0],
        ['galle', 'Regional Manager', 'Regional Administration', 'Galle district smallholdings', 0],
        ['matara', 'Regional Manager', 'Regional Administration', 'Matara district smallholdings', 0],
        ['ratnapura', 'Regional Manager', 'Regional Administration', 'Ratnapura district smallholdings', 0],
        ['kegalle', 'Regional Manager', 'Regional Administration', 'Kegalle district smallholdings', 0],
        ['kalutara', 'Regional Manager', 'Regional Administration', 'Kalutara district smallholdings', 0],
        ['kandy', 'Regional Manager', 'Regional Administration', 'Kandy district smallholdings', 0],
        ['nuwara-eliya', 'Regional Manager', 'Regional Administration', 'Nuwara Eliya district smallholdings', 0],
        ['bandarawela', 'Regional Manager', 'Regional Administration', 'Uva province smallholdings', 0],
        ['hantana', 'Centre Manager', 'Extension & Training', 'Residential training programmes', 0],
    ];

    public function run(): void
    {
        helper('norlanka');
        $now = date('Y-m-d H:i:s');

        $officeIds = [];
        $order     = 0;

        foreach (self::OFFICES as [$slug, $name, $kind, $district, $province, $address, $phone, $lat, $lng]) {
            $existing = $this->db->table('offices')->where('slug', $slug)->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('offices')->insert([
                    'slug'       => $slug,
                    'name'       => json_encode(content_locales(['en' => $name]), JSON_UNESCAPED_UNICODE),
                    'kind'       => $kind,
                    'district'   => $district,
                    'province'   => $province,
                    'address'    => json_encode(content_locales(['en' => $address]), JSON_UNESCAPED_UNICODE),
                    'phone'      => $phone,
                    'latitude'   => $lat,
                    'longitude'  => $lng,
                    'sort_order' => $order,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $officeIds[$slug] = (int) $this->db->insertID();
            } else {
                $officeIds[$slug] = (int) $existing['id'];
            }
            $order++;
        }

        $staffOrder = 0;
        foreach (self::STAFF as [$officeSlug, $designation, $division, $subject, $senior]) {
            $officeId = $officeIds[$officeSlug] ?? null;
            if ($officeId === null) {
                continue;
            }

            // The post is the identity here, not a person's name: matching on
            // (office, designation) means a second run recognises the row it
            // wrote even after TSHDA has typed the officer's actual name into
            // it, so the name is never overwritten.
            $exists = $this->db->table('staff')
                ->where('office_id', $officeId)
                ->like('designation', $designation)
                ->countAllResults() > 0;

            if ($exists) {
                $staffOrder++;
                continue;
            }

            $this->db->table('staff')->insert([
                'office_id'    => $officeId,
                'name'         => 'To be confirmed',
                'designation'  => json_encode(content_locales(['en' => $designation]), JSON_UNESCAPED_UNICODE),
                'division'     => json_encode(content_locales(['en' => $division]), JSON_UNESCAPED_UNICODE),
                'subject_area' => json_encode(content_locales(['en' => $subject]), JSON_UNESCAPED_UNICODE),
                'is_senior'    => $senior,
                'sort_order'   => $staffOrder++,
                'status'       => 'published',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
