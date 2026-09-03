<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Models\SubscriberModel;

/**
 * Alert subscribers (Clause 3.13): list management, delivery statistics and
 * bounce handling.
 *
 * There is deliberately no way to add a subscriber from here. Every address on
 * this list proved itself by following a confirmation link, and an address
 * typed in by an officer has not — adding one would put the Authority in the
 * position of mailing people who never asked.
 */
class Subscribers extends BaseController
{
    public function index()
    {
        helper('norlanka');

        $status = trim((string) $this->request->getGet('status'));
        $model  = new SubscriberModel();

        if ($status !== '') {
            $model->where('status', $status);
        }

        return view('Modules\Admin\Views\subscribers', [
            'title'       => 'Alert subscribers',
            'active'      => 'subscribers',
            'subscribers' => $model->orderBy('created_at', 'DESC')->findAll(500),
            'status'      => $status,
            'stats'       => (new SubscriberModel())->stats(),
            'topics'      => SubscriberModel::TOPICS,
        ]);
    }

    /** Export the confirmed list, for the Authority's own records. */
    public function export()
    {
        $rows = (new SubscriberModel())->where('status', 'active')->orderBy('email', 'ASC')->findAll();

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Email', 'Name', 'Language', 'Topics', 'Confirmed', 'Last sent']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['email'], $row['name'], $row['locale'], $row['topics'],
                $row['confirmed_at'], $row['last_sent_at'],
            ]);
        }
        rewind($out);
        $body = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="subscribers-' . date('Y-m-d') . '.csv"')
            ->setBody($body);
    }

    public function delete(int $id)
    {
        (new SubscriberModel())->delete($id);

        return redirect()->back()->with('message', 'Subscriber removed.');
    }
}
