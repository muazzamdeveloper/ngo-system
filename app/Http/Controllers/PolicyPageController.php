<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\View\View;

class PolicyPageController extends Controller
{
    public function show(string $type): View
    {
        abort_unless(array_key_exists($type, Policy::TYPES), 404);

        $policy = Policy::query()->where('type', $type)->where('is_published', true)->first();
        abort_unless($policy, 404);

        return view('public.policy', [
            'policy' => $policy,
            'sections' => $policy->sections()->where('status', 'active')->get(),
        ]);
    }
}
