<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteDocument;
use App\Services\SiteMedia;
use App\Support\AdminModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * List, create, edit, delete and status toggle for every module defined in config/admin.php.
 * Each module is authorized with its own permission (super admin always passes).
 */
class ModuleController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request, string $module): View
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        $base = $this->baseQuery($def);
        $query = (clone $base);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '' && ! empty($def['search'])) {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function (Builder $q) use ($def, $like) {
                foreach ($def['search'] as $i => $column) {
                    $i === 0 ? $q->where($column, 'like', $like) : $q->orWhere($column, 'like', $like);
                }
            });
        }

        $status = $request->query('status');
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        [$column, $direction] = $def['order'] ?? ['id', 'desc'];
        $query->orderBy($column, $direction)->orderBy('id', $direction);

        return view('admin.modules.index', [
            'def' => $def,
            'key' => $module,
            'items' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'counts' => [
                'all' => (clone $base)->count(),
                'active' => (clone $base)->where('status', 'active')->count(),
                'inactive' => (clone $base)->where('status', 'inactive')->count(),
            ],
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(string $module): View
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        $model = $def['model'];

        return view('admin.modules.form', [
            'def' => $def,
            'key' => $module,
            'item' => new $model(['status' => 'active']),
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        $validated = $request->validate(AdminModules::rules($def, true));
        [$attributes] = AdminModules::attributes($def, $validated, $request);
        $attributes = AdminModules::applyFilter($def, $attributes);

        $model = $def['model'];
        $item = new $model();
        $item->fill($this->onlyFillable($item, $attributes));
        $item->save();

        return redirect()->route('modules.index', $module)
            ->with('success', ucfirst(AdminModules::typeLabel($def)) . ' added.');
    }

    public function edit(string $module, int $id): View
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        return view('admin.modules.form', [
            'def' => $def,
            'key' => $module,
            'item' => $this->findOrFail($def, $id),
        ]);
    }

    public function update(Request $request, string $module, int $id): RedirectResponse
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        $item = $this->findOrFail($def, $id);

        $validated = $request->validate(AdminModules::rules($def, false, $item));
        [$attributes, $replaced] = AdminModules::attributes($def, $validated, $request, $item);

        $item->fill($this->onlyFillable($item, $attributes))->save();
        $this->deleteFiles($def, $replaced);

        return redirect()->route('modules.index', $module)
            ->with('success', ucfirst(AdminModules::typeLabel($def)) . ' updated.');
    }

    public function destroy(string $module, int $id): RedirectResponse
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        $item = $this->findOrFail($def, $id);
        $files = array_map(fn ($f) => $item->{AdminModules::column($f)}, AdminModules::uploadFields($def));

        $item->delete();
        $this->deleteFiles($def, $files, $module);

        return redirect()->route('modules.index', $module)
            ->with('success', ucfirst(AdminModules::typeLabel($def)) . ' deleted.');
    }

    public function toggle(string $module, int $id): RedirectResponse
    {
        $def = AdminModules::definition($module);
        AdminModules::authorize($def);

        $item = $this->findOrFail($def, $id);
        $item->status = $item->status === 'active' ? 'inactive' : 'active';
        $item->save();

        return back()->with('success', 'Status changed to ' . $item->status . '.');
    }

    private function baseQuery(array $def): Builder
    {
        $model = $def['model'];
        $query = $model::query();

        foreach ($def['filter'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    private function findOrFail(array $def, int $id)
    {
        return $this->baseQuery($def)->whereKey($id)->firstOrFail();
    }

    /** Drops attributes the model does not allow to be mass-assigned (for example original_name). */
    private function onlyFillable($item, array $attributes): array
    {
        return array_intersect_key($attributes, array_flip($item->getFillable()));
    }

    private function deleteFiles(array $def, array $paths, ?string $module = null): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths === []) {
            return;
        }

        // Image files and PDFs live on different disks/folders; sort them by their folder.
        $documents = array_values(array_filter($paths, fn ($p) => Str::startsWith($p, SiteDocument::FOLDER . '/')));
        $media = array_values(array_diff($paths, $documents));

        SiteMedia::delete($media);
        SiteDocument::delete($documents);
    }
}
