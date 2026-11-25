<?php

namespace App\Support;

use App\Models\OrganizationSetting;
use App\Models\Post;
use App\Models\SiteLink;
use App\Models\TickerItem;
use Illuminate\Support\Collection;
use Throwable;

/** Everything the public header and footer need, loaded once per page. */
class SiteChrome
{
    /** @return array{org: OrganizationSetting, ticker: Collection, nav: Collection, header_action: Collection, quick: Collection, action: Collection, legal: Collection} */
    public static function load(): array
    {
        $empty = collect();

        try {
            $org = OrganizationSetting::current();
            $ticker = TickerItem::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

            // Optionally the newest post titles follow the headlines typed by hand.
            if ($org->ticker_posts > 0) {
                $titles = Post::published()->latest('published_at')->limit($org->ticker_posts)->pluck('title');
                $ticker = $ticker->concat($titles->map(fn (string $title) => new TickerItem(['text' => $title])));
            }

            $links = SiteLink::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()->groupBy('group');

            return [
                'org' => $org,
                'ticker' => $ticker,
                'nav' => $links->get('nav', $empty),
                'header_action' => $links->get('header_action', $empty),
                'quick' => $links->get('quick', $empty),
                'action' => $links->get('action', $empty),
                'legal' => $links->get('legal', $empty),
            ];
        } catch (Throwable) {
            // A database problem (for example before "php artisan migrate") never breaks a public page.
            return ['org' => OrganizationSetting::current(), 'ticker' => $empty, 'nav' => $empty, 'header_action' => $empty, 'quick' => $empty, 'action' => $empty, 'legal' => $empty];
        }
    }
}
