<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class GalleryVideo extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'youtube_url', 'sort_order', 'status'];


}
