<?php

namespace App\Providers;

use App\Listeners\RecordLoginActivity;
use App\Support\SiteChrome;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sign-in history for the super admin (see the Login activity page).
        Event::listen(Login::class, [RecordLoginActivity::class, 'onLogin']);
        Event::listen(Failed::class, [RecordLoginActivity::class, 'onFailed']);
        Event::listen(Logout::class, [RecordLoginActivity::class, 'onLogout']);
        Event::listen(Lockout::class, [RecordLoginActivity::class, 'onLockout']);

        // Public application form: at most 10 sends per hour from one IP address.
        RateLimiter::for('apply', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        // Verify page downloads: at most 20 per minute from one IP address.
        RateLimiter::for('verify-download', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // Either post permission opens the Posts screens (what each person may do inside is checked there).
        Gate::define('posts.access', fn (User $user) => $user->hasPermission('posts.write') || $user->hasPermission('posts.manage_all'));

        // Header and footer of the public pages (ticker, contact details, footer links).
        View::composer('layouts.site', fn ($view) => $view->with('chrome', SiteChrome::load()));

        // Sidebar badge: online applications still waiting for a decision. Only counted
        // for people who may see members, and a database problem never breaks the page.
        View::composer('layouts.admin', function ($view) {
            $count = 0;

            try {
                if (auth()->check() && auth()->user()->can('members.view')) {
                    $count = Member::online()->pending()->count();
                }
            } catch (\Throwable) {
                $count = 0;
            }

            $view->with('pendingApplicationsCount', $count);
        });

        // Our own pagination markup (the project does not use Tailwind).
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        // Every permission name (e.g. "members.view") works with can(), @can and
        // the "can:" route middleware. The super admin always passes; others pass
        // only if their role has the permission. Returning null lets Laravel fall
        // back to any policy that is defined later.
        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ? true : null;
        });
    }
}
