<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrganizationSettingRequest;
use App\Models\OrganizationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'setting' => OrganizationSetting::current(),
        ]);
    }

    public function update(UpdateOrganizationSettingRequest $request): RedirectResponse
    {
        $setting = OrganizationSetting::query()->first() ?? new OrganizationSetting();
        $data = $request->validated();

        // Text fields: an empty box means "not set".
        $setting->fill([
            'name' => $data['name'],
            'membership_prefix' => $data['membership_prefix'],
            'site_title' => $data['site_title'] ?? null,
            'tagline' => $data['tagline'] ?? null,
            'primary_color' => $request->boolean('reset_color') ? null : ($data['primary_color'] ?? null),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'address_secondary' => $data['address_secondary'] ?? null,
            'working_hours' => $data['working_hours'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'facebook_url' => $data['facebook_url'] ?? null,
            'instagram_url' => $data['instagram_url'] ?? null,
            'youtube_url' => $data['youtube_url'] ?? null,
            'ticker_label' => $data['ticker_label'] ?? null,
        ]);

        $oldFiles = [];
        $newFiles = [];

        foreach (['logo' => 'logo_path', 'favicon' => 'favicon_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                $oldFiles[] = $setting->{$column};
                $setting->{$column} = $newFiles[] = $request->file($field)->store('branding', 'local');
            } elseif ($request->boolean('remove_' . $field)) {
                $oldFiles[] = $setting->{$column};
                $setting->{$column} = null;
            }
        }

        try {
            $setting->save();
        } catch (Throwable $e) {
            foreach ($newFiles as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        foreach (array_filter($oldFiles) as $path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('settings.edit')->with('success', 'Organization settings saved.');
    }
}
