<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUSES = ['new' => 'New', 'read' => 'Read'];

    protected $fillable = ['name', 'email', 'mobile', 'subject', 'message', 'status', 'ip'];
}
