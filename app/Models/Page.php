<?php

namespace App\Models;

use App\Services\SiteMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'summary', 'body', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function imageUrl(): ?string
    {
        return SiteMedia::url($this->image_path);
    }

    public function url(): string
    {
        return url('/page/' . $this->slug);
    }

    /** The body as safe HTML. Raw HTML typed into the box is removed; unsafe links are refused. */
    public function html(): string
    {
        return Str::markdown((string) $this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
