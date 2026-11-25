<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Gives a record a number such as CERT00004 when it is created. The number is built from
 * the highest existing number for the same prefix, inside a transaction.
 */
trait HasSequentialNumber
{
    abstract protected static function numberColumn(): string;

    abstract protected static function numberPrefix(): string;

    protected static function bootHasSequentialNumber(): void
    {
        static::creating(function ($model) {
            $column = static::numberColumn();

            if (filled($model->{$column})) {
                return;
            }

            $prefix = static::numberPrefix();
            $width = 5;

            $last = DB::table($model->getTable())
                ->where($column, 'like', $prefix . '%')
                ->lockForUpdate()
                ->pluck($column)
                ->map(fn ($value) => (int) preg_replace('/\D/', '', (string) $value))
                ->max();

            $model->{$column} = $prefix . str_pad((string) (($last ?? 0) + 1), $width, '0', STR_PAD_LEFT);
        });
    }
}
