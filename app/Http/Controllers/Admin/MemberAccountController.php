<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MemberAccountException;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\MemberAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class MemberAccountController extends Controller
{
    public function store(Request $request, Member $member, MemberAccountService $accounts): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'send_email' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $accounts->create($member, $validated['password'] ?? null, $request->boolean('send_email'));
        } catch (MemberAccountException $e) {
            return back()->withErrors(['account' => $e->getMessage()]);
        }

        $message = 'Login account created for ' . $result['user']->email . '.';

        if ($request->boolean('send_email')) {
            $message .= $result['emailed']
                ? ' An email was sent to the member.'
                : ' The email could not be sent, so please give the password to the member yourself.';
        }

        $redirect = redirect()->route('members.show', $member)->with('success', $message);

        // An automatically generated password is shown once, to the staff member only.
        return $result['generated'] ? $redirect->with('new_password', $result['password']) : $redirect;
    }
}
