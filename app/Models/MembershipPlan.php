<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasAdminContent;

    protected $fillable = ['name', 'slug', 'price', 'status'];

    protected $slugSource = 'name';

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }
}
