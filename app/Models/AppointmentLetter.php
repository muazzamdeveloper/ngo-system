<?php

namespace App\Models;

use App\Models\Concerns\HasSequentialNumber;
use Illuminate\Database\Eloquent\Model;

class AppointmentLetter extends Model
{
    use HasSequentialNumber;

    protected $fillable = ['recipient_name', 'designation', 'issue_date', 'valid_until', 'reason', 'status'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'valid_until' => 'date'];
    }

    protected static function numberColumn(): string
    {
        return 'letter_no';
    }

    protected static function numberPrefix(): string
    {
        return 'CERT';
    }
}
