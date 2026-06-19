<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Block content editor for a page. Each block's locale-aware `content` JSON is
 * edited directly (a pragmatic editor; a full visual builder is a later step).
 */
class Content extends BaseController
{
    public function edit($pageId)
    {
        $pageModel = model('Modules\Cms\Models\PageModel');
        $page      = $pageModel->find($pageId);
        if ($page === null) {
            return redirect()->to(site_url('admin/pages'))->with('error', 'Page not found.');
        }

        return view('Modules\Admin\Views\content', [
            'title'  => 'Content',
            'active' => 'pages',
            'page'   => $pageModel->withStructure($page),
        ]);
    }

    public function update($pageId)
    {
        $blocks = $this->request->getPost('blocks') ?? [];
        $model  = model('Modules\Cms\Models\PageBlockModel');
        $bad    = [];

        foreach ($blocks as $blockId => $json) {
            $decoded = json_decode((string) $json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $bad[] = $blockId;
                continue;
            }
            $model->update((int) $blockId, ['content' => json_encode($decoded, JSON_UNESCAPED_UNICODE)]);
        }

        $redirect = redirect()->to(site_url('admin/pages/' . $pageId . '/content'));
        return $bad === []
            ? $redirect->with('message', 'Content updated.')
            : $redirect->with('error', 'Some blocks had invalid JSON and were skipped: #' . implode(', #', $bad));
    }
}
