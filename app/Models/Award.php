<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'award_date', 'issuer', 'description', 'image_path', 'status'];


    protected function casts(): array
    {
        return ['award_date' => 'date'];
    }
}
