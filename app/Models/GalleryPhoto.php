<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'category', 'image_path', 'sort_order', 'status'];


}
