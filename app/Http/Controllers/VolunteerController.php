<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public volunteer sign-up. Entries start as pending until a coordinator reviews them. */
class VolunteerController extends Controller
{
    public function create(): View
    {
        return view('public.volunteer');
    }

    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return redirect()->route('volunteer.thanks');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'interests' => ['nullable', 'string', 'max:500'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        Volunteer::create($data + ['source' => 'public', 'status' => 'pending']);

        return redirect()->route('volunteer.thanks');
    }

    public function thanks(): View
    {
        return view('public.volunteer-thanks');
    }
}
