<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasAdminContent;

    protected $fillable = ['membership_level', 'name', 'sort_order', 'status'];


}
