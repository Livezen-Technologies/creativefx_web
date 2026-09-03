<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Careers\Models\JobModel;

/**
 * Vacancy listings (Clause 3.9 G): open positions with closing dates,
 * automatic expiry and the application form to download.
 *
 * Expiry is the model's job, not an editor's: a vacancy whose closing date has
 * passed drops off this page on the day, so the site never advertises a post
 * that can no longer be applied for.
 */
class Vacancies extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        return view('Modules\Tshda\Views\vacancies', [
            'jobs'            => (new JobModel())->openJobs(),
            'title'           => lang('Site.vacancies.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.vacancies.meta'),
        ]);
    }
}
