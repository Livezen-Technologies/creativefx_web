<?php

namespace Modules\Showroom\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Showroom\Models\ShowroomCategoryModel;

class Showroom extends BaseController
{
    public function index(?string $locale = null)
    {
        return view('Modules\Showroom\Views\index', [
            'title'      => 'Virtual Showroom',
            'categories' => (new ShowroomCategoryModel())->published(),
        ]);
    }

    public function scene(?string $locale = null, ?string $slug = null)
    {
        $category = (new ShowroomCategoryModel())->findWithProducts((string) $slug);
        if ($category === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Distinct materials across this category's products → filter chips.
        $materials = [];
        foreach ($category['products'] as $product) {
            foreach (json_decode($product['materials'] ?? '[]', true) ?: [] as $m) {
                $materials[$m] = true;
            }
        }

        return view('Modules\Showroom\Views\scene', [
            'title'     => 'Showroom',
            'category'  => $category,
            'materials' => array_keys($materials),
        ]);
    }
}
