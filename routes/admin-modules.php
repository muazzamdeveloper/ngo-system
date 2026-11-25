<?php

// Loaded inside the admin group in routes/web.php, so every route here already needs login,
// an active account, a changed password and admin access. Each controller checks its own permission.

use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\DonationController;
use App\Http\Controllers\Admin\GatewayController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PolicyController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Admin\SiteConfigController;
use App\Http\Controllers\Admin\SmtpController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\VolunteerAdminController;
use Illuminate\Support\Facades\Route;

// Donations ledger. Fixed paths come before the {donation} wildcard.
Route::get('/donations', [DonationController::class, 'index'])->name('donations.index');
Route::get('/donations/create', [DonationController::class, 'create'])->name('donations.create');
Route::post('/donations', [DonationController::class, 'store'])->name('donations.store');
Route::get('/donations/export', [DonationController::class, 'export'])->name('donations.export');
Route::get('/donations/{donation}/edit', [DonationController::class, 'edit'])->whereNumber('donation')->name('donations.edit');
Route::put('/donations/{donation}', [DonationController::class, 'update'])->whereNumber('donation')->name('donations.update');
Route::post('/donations/{donation}/status', [DonationController::class, 'status'])->whereNumber('donation')->name('donations.status');
Route::delete('/donations/{donation}', [DonationController::class, 'destroy'])->whereNumber('donation')->name('donations.destroy');

// Generic modules (campaigns, projects, events, gallery, designations, certificates ...).
Route::prefix('m')->group(function () {
    Route::get('/{module}', [ModuleController::class, 'index'])->where('module', '[a-z0-9-]+')->name('modules.index');
    Route::get('/{module}/create', [ModuleController::class, 'create'])->where('module', '[a-z0-9-]+')->name('modules.create');
    Route::post('/{module}', [ModuleController::class, 'store'])->where('module', '[a-z0-9-]+')->name('modules.store');
    Route::get('/{module}/{id}/edit', [ModuleController::class, 'edit'])->where('module', '[a-z0-9-]+')->whereNumber('id')->name('modules.edit');
    Route::put('/{module}/{id}', [ModuleController::class, 'update'])->where('module', '[a-z0-9-]+')->whereNumber('id')->name('modules.update');
    Route::delete('/{module}/{id}', [ModuleController::class, 'destroy'])->where('module', '[a-z0-9-]+')->whereNumber('id')->name('modules.destroy');
    Route::post('/{module}/{id}/toggle', [ModuleController::class, 'toggle'])->where('module', '[a-z0-9-]+')->whereNumber('id')->name('modules.toggle');
});

// Volunteers review.
Route::get('/volunteers', [VolunteerAdminController::class, 'index'])->name('volunteers.index');
Route::post('/volunteers/{volunteer}/review', [VolunteerAdminController::class, 'review'])->whereNumber('volunteer')->name('volunteers.review');
Route::delete('/volunteers/{volunteer}', [VolunteerAdminController::class, 'destroy'])->whereNumber('volunteer')->name('volunteers.destroy');

// Membership certificate PDF (staff with the "Membership certificates" permission).
Route::get('/membership-certificates/{certificate}/pdf', [\App\Http\Controllers\Admin\MembershipCertificatePdfController::class, 'download'])
    ->whereNumber('certificate')->middleware('can:certs.membership')->name('certificates.pdf');

// Template management (logos, signatures, seals, certificate templates).
Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::post('/templates/{key}', [TemplateController::class, 'upload'])->where('key', '[a-z_]+')->name('templates.upload');
Route::delete('/templates/{key}', [TemplateController::class, 'destroy'])->where('key', '[a-z_]+')->name('templates.destroy');

// Policies and their sections.
Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index');
Route::get('/policies/{type}', [PolicyController::class, 'show'])->where('type', '[a-z]+')->name('policies.show');
Route::post('/policies/{type}/publish', [PolicyController::class, 'publish'])->where('type', '[a-z]+')->name('policies.publish');
Route::get('/policies/{type}/sections/create', [PolicyController::class, 'createSection'])->where('type', '[a-z]+')->name('policies.sections.create');
Route::post('/policies/{type}/sections', [PolicyController::class, 'storeSection'])->where('type', '[a-z]+')->name('policies.sections.store');
Route::get('/policies/{type}/sections/{section}/edit', [PolicyController::class, 'editSection'])->where('type', '[a-z]+')->whereNumber('section')->name('policies.sections.edit');
Route::put('/policies/{type}/sections/{section}', [PolicyController::class, 'updateSection'])->where('type', '[a-z]+')->whereNumber('section')->name('policies.sections.update');
Route::delete('/policies/{type}/sections/{section}', [PolicyController::class, 'destroySection'])->where('type', '[a-z]+')->whereNumber('section')->name('policies.sections.destroy');

// Site configuration, themes and custom domain.
// Editable content of the public pages in config/site_pages.php.
Route::middleware('can:content.manage')->group(function () {
    Route::get('/content', [PageContentController::class, 'index'])->name('content.index');
    Route::get('/content/{page}', [PageContentController::class, 'edit'])->where('page', '[a-z0-9-]+')->name('content.edit');
    Route::put('/content/{page}', [PageContentController::class, 'update'])->where('page', '[a-z0-9-]+')->name('content.update');
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('content.messages.index');
    Route::put('/contact-messages/{message}/read', [ContactMessageController::class, 'markRead'])->whereNumber('message')->name('content.messages.read');
    Route::delete('/contact-messages/{message}', [ContactMessageController::class, 'destroy'])->whereNumber('message')->name('content.messages.destroy');
});

Route::get('/site-config', [SiteConfigController::class, 'edit'])->name('site-config.edit');
Route::put('/site-config', [SiteConfigController::class, 'update'])->name('site-config.update');
Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
Route::post('/themes/apply', [ThemeController::class, 'applyPalette'])->name('themes.apply');
Route::post('/themes/custom', [ThemeController::class, 'customColours'])->name('themes.custom');
Route::get('/domains', [DomainController::class, 'edit'])->name('domains.edit');
Route::post('/domains', [DomainController::class, 'verify'])->name('domains.verify');
Route::post('/domains/disconnect', [DomainController::class, 'disconnect'])->name('domains.disconnect');

// Email: SMTP server, notification broadcasts.
Route::get('/smtp', [SmtpController::class, 'edit'])->name('smtp.edit');
Route::put('/smtp', [SmtpController::class, 'update'])->name('smtp.update');
Route::post('/smtp/test', [SmtpController::class, 'test'])->name('smtp.test');
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications', [NotificationController::class, 'send'])->name('notifications.send');

// Payment gateways.
Route::get('/gateways', [GatewayController::class, 'edit'])->name('gateways.edit');
Route::put('/gateways', [GatewayController::class, 'update'])->name('gateways.update');

// Profile of the signed-in staff user.
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

// Security permissions (Coordinator role): super admin only.
Route::middleware('super.admin')->group(function () {
    Route::get('/security', [SecurityController::class, 'edit'])->name('security.edit');
    // Per-user module access for staff accounts (each account has its own ticks).
    Route::get('/access', [UserAccessController::class, 'index'])->name('access.index');
    Route::get('/access/{user}/edit', [UserAccessController::class, 'edit'])->whereNumber('user')->name('access.edit');
    Route::put('/access/{user}', [UserAccessController::class, 'update'])->whereNumber('user')->name('access.update');
    Route::post('/access/{user}/preset', [UserAccessController::class, 'preset'])->whereNumber('user')->name('access.preset');
    Route::put('/security', [SecurityController::class, 'update'])->name('security.update');
});
