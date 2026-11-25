<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Messages from the public Contact Us form. Needs the "Website pages content" permission. */
class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $messages = ContactMessage::query()
            ->when(isset(ContactMessage::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.content.messages', [
            'messages' => $messages,
            'status' => $status,
            'newCount' => ContactMessage::query()->where('status', 'new')->count(),
        ]);
    }

    public function markRead(ContactMessage $message): RedirectResponse
    {
        $message->update(['status' => 'read']);

        return back()->with('success', 'Message marked as read.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }
}
