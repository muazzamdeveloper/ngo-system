<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\GalleryVideo;
use App\Models\Page;
use App\Models\Partner;
use App\Models\PressRelease;
use App\Models\Project;
use App\Models\Slider;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\PageContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'sliders' => Slider::query()->where('status', 'active')->orderBy('sort_order')->limit(5)->get(),
            'events' => Event::query()->where('status', 'active')->whereDate('event_date', '>=', today())
                ->orderBy('event_date')->limit(3)->get(),
            'projects' => Project::query()->where('status', 'active')->orderBy('sort_order')->limit(3)->get(),
            'management' => TeamMember::query()->where('status', 'active')->orderBy('sort_order')->limit(1)->get(),
            'campaigns' => Campaign::query()->where('status', 'active')->orderBy('id', 'desc')->limit(3)->get(),
            'awards' => Award::query()->where('status', 'active')->orderByDesc('award_date')->limit(3)->get(),
            'videos' => GalleryVideo::query()->where('status', 'active')->orderBy('sort_order')->limit(6)->get(),
            'photos' => GalleryPhoto::query()->where('status', 'active')->orderBy('sort_order')->limit(9)->get(),
            'news' => PressRelease::query()->where('status', 'active')->orderByDesc('release_date')->limit(6)->get(),
            'donors' => Donation::query()->completed()->whereNotNull('donor_name')->orderByDesc('donated_at')->limit(8)->get(),
            'testimonials' => Testimonial::query()->where('status', 'active')->orderBy('sort_order')->limit(3)->get(),
            'partners' => Partner::query()->where('status', 'active')->orderBy('sort_order')->limit(12)->get(),
        ]);
    }

    public function page(string $slug): View
    {
        // A database page wins; otherwise a page defined in config/site_pages.php.
        $page = Page::where('slug', $slug)->where('is_published', true)->first();

        if ($page === null) {
            abort_unless(PageContent::exists($slug), 404);

            return view('public.content-page', ['slug' => $slug]);
        }

        return view('page', ['page' => $page]);
    }
}
