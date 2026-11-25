<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasAdminContent;

    protected $fillable = ['name', 'role', 'message', 'rating', 'photo_path', 'sort_order', 'status'];


}
