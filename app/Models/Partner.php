<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasAdminContent;

    protected $fillable = ['name', 'logo_path', 'website_url', 'sort_order', 'status'];


}
