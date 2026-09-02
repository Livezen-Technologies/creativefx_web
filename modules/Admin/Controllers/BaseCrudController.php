<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Generic admin CRUD. Subclasses declare a model + field config and get
 * list / create / edit / update / delete for free, rendered by the shared
 * crud/index and crud/form views.
 *
 * Field config entry:
 *   ['name','label','type','rules'?,'options'?,'help'?,'readonly'?]
 *   type ∈ text|textarea|email|number|select|checkbox|locale|static
 *   'locale' stores a JSON locale-map ({"en":..,"ja":..,"es":..,"zh":..}).
 */
abstract class BaseCrudController extends BaseController
{
    protected string $modelClass;
    protected string $title;      // e.g. "Pages"
    protected string $singular;   // e.g. "Page"
    protected string $route;      // admin segment, e.g. "pages"
    protected string $active;     // sidebar key
    protected array $fields = [];
    protected array $listColumns = [];
    protected bool $canCreate = true;
    protected bool $canDelete = true;
    protected string $orderBy = 'id';
    protected string $orderDir = 'ASC';
    /** @var list<array{label:string,path:string}> extra per-row links ({id} placeholder) */
    protected array $extraActions = [];

    /** @return list<string> */
    protected function locales(): array
    {
        return config('App')->supportedLocales;
    }

    protected function model()
    {
        return model($this->modelClass);
    }

    public function index()
    {
        $rows = $this->model()->orderBy($this->orderBy, $this->orderDir)->findAll();

        return view('Modules\Admin\Views\crud\index', [
            'title'       => $this->title,
            'active'      => $this->active,
            'route'       => $this->route,
            'singular'    => $this->singular,
            'rows'        => $rows,
            'listColumns'  => $this->listColumns,
            'canCreate'    => $this->canCreate,
            'canDelete'    => $this->canDelete,
            'extraActions' => $this->extraActions,
        ]);
    }

    public function create()
    {
        return $this->renderForm(null);
    }

    public function edit($id)
    {
        $row = $this->model()->find($id);
        if ($row === null) {
            return redirect()->to(site_url('admin/' . $this->route))->with('error', $this->singular . ' not found.');
        }
        return $this->renderForm($row);
    }

    protected function renderForm(?array $row)
    {
        return view('Modules\Admin\Views\crud\form', [
            'title'    => ($row ? 'Edit ' : 'New ') . $this->singular,
            'active'   => $this->active,
            'route'    => $this->route,
            'singular' => $this->singular,
            'fields'   => $this->fields,
            'row'      => $row,
            'locales'  => $this->locales(),
        ]);
    }

    public function store()
    {
        return $this->persist(null);
    }

    public function update($id)
    {
        return $this->persist($id);
    }

    protected function persist($id)
    {
        $rules = $this->rules();
        if ($rules !== [] && ! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data  = $this->collect();
        $model = $this->model();

        if ($id) {
            $model->update($id, $data);
        } else {
            $model->insert($data);
        }

        return redirect()->to(site_url('admin/' . $this->route))->with('message', $this->singular . ' saved.');
    }

    public function delete($id)
    {
        if ($this->canDelete) {
            $this->model()->delete($id);
        }
        return redirect()->to(site_url('admin/' . $this->route))->with('message', $this->singular . ' deleted.');
    }

    protected function rules(): array
    {
        $rules = [];
        foreach ($this->fields as $f) {
            if (! empty($f['rules'])) {
                $rules[$f['name']] = $f['rules'];
            }
        }
        return $rules;
    }

    protected function collect(): array
    {
        $data = [];
        foreach ($this->fields as $f) {
            if (! empty($f['readonly']) || ($f['type'] ?? 'text') === 'static') {
                continue;
            }
            $name = $f['name'];
            $type = $f['type'] ?? 'text';

            if ($type === 'locale' || $type === 'locale_textarea' || $type === 'locale_richtext') {
                $map = [];
                foreach ($this->locales() as $l) {
                    $v = $this->request->getPost($name . '_' . $l);
                    if ($v !== null && $v !== '') {
                        $map[$l] = $v;
                    }
                }
                $data[$name] = json_encode($map, JSON_UNESCAPED_UNICODE);
            } elseif ($type === 'checkbox') {
                $data[$name] = $this->request->getPost($name) ? 1 : 0;
            } else {
                $data[$name] = $this->request->getPost($name);
            }
        }
        return $data;
    }
}
