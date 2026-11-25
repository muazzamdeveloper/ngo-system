<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    public const TYPES = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
        'refund' => 'Refund & Cancellation Policy',
        'shipping' => 'Shipping & Delivery Policy',
        'disclaimer' => 'Legal Disclaimer',
    ];

    protected $fillable = ['type', 'title', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PolicySection::class)->orderBy('sort_order')->orderBy('id');
    }

    public static function forType(string $type): self
    {
        return static::query()->firstOrCreate(
            ['type' => $type],
            ['title' => self::TYPES[$type] ?? ucfirst($type), 'is_published' => true],
        );
    }
}
