<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Page builder: per-page section/block structure editor. Sections and blocks
 * can be added, reordered, shown/hidden and deleted; each block's locale-aware
 * `content` JSON is edited inline. Block types are discovered from the block
 * partial library (modules/Site/Views/cms/blocks).
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
            'title'      => 'Page builder',
            'active'     => 'pages',
            'page'       => $this->withAllStructure((int) $pageId, $page),
            'blockTypes' => $this->blockTypes(),
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
            $saved = true;
        }

        if (! empty($saved)) {
            $this->claimPage((int) $pageId);
        }

        $redirect = redirect()->to(site_url('admin/pages/' . $pageId . '/content'));
        return $bad === []
            ? $redirect->with('message', 'Content updated.')
            : $redirect->with('error', 'Some blocks had invalid JSON and were skipped: #' . implode(', #', $bad));
    }

    /**
     * Hand a page back to the seeder and take its shipped content again.
     *
     * The custom flag has to be reversible or it is a one-way door: an
     * administrator who tries the builder once, changes their mind, and wants
     * the designed page back would otherwise have no way to ask for it, and the
     * page would sit frozen at whatever they left it as through every future
     * release.
     *
     * Destructive and says so — the confirmation is on the button.
     */
    public function resetToDefault($pageId)
    {
        $pageModel = model('Modules\Cms\Models\PageModel');
        $page      = $pageModel->find((int) $pageId);
        if ($page === null) {
            return redirect()->to(site_url('admin/pages'))->with('error', 'Page not found.');
        }

        // Clearing the flag first is what lets the seeder rewrite the page; it
        // skips any page still marked as somebody's own.
        $pageModel->update((int) $pageId, ['is_custom' => 0]);

        $seeder = \Config\Database::seeder();
        $seeder->call((int) ($page['is_home'] ?? 0) === 1 || ($page['template'] ?? '') === 'home'
            ? 'Modules\Cms\Database\Seeds\HomeContentSeeder'
            : 'Modules\Cms\Database\Seeds\CorporateContentSeeder');

        return $this->backToBuilder((int) $pageId, 'Page reset to its shipped content.');
    }

    // ---- Sections -----------------------------------------------------------

    public function addSection($pageId)
    {
        $type = preg_replace('/[^a-z0-9_-]/i', '', (string) $this->request->getPost('type')) ?: 'generic';
        $key  = preg_replace('/[^a-z0-9_-]/i', '', (string) $this->request->getPost('key')) ?: $type;
        $max  = model('Modules\Cms\Models\PageSectionModel')->where('page_id', (int) $pageId)->selectMax('sort_order')->first();

        model('Modules\Cms\Models\PageSectionModel')->insert([
            'page_id'    => (int) $pageId,
            'key'        => $key,
            'type'       => $type,
            'sort_order' => (int) ($max['sort_order'] ?? 0) + 1,
            'status'     => 'published',
        ]);

        $this->claimPage((int) $pageId);

        return redirect()->to(site_url('admin/pages/' . $pageId . '/content'))->with('message', 'Section added.');
    }

    public function moveSection($sectionId)
    {
        return $this->move('Modules\Cms\Models\PageSectionModel', (int) $sectionId, 'page_id');
    }

    public function toggleSection($sectionId)
    {
        return $this->toggle('Modules\Cms\Models\PageSectionModel', (int) $sectionId);
    }

    public function deleteSection($sectionId)
    {
        $model = model('Modules\Cms\Models\PageSectionModel');
        $row   = $model->find((int) $sectionId);
        if ($row !== null) {
            model('Modules\Cms\Models\PageBlockModel')->where('section_id', (int) $sectionId)->delete();
            $model->delete((int) $sectionId);
            $this->claimPage((int) $row['page_id']);
        }
        return $this->backToBuilder($row['page_id'] ?? null, 'Section deleted.');
    }

    // ---- Blocks -------------------------------------------------------------

    public function addBlock($sectionId)
    {
        $section = model('Modules\Cms\Models\PageSectionModel')->find((int) $sectionId);
        if ($section === null) {
            return redirect()->to(site_url('admin/pages'))->with('error', 'Section not found.');
        }
        $type = preg_replace('/[^a-z0-9_]/i', '', (string) $this->request->getPost('type'));
        if (! in_array($type, $this->blockTypes(), true)) {
            return $this->backToBuilder((int) $section['page_id'], null, 'Unknown block type.');
        }
        $max = model('Modules\Cms\Models\PageBlockModel')->where('section_id', (int) $sectionId)->selectMax('sort_order')->first();

        model('Modules\Cms\Models\PageBlockModel')->insert([
            'section_id' => (int) $sectionId,
            'type'       => $type,
            'content'    => json_encode(['title' => ['en' => '']], JSON_UNESCAPED_UNICODE),
            'sort_order' => (int) ($max['sort_order'] ?? 0) + 1,
            'status'     => 'published',
        ]);

        $this->claimPage((int) $section['page_id']);

        return $this->backToBuilder((int) $section['page_id'], ucfirst($type) . ' block added — fill in its content below.');
    }

    public function moveBlock($blockId)
    {
        return $this->move('Modules\Cms\Models\PageBlockModel', (int) $blockId, 'section_id');
    }

    public function toggleBlock($blockId)
    {
        return $this->toggle('Modules\Cms\Models\PageBlockModel', (int) $blockId);
    }

    public function deleteBlock($blockId)
    {
        $model  = model('Modules\Cms\Models\PageBlockModel');
        $row    = $model->find((int) $blockId);
        $pageId = $row !== null ? $this->pageIdOf($row) : null;
        if ($row !== null) {
            $model->delete((int) $blockId);
            $this->claimPage($pageId);
        }
        return $this->backToBuilder($pageId, 'Block deleted.');
    }

    // ---- Shared helpers -----------------------------------------------------

    /** Swap sort_order with the neighbour above/below within the same parent. */
    private function move(string $modelClass, int $id, string $parentKey)
    {
        $model = model($modelClass);
        $row   = $model->find($id);
        if ($row === null) {
            return redirect()->to(site_url('admin/pages'))->with('error', 'Item not found.');
        }
        $dir = $this->request->getPost('dir') === 'up' ? 'up' : 'down';

        $neighbour = $model->where($parentKey, $row[$parentKey])
            ->where('sort_order ' . ($dir === 'up' ? '<' : '>'), $row['sort_order'])
            ->orderBy('sort_order', $dir === 'up' ? 'DESC' : 'ASC')
            ->first();

        if ($neighbour !== null) {
            $model->update($id, ['sort_order' => $neighbour['sort_order']]);
            $model->update($neighbour['id'], ['sort_order' => $row['sort_order']]);
            $this->claimPage($this->pageIdOf($row));
        }

        return $this->backToBuilder($this->pageIdOf($row));
    }

    private function toggle(string $modelClass, int $id)
    {
        $model = model($modelClass);
        $row   = $model->find($id);
        if ($row === null) {
            return redirect()->to(site_url('admin/pages'))->with('error', 'Item not found.');
        }
        $model->update($id, ['status' => $row['status'] === 'published' ? 'draft' : 'published']);
        $this->claimPage($this->pageIdOf($row));

        return $this->backToBuilder($this->pageIdOf($row));
    }

    /**
     * Record that this page's content is now an administrator's, not the
     * seeder's.
     *
     * The content seeders delete every section and block belonging to their
     * pages and write them again on each deploy. Without this flag an edit made
     * here survives until the next release and then disappears — no error, no
     * record of what it was. Set on the first change of any kind, because the
     * seeder cannot preserve half a page: it either owns the structure or it
     * leaves it alone.
     */
    private function claimPage(?int $pageId): void
    {
        if ($pageId === null) {
            return;
        }
        $model = model('Modules\Cms\Models\PageModel');
        $page  = $model->find($pageId);
        if ($page !== null && (int) ($page['is_custom'] ?? 0) !== 1) {
            $model->update($pageId, ['is_custom' => 1]);
        }
    }

    /** Resolve the owning page id for a section- or block-row. */
    private function pageIdOf(array $row): ?int
    {
        if (isset($row['page_id'])) {
            return (int) $row['page_id'];
        }
        if (isset($row['section_id'])) {
            $section = model('Modules\Cms\Models\PageSectionModel')->find((int) $row['section_id']);
            return $section !== null ? (int) $section['page_id'] : null;
        }
        return null;
    }

    private function backToBuilder(?int $pageId, ?string $message = null, ?string $error = null)
    {
        $redirect = redirect()->to($pageId !== null ? site_url('admin/pages/' . $pageId . '/content') : site_url('admin/pages'));
        if ($message !== null) {
            $redirect = $redirect->with('message', $message);
        }
        if ($error !== null) {
            $redirect = $redirect->with('error', $error);
        }
        return $redirect;
    }

    /** Available block types = the block partial library on disk. */
    private function blockTypes(): array
    {
        $types = [];
        foreach (glob(ROOTPATH . 'modules/Site/Views/cms/blocks/*.php') ?: [] as $file) {
            $types[] = basename($file, '.php');
        }
        sort($types);
        return $types;
    }

    /**
     * Like PageModel::withStructure() but INCLUDING drafts, so the builder can
     * show and re-enable hidden sections/blocks.
     */
    private function withAllStructure(int $pageId, array $page): array
    {
        $sections = model('Modules\Cms\Models\PageSectionModel')
            ->where('page_id', $pageId)->orderBy('sort_order', 'ASC')->findAll();
        foreach ($sections as &$section) {
            $section['blocks'] = model('Modules\Cms\Models\PageBlockModel')
                ->where('section_id', $section['id'])->orderBy('sort_order', 'ASC')->findAll();
        }
        unset($section);
        $page['sections'] = $sections;

        return $page;
    }
}
