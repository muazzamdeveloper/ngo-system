<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'slug', 'status', 'start_date', 'end_date', 'image_path', 'description', 'sort_order'];

    protected $slugSource = 'title';

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }
}
