<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Core\Libraries\Recaptcha;
use Modules\Tshda\Libraries\Reference;
use Modules\Tshda\Models\DiscussionCommentModel;
use Modules\Tshda\Models\DiscussionTopicModel;

/**
 * The moderated discussion forum (Clause 3.9 B.V and Clause 3.14): threads
 * opened and closed by the Authority, comments held for pre-publication
 * moderation, CAPTCHA on the form.
 */
class Discussion extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        return view('Modules\Tshda\Views\discussion\index', [
            'topics'          => (new DiscussionTopicModel())->live(),
            'title'           => lang('Site.discussion.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.discussion.meta'),
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $topic = (new DiscussionTopicModel())->findLive((string) $slug);
        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('Modules\Tshda\Views\discussion\show', [
            'topic'           => $topic,
            'comments'        => (new DiscussionCommentModel())->approved((int) $topic['id']),
            'open'            => DiscussionTopicModel::isOpen($topic),
            'title'           => t_field($topic['title']) . ' — ' . setting('site_name', ''),
            'metaDescription' => mb_substr(strip_tags(t_field($topic['body'])), 0, 200),
        ]);
    }

    public function comment(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $topic = (new DiscussionTopicModel())->findLive((string) $slug);
        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to(locale_url('discussion/' . $slug));
        }

        if (! DiscussionTopicModel::isOpen($topic)) {
            return redirect()->back()->withInput()->with('error', lang('Site.discussion.err_closed'));
        }

        $rules = [
            'author' => 'required|min_length[2]|max_length[128]',
            'email'  => 'permit_empty|valid_email|max_length[128]',
            'body'   => 'required|min_length[10]|max_length[2000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (Recaptcha::guards('contact')) {
            $check = Recaptcha::verify($this->request->getPost('recaptcha_token'), 'comment');
            if (! $check['ok']) {
                log_message('warning', 'Comment refused by reCAPTCHA: ' . $check['reason']);

                return redirect()->back()->withInput()->with('error', lang('Site.booking.err_robot'));
            }
        }

        (new DiscussionCommentModel())->insert([
            'topic_id' => (int) $topic['id'],
            'author'   => trim((string) $this->request->getPost('author')),
            'email'    => trim((string) $this->request->getPost('email')),
            'district' => trim((string) $this->request->getPost('district')),
            'body'     => trim((string) $this->request->getPost('body')),
            'locale'   => current_locale(),
            // Nothing appears until a moderator releases it. The visitor is
            // told that plainly rather than left wondering why their comment
            // is not on the page.
            'status'   => 'pending',
            'ip_hash'  => Reference::ipHash($this->request->getIPAddress()),
        ]);

        return redirect()->to(locale_url('discussion/' . $slug))->with('comment_ok', '1');
    }
}
