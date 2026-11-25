<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Home page for logged-in members (expanded in the member profile task). */
    public function index(Request $request): View
    {
        return view('account.home', [
            'member' => Member::where('user_id', $request->user()->id)->first(),
        ]);
    }
}
