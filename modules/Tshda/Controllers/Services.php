<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Tshda\Models\ServiceModel;

/**
 * The service catalogue (Clause 3.9 D): every service the Authority provides,
 * each with its eligibility, process, forms, responsible division and contact
 * point.
 */
class Services extends BaseController
{
    /** The stakeholder clusters, in the order the site presents them. */
    public const AUDIENCES = ['smallholder', 'society', 'supplier', 'officer', 'jobseeker'];

    /** The functional areas of Clause 3.9 E. */
    public const AREAS = ['land', 'societies', 'training', 'general'];

    public function index(?string $locale = null)
    {
        helper('norlanka');

        $model = new ServiceModel();

        $area     = trim((string) $this->request->getGet('area'));
        $audience = trim((string) $this->request->getGet('for'));
        $query    = trim((string) $this->request->getGet('q'));

        // An unknown filter is a 404 rather than a silently ignored parameter:
        // /services?area=nonsense returning the full list looks like a working
        // filter that found everything.
        if ($area !== '' && ! in_array($area, self::AREAS, true)) {
            throw PageNotFoundException::forPageNotFound();
        }
        if ($audience !== '' && ! in_array($audience, self::AUDIENCES, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rows = $model->live();
        if ($area !== '') {
            $rows->where('area', $area);
        }
        if ($audience !== '') {
            $rows->where('audience', $audience);
        }
        if ($query !== '') {
            $rows->groupStart()->like('title', $query)->orLike('summary', $query)->groupEnd();
        }

        return view('Modules\Tshda\Views\services\index', [
            'services'        => $rows->findAll(),
            'area'            => $area,
            'audience'        => $audience,
            'query'           => $query,
            'title'           => lang('Site.services.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.services.meta'),
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $service = (new ServiceModel())->findLive((string) $slug);
        if ($service === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $related = (new ServiceModel())->live()
            ->where('area', $service['area'])
            ->where('id !=', (int) $service['id'])
            ->findAll(3);

        return view('Modules\Tshda\Views\services\show', [
            'service'         => $service,
            'related'         => $related,
            'title'           => t_field($service['title']) . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($service['summary']),
        ]);
    }
}
