<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Member extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_DEACTIVATED = 'deactivated';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_BLOCKED,
        self::STATUS_DEACTIVATED,
    ];

    public const GENDERS = ['male', 'female', 'other'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_ONLINE = 'online';

    /** Upload field name => database column that holds the private file path. */
    public const DOCUMENT_COLUMNS = [
        'aadhaar_front' => 'aadhaar_front_path',
        'aadhaar_back' => 'aadhaar_back_path',
        'payment_screenshot' => 'payment_screenshot_path',
        'resume' => 'resume_path',
    ];

    public const DOCUMENT_LABELS = [
        'aadhaar_front' => 'Aadhaar front',
        'aadhaar_back' => 'Aadhaar back',
        'payment_screenshot' => 'Payment screenshot',
        'resume' => 'Resume',
    ];

    /**
     * Fields a form may fill. Status, membership number, reviewer, login account
     * and every Aadhaar column are deliberately excluded: they are set only by
     * dedicated code, never by submitted form data.
     */
    protected $fillable = [
        'full_name',
        'father_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'mobile',
        'whatsapp',
        'email',
        'address',
        'city',
        'district',
        'work_area',
        'state',
        'pincode',
        'occupation',
        'photo_path',
    ];

    /** Never included when a member is converted to an array or JSON. */
    protected $hidden = [
        'aadhaar_encrypted',
        'aadhaar_hash',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // ---------- Relationships ----------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---------- Status ----------

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Members who sent the public application form (not added by staff). */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_ONLINE);
    }

    /** Number of uploaded documents (Aadhaar front/back, payment screenshot, resume). */
    public function documentsCount(): int
    {
        return collect(self::DOCUMENT_COLUMNS)
            ->filter(fn (string $column) => filled($this->{$column}))
            ->count();
    }

    // ---------- Aadhaar (always encrypted at rest) ----------

    /** Keep digits only, e.g. "1234 5678 9012" becomes "123456789012". */
    public static function normalizeAadhaar(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        return $digits === '' ? null : $digits;
    }

    /** Keyed hash used to detect duplicates and look a member up without decrypting. */
    public static function aadhaarHash(string $digits): string
    {
        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }

    /** Store (or clear) the Aadhaar number. Call save() afterwards. */
    public function setAadhaar(?string $number): static
    {
        $digits = static::normalizeAadhaar($number);

        if ($digits === null) {
            $this->aadhaar_encrypted = null;
            $this->aadhaar_last4 = null;
            $this->aadhaar_hash = null;

            return $this;
        }

        $this->aadhaar_encrypted = Crypt::encryptString($digits);
        $this->aadhaar_last4 = substr($digits, -4);
        $this->aadhaar_hash = static::aadhaarHash($digits);

        return $this;
    }

    /** Full number. Only call this after checking the members.view_aadhaar permission. */
    public function fullAadhaar(): ?string
    {
        if ($this->aadhaar_encrypted === null) {
            return null;
        }

        try {
            return Crypt::decryptString($this->aadhaar_encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    /** Safe display value, e.g. "XXXX XXXX 1234". */
    public function maskedAadhaar(): string
    {
        return $this->aadhaar_last4 ? 'XXXX XXXX ' . $this->aadhaar_last4 : 'Not provided';
    }

    public static function findByAadhaar(string $number): ?self
    {
        $digits = static::normalizeAadhaar($number);

        return $digits === null
            ? null
            : static::query()->where('aadhaar_hash', static::aadhaarHash($digits))->first();
    }
}
