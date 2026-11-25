<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Public Contact Us form. Bots that fill the hidden "website" field are dropped silently. */
class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return redirect('/page/contact-us')->with('success', 'Thank you. Your message has been sent.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactMessage::query()->create($data + ['status' => 'new', 'ip' => $request->ip()]);

        return redirect('/page/contact-us')->with('success', 'Thank you. Your message has been sent and our team will reply soon.');
    }
}
