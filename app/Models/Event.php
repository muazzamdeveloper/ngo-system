<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'slug', 'event_date', 'event_time', 'location', 'image_path', 'description', 'status'];

    protected $slugSource = 'title';

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }
}
