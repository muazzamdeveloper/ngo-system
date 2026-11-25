<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * The single row of donation / payment settings. The QR path is never mass-assignable:
 * it is only set by PaymentSettingController after a validated upload.
 */
class PaymentSetting extends Model
{
    protected $fillable = ['donation_upi_id', 'donation_instructions'];

    /** The settings row; defaults if the table does not exist yet (before migrate). */
    public static function current(): self
    {
        try {
            return static::query()->first() ?? new static();
        } catch (QueryException) {
            return new static();
        }
    }

    public function hasDonationQr(): bool
    {
        return filled($this->donation_qr_path);
    }
}
