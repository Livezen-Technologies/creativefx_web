<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Tshda\Models\StatisticModel;

/**
 * Published statistics (Clause 3.9 F): charts, tables and downloadable
 * datasets, all of it maintained through the CMS rather than redrawn by hand.
 */
class Statistics extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        return view('Modules\Tshda\Views\statistics\index', [
            'datasets'        => (new StatisticModel())->live(),
            'title'           => lang('Site.statistics.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.statistics.meta'),
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $dataset = (new StatisticModel())->findLive((string) $slug);
        if ($dataset === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$columns, $rows] = StatisticModel::decode($dataset);

        return view('Modules\Tshda\Views\statistics\show', [
            'dataset'         => $dataset,
            'columns'         => $columns,
            'rows'            => $rows,
            'title'           => t_field($dataset['title']) . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($dataset['description']),
        ]);
    }

    /**
     * The same dataset as a spreadsheet. Clause 3.9 F asks for downloadable
     * datasets, and a CSV is the form every office already has something that
     * opens — no licence, no plug-in, no conversion.
     */
    public function csv(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $dataset = (new StatisticModel())->findLive((string) $slug);
        if ($dataset === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        [$columns, $rows] = StatisticModel::decode($dataset);

        $out = fopen('php://temp', 'r+');
        // A UTF-8 byte-order mark, so that Excel opens Sinhala and Tamil column
        // headings as Sinhala and Tamil rather than as mojibake. Without it the
        // trilingual export is unreadable in the one program most offices use.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_map(static fn ($c) => t_field($c), $columns));
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn ($cell) => $cell === null ? '' : (string) $cell, $row));
        }
        rewind($out);
        $body = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $dataset['slug'] . '.csv"')
            ->setBody($body);
    }
}
