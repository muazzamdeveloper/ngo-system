<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    public const CATEGORIES = ['Certification', 'Legal', 'Audit report', 'Annual report', 'Other'];

    protected $fillable = ['title', 'category', 'file_path', 'original_name'];
}
