<?php

use App\Http\Controllers\Account\DocumentController as AccountDocumentController;
use App\Http\Controllers\Account\HomeController as AccountHomeController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\ApplicationInboxController;
use App\Http\Controllers\Admin\ApplicationPaymentController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeItemController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\LoginActivityController;
use App\Http\Controllers\Admin\MemberAccountController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberPdfController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CardSettingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteLinkController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TickerItemController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DonationQrController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SiteMediaController;
use App\Http\Controllers\PaymentQrController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/page/{slug}', [HomeController::class, 'page'])->where('slug', '[a-z0-9-]+')->name('page.show');

// The payment QR is public (it is shown above the application form). Nothing else from Card Settings is.
Route::get('/payment-qr', PaymentQrController::class)->name('payment-qr');
// The donation QR (used by the donate page). 404 until one is uploaded in Payment settings.
Route::get('/donation-qr', DonationQrController::class)->name('donation-qr');
Route::get('/branding/logo', [BrandingController::class, 'logo'])->name('site.logo');
Route::get('/media/{file}', [SiteMediaController::class, 'show'])->where('file', '[A-Za-z0-9.]+')->name('site.media');
Route::get('/branding/icon', [BrandingController::class, 'icon'])->name('site.icon');

// Public membership application. Anyone can open it; sending is rate limited.
Route::get('/apply', [ApplicationController::class, 'create'])->name('apply.create');
Route::post('/apply', [ApplicationController::class, 'store'])->middleware('throttle:apply')->name('apply.store');
Route::get('/apply/thanks', [ApplicationController::class, 'thanks'])->name('apply.thanks');

// Public membership check by Aadhaar (rate limited inside the controller). Downloads need a
// signed link that expires after 15 minutes.
Route::get('/verify', [VerifyController::class, 'create'])->name('verify.create');
Route::post('/verify', [VerifyController::class, 'check'])->name('verify.check');
Route::get('/verify/download/{member}/{document}', [VerifyController::class, 'download'])
    ->whereNumber('member')
    ->where('document', 'id-card|joining-letter')
    ->middleware(['signed', 'throttle:verify-download'])
    ->name('verify.download');

// Only for visitors who are NOT logged in.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Any logged-in user may log out or change their password.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');
});

// Member home page (people with a temporary password must change it first).
Route::middleware(['auth', 'account.active', 'password.changed'])->group(function () {
    Route::get('/account', [AccountHomeController::class, 'index'])->name('account.home');
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::get('/account/photo', [ProfileController::class, 'photo'])->name('account.photo');
    Route::get('/account/documents/{document}', [AccountDocumentController::class, 'download'])
        ->where('document', 'id-card|joining-letter')->name('account.document');
});

// Admin area: logged in AND allowed by the admin access check.
Route::middleware(['auth', 'account.active', 'password.changed', 'admin.access'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Create routes come first so "/members/create" is never mistaken for a member id.
    Route::middleware('can:members.create')->group(function () {
        Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    });

    Route::middleware('can:members.edit')->group(function () {
        Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->whereNumber('member')->name('members.edit');
        Route::put('/members/{member}', [MemberController::class, 'update'])->whereNumber('member')->name('members.update');
    });

    Route::post('/members/{member}/account', [MemberAccountController::class, 'store'])
        ->middleware('can:members.create_account')->whereNumber('member')->name('members.account');

    // Decisions on applications: approve, reject, reopen.
    Route::middleware('can:members.approve')->group(function () {
        Route::post('/members/{member}/approve', [MemberController::class, 'approve'])->whereNumber('member')->name('members.approve');
        Route::post('/members/{member}/reject', [MemberController::class, 'reject'])->whereNumber('member')->name('members.reject');
        Route::post('/members/{member}/reopen', [MemberController::class, 'reopen'])->whereNumber('member')->name('members.reopen');
    });

    // Changes to existing members: deactivate, restore, block, unblock.
    Route::middleware('can:members.deactivate')->group(function () {
        Route::post('/members/{member}/deactivate', [MemberController::class, 'deactivate'])->whereNumber('member')->name('members.deactivate');
        Route::post('/members/{member}/restore', [MemberController::class, 'restore'])->whereNumber('member')->name('members.restore');
        Route::post('/members/{member}/block', [MemberController::class, 'block'])->whereNumber('member')->name('members.block');
        Route::post('/members/{member}/unblock', [MemberController::class, 'unblock'])->whereNumber('member')->name('members.unblock');
    });

    Route::post('/members/{member}/aadhaar', [MemberController::class, 'revealAadhaar'])
        ->middleware('can:members.view_aadhaar')->whereNumber('member')->name('members.aadhaar');

    Route::middleware('can:members.view')->group(function () {
        Route::get('/applications', [ApplicationInboxController::class, 'index'])->name('applications.index');
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::get('/members/{member}', [MemberController::class, 'show'])->whereNumber('member')->name('members.show');
        Route::get('/members/{member}/pdf/{document}', [MemberPdfController::class, 'download'])
            ->whereNumber('member')->where('document', 'id-card|joining-letter')->name('members.pdf');
        Route::get('/members/{member}/photo', [MemberController::class, 'photo'])->whereNumber('member')->name('members.photo');
        Route::get('/members/{member}/documents/{document}', [MemberController::class, 'document'])
            ->whereNumber('member')
            ->where('document', 'aadhaar-front|aadhaar-back|payment-screenshot|resume')
            ->name('members.document');
    });

    // Staff accounts and their roles.
    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users', [StaffController::class, 'index'])->name('users.index');
        Route::get('/users/create', [StaffController::class, 'create'])->name('users.create');
        Route::post('/users', [StaffController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [StaffController::class, 'edit'])->whereNumber('user')->name('users.edit');
        Route::put('/users/{user}', [StaffController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::post('/users/{user}/deactivate', [StaffController::class, 'deactivate'])->whereNumber('user')->name('users.deactivate');
        Route::post('/users/{user}/activate', [StaffController::class, 'activate'])->whereNumber('user')->name('users.activate');
    });

    // Roles and permissions: super admin only, whatever permissions anyone holds.
    Route::middleware('super.admin')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->whereNumber('role')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('roles.destroy');

        Route::get('/login-activity', [LoginActivityController::class, 'index'])->name('login-activity.index');
    });

    Route::middleware('can:card_settings.manage')->group(function () {
        Route::get('/card-settings', [CardSettingController::class, 'edit'])->name('card-settings.edit');
        Route::put('/card-settings', [CardSettingController::class, 'update'])->name('card-settings.update');
        Route::get('/card-settings/images/{image}', [CardSettingController::class, 'image'])
            ->where('image', 'logo|payment-qr|website-qr|stamp')->name('card-settings.image');
    });

    // Application-fee payments (QR + screenshot): view and check them.
    Route::middleware('can:payments.view')->group(function () {
        Route::get('/payments', [ApplicationPaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{member}/screenshot', [ApplicationPaymentController::class, 'screenshot'])
            ->whereNumber('member')->name('payments.screenshot');
    });

    Route::middleware('can:payments.verify')->group(function () {
        Route::post('/payments/{member}/verify', [ApplicationPaymentController::class, 'verify'])->whereNumber('member')->name('payments.verify');
        Route::post('/payments/{member}/reject', [ApplicationPaymentController::class, 'reject'])->whereNumber('member')->name('payments.reject');
        Route::post('/payments/{member}/reset', [ApplicationPaymentController::class, 'reset'])->whereNumber('member')->name('payments.reset');
    });

    // Donation QR and other payment settings.
    Route::middleware('can:payment_settings.manage')->group(function () {
        Route::get('/payment-settings', [PaymentSettingController::class, 'edit'])->name('payment-settings.edit');
        Route::put('/payment-settings', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
        Route::get('/payment-settings/donation-qr', [PaymentSettingController::class, 'donationQr'])->name('payment-settings.donation-qr');
    });

    // Posts: writers work on their own posts, people who manage all posts see everything and manage categories.
    Route::middleware('can:posts.access')->group(function () {
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->whereNumber('post')->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->whereNumber('post')->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->whereNumber('post')->name('posts.destroy');
    });

    Route::middleware('can:posts.manage_all')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->whereNumber('category')->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category')->name('categories.destroy');
    });

    // Website content. Each area has its own permission, so the super admin can hand out just what is needed.
    Route::middleware('can:website.menus')->group(function () {
        // News ticker, header menu, header buttons and footer links.
        Route::get('/ticker', [TickerItemController::class, 'index'])->name('ticker.index');
        Route::post('/ticker/settings', [TickerItemController::class, 'settings'])->name('ticker.settings');
        Route::post('/ticker', [TickerItemController::class, 'store'])->name('ticker.store');
        Route::get('/ticker/{item}/edit', [TickerItemController::class, 'edit'])->whereNumber('item')->name('ticker.edit');
        Route::put('/ticker/{item}', [TickerItemController::class, 'update'])->whereNumber('item')->name('ticker.update');
        Route::delete('/ticker/{item}', [TickerItemController::class, 'destroy'])->whereNumber('item')->name('ticker.destroy');

        Route::get('/site-links', [SiteLinkController::class, 'index'])->name('site-links.index');
        Route::post('/site-links', [SiteLinkController::class, 'store'])->name('site-links.store');
        Route::get('/site-links/{link}/edit', [SiteLinkController::class, 'edit'])->whereNumber('link')->name('site-links.edit');
        Route::put('/site-links/{link}', [SiteLinkController::class, 'update'])->whereNumber('link')->name('site-links.update');
        Route::delete('/site-links/{link}', [SiteLinkController::class, 'destroy'])->whereNumber('link')->name('site-links.destroy');

    });

    Route::middleware('can:website.home')->group(function () {
        Route::get('/home-sections', [HomeSectionController::class, 'index'])->name('home-sections.index');
        Route::post('/home-sections', [HomeSectionController::class, 'store'])->name('home-sections.store');
        Route::get('/home-sections/{section}/edit', [HomeSectionController::class, 'edit'])->whereNumber('section')->name('home-sections.edit');
        Route::put('/home-sections/{section}', [HomeSectionController::class, 'update'])->whereNumber('section')->name('home-sections.update');
        Route::delete('/home-sections/{section}', [HomeSectionController::class, 'destroy'])->whereNumber('section')->name('home-sections.destroy');

        Route::post('/home-sections/{section}/items', [HomeItemController::class, 'store'])->whereNumber('section')->name('home-items.store');
        Route::get('/home-items/{item}/edit', [HomeItemController::class, 'edit'])->whereNumber('item')->name('home-items.edit');
        Route::put('/home-items/{item}', [HomeItemController::class, 'update'])->whereNumber('item')->name('home-items.update');
        Route::delete('/home-items/{item}', [HomeItemController::class, 'destroy'])->whereNumber('item')->name('home-items.destroy');
    });

    Route::middleware('can:website.pages')->group(function () {
        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->whereNumber('page')->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->whereNumber('page')->name('pages.update');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->whereNumber('page')->name('pages.destroy');
    });

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
    require __DIR__.'/admin-modules.php';
});

require __DIR__.'/public-site.php';
