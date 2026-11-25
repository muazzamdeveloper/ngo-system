<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Donation extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    public const METHODS = [
        'upi' => 'UPI / QR',
        'bank' => 'Bank transfer',
        'online' => 'Online gateway',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
    ];

    protected $fillable = [
        'campaign_id', 'donor_name', 'donor_email', 'donor_phone', 'donor_pan', 'amount',
        'method', 'status', 'transaction_ref', 'notes', 'donated_at', 'source',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'donated_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Donation $donation) {
            $donation->donated_at ??= now();
            if ($donation->status === 'completed') {
                $donation->receipt_no ??= self::nextReceiptNo();
            }
        });

        // A receipt number is issued once the donation is confirmed, never while it is pending.
        static::updating(function (Donation $donation) {
            if ($donation->isDirty('status') && $donation->status === 'completed') {
                $donation->receipt_no ??= self::nextReceiptNo();
            }
        });
    }

    /** Receipt numbers restart each year: NGO-2026-0001. */
    public static function nextReceiptNo(): string
    {
        $prefix = 'NGO-' . now()->format('Y') . '-';
        $last = DB::table('donations')->where('receipt_no', 'like', $prefix . '%')
            ->lockForUpdate()->pluck('receipt_no')
            ->map(fn ($n) => (int) substr((string) $n, strlen($prefix)))->max();

        return $prefix . str_pad((string) (($last ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst((string) $this->method);
    }
}
