<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCardSettingRequest;
use App\Models\CardSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CardSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.card-settings.edit', [
            'setting' => CardSetting::current(),
            'pdfReady' => class_exists(\Dompdf\Dompdf::class),
        ]);
    }

    public function update(UpdateCardSettingRequest $request): RedirectResponse
    {
        $setting = CardSetting::query()->first() ?? new CardSetting();
        $validated = $request->validated();

        // Text settings go through $fillable. Image paths are handled separately below.
        $setting->fill(Arr::except($validated, [
            ...array_keys(CardSetting::IMAGES),
            ...array_map(fn ($key) => 'remove_' . $key, array_keys(CardSetting::IMAGES)),
        ]));

        $newFiles = [];
        $oldFiles = [];

        foreach (CardSetting::IMAGES as $key => $column) {
            if ($request->hasFile($key)) {
                $newFiles[] = $path = $request->file($key)->store('card-settings', 'local');
                $oldFiles[] = $setting->{$column};
                $setting->{$column} = $path;
            } elseif ($request->boolean('remove_' . $key) && $setting->{$column}) {
                $oldFiles[] = $setting->{$column};
                $setting->{$column} = null;
            }
        }

        try {
            DB::transaction(fn () => $setting->save());
        } catch (Throwable $e) {
            foreach ($newFiles as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        foreach (array_filter($oldFiles) as $path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('card-settings.edit')->with('success', 'Card settings saved.');
    }

    /** Preview of one stored image inside the admin panel (private disk, never a public URL). */
    public function image(Request $request, string $image): StreamedResponse
    {
        $key = str_replace('-', '_', $image);
        $column = CardSetting::IMAGES[$key] ?? null;

        abort_if($column === null, 404);

        $path = CardSetting::current()->{$column};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
