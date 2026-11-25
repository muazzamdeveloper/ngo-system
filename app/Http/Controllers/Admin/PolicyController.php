<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicySection;
use App\Support\HtmlCleaner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Privacy, terms, refund, shipping and disclaimer policies, each made of ordered sections. */
class PolicyController extends Controller
{
    public function index(): View
    {
        Gate::authorize('policies.manage');

        $policies = collect(Policy::TYPES)->map(fn ($title, $type) => Policy::forType($type)->loadCount('sections'));

        return view('admin.policies.index', ['policies' => $policies, 'types' => Policy::TYPES]);
    }

    public function show(string $type): View
    {
        Gate::authorize('policies.manage');
        $policy = $this->policy($type);

        return view('admin.policies.show', ['policy' => $policy, 'type' => $type, 'sections' => $policy->sections()->get()]);
    }

    public function publish(string $type): RedirectResponse
    {
        Gate::authorize('policies.manage');
        $policy = $this->policy($type);
        $policy->update(['is_published' => ! $policy->is_published]);

        return back()->with('success', $policy->is_published ? 'Policy published.' : 'Policy hidden from the public site.');
    }

    public function createSection(string $type): View
    {
        Gate::authorize('policies.manage');

        return view('admin.policies.section-form', [
            'type' => $type,
            'policy' => $this->policy($type),
            'section' => new PolicySection(['status' => 'active', 'icon' => 'fa-info-circle', 'sort_order' => $this->nextOrder($type)]),
        ]);
    }

    public function storeSection(Request $request, string $type): RedirectResponse
    {
        Gate::authorize('policies.manage');
        $policy = $this->policy($type);

        $policy->sections()->create($this->validated($request));

        return redirect()->route('policies.show', $type)->with('success', 'Section added.');
    }

    public function editSection(string $type, PolicySection $section): View
    {
        Gate::authorize('policies.manage');
        $this->ensureBelongs($type, $section);

        return view('admin.policies.section-form', ['type' => $type, 'policy' => $this->policy($type), 'section' => $section]);
    }

    public function updateSection(Request $request, string $type, PolicySection $section): RedirectResponse
    {
        Gate::authorize('policies.manage');
        $this->ensureBelongs($type, $section);

        $section->update($this->validated($request));

        return redirect()->route('policies.show', $type)->with('success', 'Section updated.');
    }

    public function destroySection(string $type, PolicySection $section): RedirectResponse
    {
        Gate::authorize('policies.manage');
        $this->ensureBelongs($type, $section);

        $section->delete();

        return redirect()->route('policies.show', $type)->with('success', 'Section deleted.');
    }

    private function policy(string $type): Policy
    {
        abort_unless(array_key_exists($type, Policy::TYPES), 404);

        return Policy::forType($type);
    }

    private function ensureBelongs(string $type, PolicySection $section): void
    {
        abort_unless($section->policy?->type === $type, 404);
    }

    private function nextOrder(string $type): int
    {
        return (int) (Policy::forType($type)->sections()->max('sort_order') ?? 0) + 1;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:60', 'regex:/^fa-[a-z0-9-]+$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'content' => ['required', 'string', 'max:50000'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['content'] = HtmlCleaner::clean($data['content']);
        $data['icon'] = $data['icon'] ?? 'fa-info-circle';

        return $data;
    }
}
