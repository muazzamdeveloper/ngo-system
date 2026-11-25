<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateAsset extends Model
{
    /** key => [label, required for receipt/certificate generation] */
    public const ASSETS = [
        'logo' => ['Organization logo', true],
        'signature' => ['Authorized signature', true],
        'seal' => ['Official seal / stamp', true],
        'id_card_template' => ['Member ID card template', false],
        'certificate_template' => ['Membership certificate template', false],
        'appointment_template' => ['Appointment letter template', false],
        'donation_qr' => ['Direct donation UPI QR code', false],
    ];

    protected $fillable = ['key', 'path', 'original_name'];

    public static function forKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
