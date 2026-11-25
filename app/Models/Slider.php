<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'image_path', 'sort_order', 'status'];


}
