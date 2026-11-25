<?php

namespace App\Models;

use App\Services\SiteMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HomeSection extends Model
{
    /**
     * What each kind of section is made of.
     *   section: the boxes on the section itself   (title, subtitle, body, image, image2, link, map)
     *   item:    the boxes on each item inside it  (image, title, subtitle, body, link, rating)
     */
    public const TYPES = [
        'hero' => [
            'label' => 'Hero slider',
            'help' => 'The big slider at the top. Each slide has a background picture, a heading, a text and a button.',
            'section' => [], 'item_name' => 'Slide', 'item' => ['image', 'title', 'body', 'link'],
        ],
        'message' => [
            'label' => 'Message (photo and text)',
            'help' => "For example a director's message: a photo, a text and a signature picture.",
            'section' => ['title', 'subtitle', 'body', 'image', 'image2'], 'item_name' => null, 'item' => [],
        ],
        'banner' => [
            'label' => 'Highlight banner',
            'help' => 'A strong line of text with phone numbers or short facts below it.',
            'section' => ['title', 'subtitle'], 'item_name' => 'Number or fact', 'item' => ['title', 'subtitle'],
        ],
        'cards' => [
            'label' => 'Cards (what we do)',
            'help' => 'A row of cards with a picture, a title and a short text.',
            'section' => ['title', 'subtitle'], 'item_name' => 'Card', 'item' => ['image', 'title', 'body', 'link'],
        ],
        'people' => [
            'label' => 'People (leaders, team)',
            'help' => 'Photos with a name and a role.',
            'section' => ['title', 'subtitle', 'link'], 'item_name' => 'Person', 'item' => ['image', 'title', 'subtitle'],
        ],
        'testimonials' => [
            'label' => 'Testimonials',
            'help' => 'What supporters say, with a name, a role and a star rating.',
            'section' => ['title', 'subtitle'], 'item_name' => 'Testimonial', 'item' => ['image', 'title', 'subtitle', 'body', 'rating'],
        ],
        'about_tabs' => [
            'label' => 'About with tabs',
            'help' => 'A short text with tabs such as Mission, Vision and Values, and a button.',
            'section' => ['title', 'subtitle', 'link'], 'item_name' => 'Tab', 'item' => ['title', 'body'],
        ],
        'gallery' => [
            'label' => 'Photo gallery',
            'help' => 'A grid of pictures.',
            'section' => ['title', 'subtitle', 'link'], 'item_name' => 'Photo', 'item' => ['image', 'title'],
        ],
        'faq' => [
            'label' => 'Questions and answers',
            'help' => 'Questions that open to show the answer.',
            'section' => ['title', 'subtitle'], 'item_name' => 'Question', 'item' => ['title', 'body'],
        ],
        'contact' => [
            'label' => 'Contact and map',
            'help' => 'Shows the address, phone and email from Organization settings, and an optional map.',
            'section' => ['title', 'subtitle', 'body', 'map'], 'item_name' => null, 'item' => [],
        ],
        'news' => [
            'label' => 'Latest news (posts)',
            'help' => 'Shows the newest published posts as cards. Posts are written under "Posts".',
            'section' => ['title', 'subtitle', 'link', 'count'], 'item_name' => null, 'item' => [],
        ],
        'text' => [
            'label' => 'Text block',
            'help' => 'A heading, a text and an optional picture and button.',
            'section' => ['title', 'subtitle', 'body', 'image', 'link'], 'item_name' => null, 'item' => [],
        ],
    ];

    protected $fillable = ['type', 'title', 'subtitle', 'body', 'link_label', 'link_url', 'item_limit', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(HomeItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function definition(): array
    {
        return self::TYPES[$this->type] ?? ['label' => $this->type, 'help' => '', 'section' => [], 'item_name' => null, 'item' => []];
    }

    public function hasField(string $field): bool
    {
        return in_array($field, $this->definition()['section'], true);
    }

    /** The text as safe HTML (raw HTML typed into the box is removed). */
    public function bodyHtml(): string
    {
        return Str::markdown((string) $this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function imageUrl(): ?string
    {
        return SiteMedia::url($this->image_path);
    }

    public function image2Url(): ?string
    {
        return SiteMedia::url($this->image2_path);
    }
}
