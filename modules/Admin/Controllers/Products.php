<?php

namespace Modules\Admin\Controllers;

class Products extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\ProductModel';
    protected string $title      = 'Products';
    protected string $singular   = 'Product';
    protected string $route      = 'products';
    protected string $active     = 'products';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'sku', 'label' => 'SKU'],
        ['name' => 'label', 'label' => 'Label'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => []],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'URL segment, e.g. denim-pinafore-set'],
        ['name' => 'sku', 'label' => 'SKU'],
        ['name' => 'code', 'label' => 'Product code'],
        ['name' => 'collection', 'label' => 'Collection', 'help' => 'e.g. Kidswear Denim'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'short_description', 'label' => 'Short description', 'type' => 'locale_textarea', 'help' => 'Shown on cards and at the top of the product page'],
        ['name' => 'description', 'label' => 'Full description', 'type' => 'locale_richtext'],
        ['name' => 'features', 'label' => 'Features', 'type' => 'list', 'item_label' => 'feature'],
        ['name' => 'applications', 'label' => 'Applications', 'type' => 'list', 'item_label' => 'application'],
        ['name' => 'specs', 'label' => 'Specifications', 'type' => 'pairs', 'item_label' => 'spec', 'pair_labels' => ['Label (e.g. Fabric)', 'Value (e.g. Organic cotton)']],
        ['name' => 'hero_image', 'label' => 'Featured image', 'type' => 'image', 'folder' => 'products', 'help' => 'Drop a file, or choose one from the media library'],
        ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'gallery', 'folder' => 'products', 'help' => 'Add from the media library or upload — drag order = display order'],
        ['name' => 'model_path', 'label' => '3D model (GLB/GLTF)', 'type' => 'file', 'accept' => '.glb,.gltf', 'folder' => 'models', 'help' => 'Enables the interactive 3D viewer'],
        ['name' => 'brochure_path', 'label' => 'Datasheet / brochure', 'type' => 'file', 'accept' => '.pdf', 'folder' => 'brochures', 'help' => 'PDF offered for download on the product page'],
        ['name' => 'certifications', 'label' => 'Certifications', 'help' => 'Comma-separated, e.g. GOTS,GRS,SEDEX,HIGG'],
        ['name' => 'label', 'label' => 'Merchandising label', 'type' => 'select', 'options' => ['' => '— none —', 'new' => 'New arrival', 'bestseller' => 'Bestseller', 'popular' => 'Popular']],
        ['name' => 'is_featured', 'label' => 'Featured product', 'type' => 'checkbox', 'help' => 'Shown first and in the Featured filter / product_grid block'],
        ['name' => 'meta_title', 'label' => 'SEO meta title', 'type' => 'locale'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
    ];

    protected function renderForm(?array $row)
    {
        helper('norlanka');

        // Category options are data-driven, so they're resolved per request.
        foreach ($this->fields as &$field) {
            if ($field['name'] === 'category_id') {
                $options = [];
                foreach (model('Modules\Catalog\Models\ProductCategoryModel')->orderBy('sort_order')->findAll() as $cat) {
                    $options[$cat['id']] = t_field($cat['name'], 'en') . ' (' . $cat['slug'] . ')';
                }
                $field['options'] = $options;
            }
        }
        unset($field);

        return parent::renderForm($row);
    }

    protected function collect(): array
    {
        $data = parent::collect();
        $data['category_id'] = (int) $data['category_id'];
        $data['label']       = $data['label'] !== '' ? $data['label'] : null;

        // Validate JSON fields so a typo can't break the public pages.
        foreach (['features', 'applications', 'specs', 'gallery'] as $jsonField) {
            $raw = trim((string) ($data[$jsonField] ?? ''));
            if ($raw === '') {
                $data[$jsonField] = null;
                continue;
            }
            $decoded = json_decode($raw, true);
            $data[$jsonField] = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : null;
        }
        return $data;
    }
}
