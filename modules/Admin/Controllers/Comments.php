<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Models\DiscussionCommentModel;
use Modules\Tshda\Models\DiscussionTopicModel;

/**
 * The comment moderation queue (Clause 3.14).
 *
 * Nothing a member of the public writes appears on the site until a moderator
 * releases it, so this screen is the gate. Rejecting keeps the record — the
 * Authority should be able to say what it did not publish and why, and a
 * deleted row cannot answer that.
 */
class Comments extends BaseController
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    public function index()
    {
        helper('norlanka');

        $status = trim((string) $this->request->getGet('status'));
        if ($status === '' && $this->request->getGet('status') === null) {
            // The queue opens on what needs doing, not on everything.
            $status = 'pending';
        }

        $model = new DiscussionCommentModel();

        return view('Modules\Admin\Views\comments', [
            'title'    => 'Comment moderation',
            'active'   => 'discussion',
            'comments' => $model->moderationQueue($status ?: null),
            'topics'   => (new DiscussionTopicModel())->findAll(),
            'status'   => $status,
            'statuses' => self::STATUSES,
            'counts'   => [
                'pending'  => (new DiscussionCommentModel())->where('status', 'pending')->countAllResults(),
                'approved' => (new DiscussionCommentModel())->where('status', 'approved')->countAllResults(),
                'rejected' => (new DiscussionCommentModel())->where('status', 'rejected')->countAllResults(),
            ],
        ]);
    }

    public function update(int $id)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->with('error', 'Unknown status.');
        }

        $model = new DiscussionCommentModel();
        if ($model->find($id) === null) {
            return redirect()->back()->with('error', 'That comment no longer exists.');
        }

        $model->update($id, [
            'status'       => $status,
            'moderated_by' => session()->get('admin_user')['id'] ?? null,
            'moderated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('message', 'Comment ' . $status . '.');
    }

    public function delete(int $id)
    {
        (new DiscussionCommentModel())->delete($id);

        return redirect()->back()->with('message', 'Comment deleted.');
    }
}
