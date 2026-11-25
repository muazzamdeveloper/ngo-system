<?php

namespace App\Http\Controllers;

use App\Models\Concerns\HasAdminContent;
use App\Support\AdminModules;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public list and detail pages for modules that set 'public' in config/admin.php.
 * Only active rows are shown.
 */
class PublicModuleController extends Controller
{
    public function index(Request $request, string $module): View
    {
        $def = $this->publicDefinition($module);

        $model = $def['model'];
        $query = $model::query()->where('status', 'active');

        foreach ($def['filter'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        [$column, $direction] = $def['order'] ?? ['id', 'desc'];

        return view('public.module.index', [
            'def' => $def,
            'key' => $module,
            'items' => $query->orderBy($column, $direction)->orderBy('id', $direction)->paginate(12),
        ]);
    }

    public function show(string $module, string $slug): View
    {
        $def = $this->publicDefinition($module);

        abort_unless($def['public']['detail'] ?? false, 404);

        $model = $def['model'];
        $item = $model::query()->where('status', 'active')->where('slug', $slug)->firstOrFail();

        return view('public.module.show', [
            'def' => $def,
            'key' => $module,
            'item' => $item,
        ]);
    }

    private function publicDefinition(string $module): array
    {
        $def = AdminModules::definition($module);

        abort_unless(isset($def['public']), 404);

        return $def;
    }
}
