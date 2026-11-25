<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasAdminContent;

    protected $fillable = ['group', 'name', 'designation', 'message', 'photo_path', 'sort_order', 'status'];


}
