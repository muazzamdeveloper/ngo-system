<?php

namespace App\Models;

use App\Models\Concerns\HasSequentialNumber;
use Illuminate\Database\Eloquent\Model;

class MembershipCertificate extends Model
{
    use HasSequentialNumber;

    protected $fillable = ['member_name', 'designation', 'issue_date', 'valid_until', 'status', 'notes'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'valid_until' => 'date'];
    }

    protected static function numberColumn(): string
    {
        return 'certificate_no';
    }

    protected static function numberPrefix(): string
    {
        return 'MEMB';
    }

    public function validityLabel(): string
    {
        return $this->valid_until ? $this->valid_until->format('d M Y') : 'Lifetime';
    }
}
