<?php

/*
|--------------------------------------------------------------------------
| Public site navigation and footer
|--------------------------------------------------------------------------
| Used by partials/site-header and partials/site-footer. Each link is either
| 'route' (a named route) or 'url' (a path such as /page/about-organisation).
| Pages that are still to be built point to /page/{slug}; they are created in
| the admin under Pages.
*/

return [
    'menu' => [
        ['label' => 'Home', 'icon' => 'home', 'route' => 'home'],
        ['label' => 'About Us', 'icon' => 'info', 'children' => [
            ['label' => 'About Organisation', 'url' => '/page/about-organisation'],
            ['label' => 'Leadership Desk', 'url' => '/page/leadership-desk'],
        ]],
        ['label' => 'Our Team', 'icon' => 'team', 'children' => [
            ['label' => 'All Members', 'url' => '/page/our-general-members'],
            ['label' => 'Management Team', 'url' => '/page/management-team'],
            ['label' => 'Our Volunteers', 'url' => '/page/our-volunteers'],
        ]],
        ['label' => 'Apply Now', 'icon' => 'user-plus', 'route' => 'apply.create'],
        ['label' => 'Events & News', 'icon' => 'calendar', 'children' => [
            ['label' => 'Upcoming Events', 'route' => 'public.events.index'],
            ['label' => 'News & Updates', 'url' => '/page/news-activities'],
        ]],
        ['label' => 'Gallery', 'icon' => 'image', 'children' => [
            ['label' => 'Photo Gallery', 'route' => 'public.gallery.index'],
            ['label' => 'Video Gallery', 'route' => 'public.videos.index'],
        ]],
        ['label' => 'Support Us', 'icon' => 'hand', 'children' => [
            ['label' => 'Make Donation', 'route' => 'donate'],
            ['label' => 'Our Supporters', 'url' => '/page/our-supporters'],
            ['label' => 'Crowdfunding', 'route' => 'public.campaigns.index'],
        ]],
        ['label' => 'Certifications', 'icon' => 'award', 'children' => [
            ['label' => 'Certifications', 'url' => '/page/certifications'],
            ['label' => 'Official Documents', 'url' => '/page/official-documents'],
        ]],
        ['label' => 'Contact', 'icon' => 'mail', 'url' => '/page/contact-us'],
        ['label' => 'Login', 'icon' => 'login', 'route' => 'login'],
    ],

    'quick_links' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'About Us', 'url' => '/page/about-organisation'],
        ['label' => 'Our Team', 'url' => '/page/our-general-members'],
        ['label' => 'Events', 'route' => 'public.events.index'],
        ['label' => 'Gallery', 'route' => 'public.gallery.index'],
        ['label' => 'Contact', 'url' => '/page/contact-us'],
    ],

    'services' => [
        ['label' => 'Donations', 'route' => 'donate'],
        ['label' => 'Crowdfunding', 'route' => 'public.campaigns.index'],
        ['label' => 'Volunteer Apply', 'route' => 'volunteer'],
        ['label' => 'Certifications', 'url' => '/page/certifications'],
        ['label' => 'Support', 'url' => '/page/contact-us'],
    ],

    'legal' => [
        ['label' => 'Terms', 'route' => 'policy.show', 'param' => 'terms'],
        ['label' => 'Privacy', 'route' => 'policy.show', 'param' => 'privacy'],
        ['label' => 'Refund', 'route' => 'policy.show', 'param' => 'refund'],
        ['label' => 'Disclaimer', 'route' => 'policy.show', 'param' => 'disclaimer'],
        ['label' => 'Shipping', 'route' => 'policy.show', 'param' => 'shipping'],
    ],

    // Credit line shown at the very bottom of the footer.
    'credit' => ['text' => 'Designed & Developed by', 'name' => 'Online Growth Hub', 'url' => null],
];
