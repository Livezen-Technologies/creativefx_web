<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

/**
 * Enquiries: corporate quote requests, contact messages, newsletter sign-ups,
 * resource downloads, waitlist requests and "please run this on a date I can
 * make".
 *
 * Its own table rather than the platform's generic contact messages, because a
 * corporate enquiry carries team size, courses of interest, preferred dates and
 * a budget band, and it is worked by a person against a response time. Flatten
 * that into a `message` column and the sales process loses the fields it is
 * built on.
 *
 * utm_json is captured at submission. Cost per enrolment by channel is a launch
 * metric, and attribution that is not recorded at the moment of the enquiry
 * cannot be reconstructed afterwards.
 */
class LeadModel extends Model
{
    protected $table         = 'training_leads';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'type', 'name', 'email', 'phone', 'company', 'country', 'team_size',
        'courses_json', 'preferred_dates', 'mode', 'location', 'budget_band',
        'message', 'payload_json', 'source', 'utm_json', 'status', 'assigned_to',
    ];

    /**
     * The UTM parameters on the request that produced this enquiry.
     *
     * Read from the query string first and from a first-touch cookie second, so
     * an enquiry made three pages after the advert is still attributed to it.
     */
    public static function utm(): string
    {
        $request = service('request');
        $out     = [];

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'] as $key) {
            $value = $request->getGet($key);
            if (is_string($value) && $value !== '') {
                $out[$key] = mb_substr($value, 0, 128);
            }
        }

        if ($out === []) {
            $stored = json_decode((string) ($request->getCookie('mlp_utm') ?? ''), true);
            if (is_array($stored)) {
                $out = $stored;
            }
        }

        $referrer = $request->getServer('HTTP_REFERER');
        if (is_string($referrer) && $referrer !== '') {
            $out['referrer'] = mb_substr($referrer, 0, 255);
        }

        return json_encode($out, JSON_UNESCAPED_SLASHES);
    }
}
