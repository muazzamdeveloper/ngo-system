<?php

namespace App\Support;

use App\Services\SiteDocument;
use App\Services\SiteMedia;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reads config/admin.php and builds validation rules for the generic module engine.
 */
class AdminModules
{
    public const IMAGE_RULES = ['image', 'mimes:png,jpg,jpeg,webp', 'max:5120'];
    public const PDF_RULES = ['file', 'mimes:pdf', 'max:10240'];
    public const YOUTUBE_PATTERN = '/^https?:\/\/(www\.|m\.)?(youtube\.com\/(watch\?|embed\/|shorts\/)|youtu\.be\/)[A-Za-z0-9_\-]{6,}/';

    public static function all(): array
    {
        return config('admin.modules', []);
    }

    /** Definition for a module key, or a 404. Authorization is done separately with authorize(). */
    public static function definition(string $key): array
    {
        $def = config("admin.modules.{$key}");

        if (! is_array($def)) {
            throw new NotFoundHttpException('Unknown module.');
        }

        return $def + ['key' => $key];
    }

    public static function authorize(array $def): void
    {
        Gate::authorize($def['permission']);
    }

    /** Field definitions that store a file, keyed by the request field name. */
    public static function uploadFields(array $def): array
    {
        return array_values(array_filter($def['fields'], fn ($f) => in_array($f['type'], ['image', 'pdf'], true)));
    }

    public static function column(array $field): string
    {
        return $field['column'] ?? $field['name'];
    }

    /** Validation rules for create (store) or update. */
    public static function rules(array $def, bool $creating, $item = null): array
    {
        $rules = [];

        foreach ($def['fields'] as $field) {
            $name = $field['name'];
            $required = ! empty($field['required']);
            $base = [];

            switch ($field['type']) {
                case 'text':
                case 'textarea':
                case 'richtext':
                    $base = ['string', 'max:' . ($field['max'] ?? ($field['type'] === 'text' ? 255 : 20000))];
                    break;

                case 'url':
                    $base = ['string', 'url:http,https', 'max:' . ($field['max'] ?? 500)];
                    break;

                case 'youtube':
                    $base = ['string', 'max:' . ($field['max'] ?? 500), 'regex:' . self::YOUTUBE_PATTERN];
                    break;

                case 'date':
                    $base = ['date'];
                    if (! empty($field['after'])) {
                        $base[] = 'after_or_equal:' . $field['after'];
                    }
                    break;

                case 'time':
                    $base = ['date_format:H:i'];
                    break;

                case 'money':
                    $base = ['numeric', 'min:' . ($field['min'] ?? 0), 'max:99999999.99'];
                    break;

                case 'number':
                    $base = ['integer', 'min:' . ($field['min'] ?? 0), 'max:' . ($field['max'] ?? 999999)];
                    break;

                case 'select':
                    $base = [Rule::in(array_map('strval', array_keys($field['options'] ?? [])))];
                    break;

                case 'status':
                    $statusMap = defined($def['model'] . '::STATUSES')
                        ? constant($def['model'] . '::STATUSES')
                        : ['active' => '', 'inactive' => ''];
                    $base = [Rule::in(array_keys($statusMap))];
                    break;

                case 'image':
                    $base = self::IMAGE_RULES;
                    break;

                case 'pdf':
                    $base = self::PDF_RULES;
                    break;
            }

            if (in_array($field['type'], ['image', 'pdf'], true)) {
                // File fields: required only when creating, or when the record has no file yet.
                $hasExisting = $item && filled($item->{self::column($field)});
                $needsFile = $required && ($creating || ! $hasExisting);
                $rules[$name] = array_merge([$needsFile ? 'required' : 'nullable'], $base);
                continue;
            }

            $rules[$name] = array_merge([$required ? 'required' : 'nullable'], $base);
        }

        return $rules;
    }

    /**
     * Converts validated input into model attributes. Uploads are stored here; the caller
     * deletes replaced files. Returns [attributes, filesToDelete].
     */
    public static function attributes(array $def, array $validated, $request, $item = null): array
    {
        $attributes = [];
        $old = [];

        foreach ($def['fields'] as $field) {
            $name = $field['name'];
            $column = self::column($field);

            if (in_array($field['type'], ['image', 'pdf'], true)) {
                if ($request->hasFile($name)) {
                    if ($item && filled($item->{$column})) {
                        $old[] = $item->{$column};
                    }

                    $attributes[$column] = $field['type'] === 'pdf'
                        ? SiteDocument::store($request->file($name))
                        : SiteMedia::store($request->file($name));

                    if ($field['type'] === 'pdf') {
                        $attributes['original_name'] = mb_substr($request->file($name)->getClientOriginalName(), 0, 255);
                    }
                }
                continue;
            }

            if (! array_key_exists($name, $validated)) {
                continue;
            }

            $value = $validated[$name];

            $attributes[$column] = match ($field['type']) {
                'richtext' => \App\Support\HtmlCleaner::clean((string) $value),
                'money' => round((float) $value, 2),
                'number', 'select' => is_numeric($value) ? (int) $value : $value,
                default => ($value === '' ? null : $value),
            };
        }

        return [$attributes, $old];
    }

    /** Constant column values for a module that shares a table (leadership / governing). */
    public static function applyFilter(array $def, array $attributes): array
    {
        return array_merge($attributes, $def['filter'] ?? []);
    }

    public static function typeLabel(array $def): string
    {
        return $def['singular'] ?? 'item';
    }

    public static function label(array $def, string $key): string
    {
        return Arr::get($def, $key, '');
    }
}
