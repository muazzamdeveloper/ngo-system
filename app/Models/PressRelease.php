<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class PressRelease extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'slug', 'release_date', 'excerpt', 'description', 'image_path', 'status'];

    protected $slugSource = 'title';

    protected function casts(): array
    {
        return ['release_date' => 'date'];
    }
}
