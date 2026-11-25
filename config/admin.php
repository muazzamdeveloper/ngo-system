<?php

use App\Models\AppointmentLetter;
use App\Models\Award;
use App\Models\Campaign;
use App\Models\Designation;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\GalleryVideo;
use App\Models\LegalDocument;
use App\Models\MembershipCertificate;
use App\Models\MembershipPlan;
use App\Models\Partner;
use App\Models\PressRelease;
use App\Models\Project;
use App\Models\Slider;
use App\Models\TeamMember;
use App\Models\Testimonial;

/*
|--------------------------------------------------------------------------
| Admin modules
|--------------------------------------------------------------------------
| Each module below is managed by App\Http\Controllers\Admin\ModuleController.
| Field types: text, textarea, richtext, date, time, money, number, select,
| status, image, pdf, youtube, url. Columns use formats: id, strong, image,
| status, date, time, money, goal, badge, text, link, stars.
|
| 'public' => the public list (and detail, when 'detail' is true) URL.
| 'filter' => constant column values for a module that shares a table (team groups).
*/
return [

    'modules' => [

        'campaigns' => [
            'label' => 'Campaigns', 'singular' => 'campaign', 'model' => Campaign::class,
            'permission' => 'campaigns.manage', 'title' => 'Manage campaigns',
            'subtitle' => 'Track fundraising initiatives, goal progress and public campaigns.',
            'search' => ['title'], 'order' => ['id', 'desc'],
            'public' => ['path' => 'campaigns', 'detail' => true],
            'columns' => [
                ['label' => '#', 'key' => 'id', 'format' => 'id'],
                ['label' => 'Image', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Campaign', 'key' => 'title', 'format' => 'strong'],
                ['label' => 'Goal & raised', 'key' => 'goal_amount', 'format' => 'goal'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
                ['label' => 'Created', 'key' => 'created_at', 'format' => 'date'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Campaign title', 'type' => 'text', 'required' => true, 'max' => 255, 'col' => 2],
                ['name' => 'short_description', 'label' => 'Short description', 'type' => 'textarea', 'max' => 500, 'rows' => 2, 'col' => 2],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Campaign image', 'type' => 'image', 'hint' => 'Recommended 800x600 px, max 5 MB.'],
                ['name' => 'goal_amount', 'label' => 'Goal amount (₹)', 'type' => 'money', 'required' => true, 'min' => 0],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date', 'after' => 'start_date'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'richtext', 'col' => 2],
            ],
        ],

        'projects' => [
            'label' => 'Our projects', 'singular' => 'project', 'model' => Project::class,
            'permission' => 'projects.manage', 'title' => 'Our projects',
            'subtitle' => 'Causes and programmes shown on the public site.',
            'search' => ['title'], 'order' => ['sort_order', 'asc'],
            'public' => ['path' => 'projects', 'detail' => true],
            'columns' => [
                ['label' => 'Image', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Project', 'key' => 'title', 'format' => 'strong'],
                ['label' => 'Duration', 'key' => 'start_date', 'format' => 'range', 'to' => 'end_date'],
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Project title', 'type' => 'text', 'required' => true, 'max' => 255, 'col' => 2],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Featured image', 'type' => 'image', 'hint' => 'Recommended 800x600 px. Max 5 MB.'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'End date', 'type' => 'date', 'after' => 'start_date'],
                ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'description', 'label' => 'Description', 'type' => 'richtext', 'col' => 2],
            ],
        ],

        'events' => [
            'label' => 'Upcoming events', 'singular' => 'event', 'model' => Event::class,
            'permission' => 'events.manage', 'title' => 'Manage events',
            'subtitle' => 'Programmes, camps and drives shown on the events page.',
            'search' => ['title', 'location'], 'order' => ['event_date', 'desc'],
            'public' => ['path' => 'events', 'detail' => true],
            'columns' => [
                ['label' => 'Date & time', 'key' => 'event_date', 'format' => 'eventdate'],
                ['label' => 'Image', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Event details', 'key' => 'title', 'format' => 'strong', 'sub' => 'slug'],
                ['label' => 'Location', 'key' => 'location', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Event title', 'type' => 'text', 'required' => true, 'max' => 255, 'col' => 2],
                ['name' => 'event_date', 'label' => 'Event date', 'type' => 'date', 'required' => true],
                ['name' => 'event_time', 'label' => 'Event time', 'type' => 'time'],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'max' => 255, 'placeholder' => 'e.g. New Delhi, India'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Event image', 'type' => 'image'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'richtext', 'col' => 2],
            ],
        ],

        'press-releases' => [
            'label' => 'Press releases', 'singular' => 'press release', 'model' => PressRelease::class,
            'permission' => 'press.manage', 'title' => 'Press releases',
            'subtitle' => 'Official statements and press coverage.',
            'search' => ['title'], 'order' => ['release_date', 'desc'],
            'public' => ['path' => 'press-releases', 'detail' => true],
            'columns' => [
                ['label' => 'Image', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Title', 'key' => 'title', 'format' => 'strong'],
                ['label' => 'Released', 'key' => 'release_date', 'format' => 'date'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255, 'col' => 2],
                ['name' => 'release_date', 'label' => 'Release date', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'excerpt', 'label' => 'Short excerpt', 'type' => 'textarea', 'max' => 500, 'rows' => 2, 'col' => 2],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Image', 'type' => 'image'],
                ['name' => 'description', 'label' => 'Full text', 'type' => 'richtext', 'col' => 2],
            ],
        ],

        'awards' => [
            'label' => 'Awards & recognitions', 'singular' => 'award', 'model' => Award::class,
            'permission' => 'awards.manage', 'title' => 'Awards & recognitions',
            'subtitle' => 'Achievements the organisation has received.',
            'search' => ['title', 'issuer'], 'order' => ['award_date', 'desc'],
            'public' => ['path' => 'awards', 'detail' => false],
            'columns' => [
                ['label' => 'Image', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Award', 'key' => 'title', 'format' => 'strong', 'sub' => 'issuer'],
                ['label' => 'Date', 'key' => 'award_date', 'format' => 'date'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Award title', 'type' => 'text', 'required' => true, 'max' => 255, 'col' => 2],
                ['name' => 'issuer', 'label' => 'Given by', 'type' => 'text', 'max' => 255],
                ['name' => 'award_date', 'label' => 'Award date', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Image', 'type' => 'image'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4, 'col' => 2],
            ],
        ],

        'testimonials' => [
            'label' => 'Testimonials', 'singular' => 'testimonial', 'model' => Testimonial::class,
            'permission' => 'testimonials.manage', 'title' => 'Testimonials',
            'subtitle' => 'What beneficiaries, members and partners say about the work.',
            'search' => ['name', 'message'], 'order' => ['sort_order', 'asc'],
            'columns' => [
                ['label' => 'Photo', 'key' => 'photo_path', 'format' => 'image'],
                ['label' => 'Name', 'key' => 'name', 'format' => 'strong', 'sub' => 'role'],
                ['label' => 'Message', 'key' => 'message', 'format' => 'excerpt'],
                ['label' => 'Rating', 'key' => 'rating', 'format' => 'stars'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'role', 'label' => 'Role / place', 'type' => 'text', 'max' => 150],
                ['name' => 'rating', 'label' => 'Rating', 'type' => 'select', 'required' => true, 'options' => [5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']],
                ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'message', 'label' => 'Testimonial', 'type' => 'textarea', 'required' => true, 'rows' => 4, 'col' => 2],
                ['name' => 'photo', 'column' => 'photo_path', 'label' => 'Photo', 'type' => 'image'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
        ],

        'partners' => [
            'label' => 'Partners & sponsors', 'singular' => 'partner', 'model' => Partner::class,
            'permission' => 'partners.manage', 'title' => 'Partners & sponsors',
            'subtitle' => 'Organisations that support the foundation.',
            'search' => ['name'], 'order' => ['sort_order', 'asc'],
            'columns' => [
                ['label' => 'Logo', 'key' => 'logo_path', 'format' => 'image'],
                ['label' => 'Partner name', 'key' => 'name', 'format' => 'strong'],
                ['label' => 'Website', 'key' => 'website_url', 'format' => 'link'],
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Partner name', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'website_url', 'label' => 'Website link', 'type' => 'url', 'max' => 500],
                ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'logo', 'column' => 'logo_path', 'label' => 'Partner logo', 'type' => 'image'],
            ],
        ],

        'sliders' => [
            'label' => 'Home sliders', 'singular' => 'slider', 'model' => Slider::class,
            'permission' => 'sliders.manage', 'title' => 'Manage home sliders',
            'subtitle' => 'Slides shown at the top of the home page. Lower order numbers appear first.',
            'search' => ['title'], 'order' => ['sort_order', 'asc'],
            'columns' => [
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'id'],
                ['label' => 'Slide preview', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Title', 'key' => 'title', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title (optional)', 'type' => 'text', 'max' => 255],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'min' => 0, 'max' => 9999, 'hint' => 'Lower numbers appear first.'],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Slider image', 'type' => 'image', 'required' => true, 'hint' => 'Recommended 1920x800 px landscape. Max 5 MB.'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
        ],

        'gallery' => [
            'label' => 'Photo gallery', 'singular' => 'photo', 'model' => GalleryPhoto::class,
            'permission' => 'gallery.manage', 'title' => 'Photo gallery',
            'subtitle' => 'Photographs of programmes and events.',
            'search' => ['title', 'category'], 'order' => ['sort_order', 'asc'],
            'public' => ['path' => 'gallery', 'detail' => false],
            'columns' => [
                ['label' => 'Photo', 'key' => 'image_path', 'format' => 'image'],
                ['label' => 'Title', 'key' => 'title', 'format' => 'strong', 'sub' => 'category'],
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'max' => 255],
                ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'max' => 100],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'image', 'column' => 'image_path', 'label' => 'Photo', 'type' => 'image', 'required' => true],
            ],
        ],

        'videos' => [
            'label' => 'Video gallery', 'singular' => 'video', 'model' => GalleryVideo::class,
            'permission' => 'videos.manage', 'title' => 'YouTube videos',
            'subtitle' => 'Paste a YouTube link. The video is embedded on the public videos page.',
            'search' => ['title'], 'order' => ['sort_order', 'asc'],
            'public' => ['path' => 'videos', 'detail' => false],
            'columns' => [
                ['label' => 'Title', 'key' => 'title', 'format' => 'strong'],
                ['label' => 'YouTube link', 'key' => 'youtube_url', 'format' => 'link'],
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Video title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'youtube_url', 'label' => 'YouTube link', 'type' => 'youtube', 'required' => true, 'max' => 500],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
        ],

        'leadership' => [
            'label' => 'Leadership desk', 'singular' => 'leader', 'model' => TeamMember::class,
            'permission' => 'team.manage', 'title' => 'Leadership desk',
            'subtitle' => 'Office bearers and the message from the leadership.',
            'search' => ['name', 'designation'], 'order' => ['sort_order', 'asc'],
            'filter' => ['group' => 'leadership'],
            'columns' => [
                ['label' => 'Photo', 'key' => 'photo_path', 'format' => 'image'],
                ['label' => 'Name', 'key' => 'name', 'format' => 'strong', 'sub' => 'designation'],
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'max' => 150],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'rows' => 5, 'col' => 2],
                ['name' => 'photo', 'column' => 'photo_path', 'label' => 'Photo', 'type' => 'image'],
            ],
        ],

        'governing' => [
            'label' => 'Team & governing body', 'singular' => 'member', 'model' => TeamMember::class,
            'permission' => 'team.manage', 'title' => 'Team & governing body',
            'subtitle' => 'Governing body members and the wider team.',
            'search' => ['name', 'designation'], 'order' => ['sort_order', 'asc'],
            'filter' => ['group' => 'governing'],
            'columns' => [
                ['label' => 'Photo', 'key' => 'photo_path', 'format' => 'image'],
                ['label' => 'Name', 'key' => 'name', 'format' => 'strong', 'sub' => 'designation'],
                ['label' => 'Order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'max' => 150],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'min' => 0, 'max' => 9999],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'photo', 'column' => 'photo_path', 'label' => 'Photo', 'type' => 'image'],
            ],
        ],

        'legal-documents' => [
            'label' => 'Legal documents', 'singular' => 'document', 'model' => LegalDocument::class,
            'permission' => 'documents.manage', 'title' => 'Manage documents',
            'subtitle' => 'Registration, tax and audit documents published on the site (PDF).',
            'search' => ['title', 'category'], 'order' => ['id', 'desc'],
            'columns' => [
                ['label' => 'Type', 'key' => 'file_path', 'format' => 'pdf'],
                ['label' => 'Document title', 'key' => 'title', 'format' => 'strong', 'link' => 'file'],
                ['label' => 'Category', 'key' => 'category', 'format' => 'badge'],
                ['label' => 'Uploaded on', 'key' => 'created_at', 'format' => 'date'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Document title', 'type' => 'text', 'required' => true, 'max' => 255],
                ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'required' => true, 'options' => array_combine(LegalDocument::CATEGORIES, LegalDocument::CATEGORIES)],
                ['name' => 'file', 'column' => 'file_path', 'label' => 'PDF file', 'type' => 'pdf', 'required' => true, 'hint' => 'PDF only, max 10 MB.'],
            ],
        ],

        'designations' => [
            'label' => 'Manage designations', 'singular' => 'designation', 'model' => Designation::class,
            'permission' => 'designations.manage', 'title' => 'Manage designations',
            'subtitle' => 'Posts that members can hold at each level of the organisation.',
            'search' => ['name', 'membership_level'], 'order' => ['membership_level', 'asc'],
            'columns' => [
                ['label' => 'ID', 'key' => 'id', 'format' => 'id'],
                ['label' => 'Membership level', 'key' => 'membership_level', 'format' => 'badge'],
                ['label' => 'Designation name', 'key' => 'name', 'format' => 'strong'],
                ['label' => 'Sort order', 'key' => 'sort_order', 'format' => 'text'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'membership_level', 'label' => 'Membership level', 'type' => 'select', 'required' => true, 'options' => [
                    'National' => 'National', 'Global' => 'Global', 'State' => 'State', 'District' => 'District',
                    'Mandal' => 'Mandal', 'Tehsil' => 'Tehsil', 'Block' => 'Block', 'Gram Panchayat' => 'Gram Panchayat',
                    'Village' => 'Village', 'Taluka' => 'Taluka', 'Other' => 'Other',
                ]],
                ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 9999],
                ['name' => 'name', 'label' => 'Designation name', 'type' => 'text', 'required' => true, 'max' => 150, 'placeholder' => 'e.g. Block Secretary'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
        ],

        'membership-plans' => [
            'label' => 'Membership pricing', 'singular' => 'plan', 'model' => MembershipPlan::class,
            'permission' => 'plans.manage', 'title' => 'Membership pricing management',
            'subtitle' => 'Plans and fees. Active plans are offered on the application form.',
            'search' => ['name'], 'order' => ['id', 'asc'],
            'columns' => [
                ['label' => '#', 'key' => 'id', 'format' => 'id'],
                ['label' => 'Plan name', 'key' => 'name', 'format' => 'strong', 'sub' => 'slug'],
                ['label' => 'Registration fee', 'key' => 'price', 'format' => 'money'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Plan name', 'type' => 'text', 'required' => true, 'max' => 150, 'placeholder' => 'e.g. Life Executive Member'],
                ['name' => 'price', 'label' => 'Registration fee / price (₹)', 'type' => 'money', 'required' => true, 'min' => 0],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
        ],

        'membership-certificates' => [
            'label' => 'Membership certificates', 'singular' => 'certificate', 'model' => MembershipCertificate::class,
            'permission' => 'certs.membership', 'title' => 'Membership certificates',
            'subtitle' => 'Certificates issued to members. Numbers are generated automatically (MEMB00001).',
            'search' => ['member_name', 'certificate_no', 'designation'], 'order' => ['id', 'desc'],
            'columns' => [
                ['label' => 'No.', 'key' => 'certificate_no', 'format' => 'strong'],
                ['label' => 'Member name', 'key' => 'member_name', 'format' => 'text'],
                ['label' => 'Designation', 'key' => 'designation', 'format' => 'text'],
                ['label' => 'Valid until', 'key' => 'valid_until', 'format' => 'validity'],
                ['label' => 'Issue date', 'key' => 'issue_date', 'format' => 'date'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
                ['label' => 'Certificate', 'key' => 'id', 'format' => 'download', 'route' => 'certificates.pdf'],
            ],
            'fields' => [
                ['name' => 'member_name', 'label' => 'Member name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'max' => 150],
                ['name' => 'issue_date', 'label' => 'Issue date', 'type' => 'date', 'required' => true],
                ['name' => 'valid_until', 'label' => 'Valid until (blank = lifetime)', 'type' => 'date', 'after' => 'issue_date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'notes', 'label' => 'Internal notes', 'type' => 'textarea', 'rows' => 2, 'col' => 2],
            ],
        ],

        'appointment-letters' => [
            'label' => 'Appointment letters', 'singular' => 'letter', 'model' => AppointmentLetter::class,
            'permission' => 'letters.appointment', 'title' => 'Appointment letters',
            'subtitle' => 'Letters appointing people to designations. Numbers are generated automatically (CERT00001).',
            'search' => ['recipient_name', 'letter_no', 'designation'], 'order' => ['id', 'desc'],
            'columns' => [
                ['label' => 'No.', 'key' => 'letter_no', 'format' => 'strong'],
                ['label' => 'Recipient', 'key' => 'recipient_name', 'format' => 'upper'],
                ['label' => 'Designation', 'key' => 'designation', 'format' => 'text'],
                ['label' => 'Issue date', 'key' => 'issue_date', 'format' => 'date'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status'],
            ],
            'fields' => [
                ['name' => 'recipient_name', 'label' => 'Recipient name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'designation', 'label' => 'Designation / post', 'type' => 'text', 'max' => 150],
                ['name' => 'issue_date', 'label' => 'Issue date', 'type' => 'date', 'required' => true],
                ['name' => 'valid_until', 'label' => 'Valid until (optional)', 'type' => 'date', 'after' => 'issue_date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['name' => 'reason', 'label' => 'Reason / description', 'type' => 'textarea', 'rows' => 3, 'col' => 2],
            ],
        ],
    ],

    /*
    | Permissions. 'group' sets the heading on the security page, 'coordinator' is the
    | default for the Coordinator role when the permission is first created.
    */
    'permissions' => [
        'campaigns.manage' => ['Campaigns management', 'Campaigns & financials', true],
        'donations.manage' => ['Donations ledger', 'Campaigns & financials', true],
        'about.manage' => ['About us profile', 'Events & web content', true],
        'events.manage' => ['Events portal', 'Events & web content', true],
        'testimonials.manage' => ['Testimonials management', 'Events & web content', true],
        'gallery.manage' => ['Gallery images', 'Events & web content', true],
        'videos.manage' => ['YouTube videos', 'Events & web content', true],
        'awards.manage' => ['Achievements & awards', 'Events & web content', true],
        'partners.manage' => ['Partners & sponsors', 'Events & web content', true],
        'projects.manage' => ['Our projects', 'Events & web content', true],
        'sliders.manage' => ['Homepage sliders', 'Publishing & downloads', false],
        'posts.write' => ['News & activities', 'Publishing & downloads', false],
        'press.manage' => ['Press releases', 'Publishing & downloads', false],
        'documents.manage' => ['Public documents', 'Publishing & downloads', false],
        'certs.membership' => ['Membership certificates', 'Certificates & letters', false],
        'letters.appointment' => ['Appointment letters', 'Certificates & letters', true],
        'templates.manage' => ['Letters composer & templates', 'Certificates & letters', true],
        'users.manage' => ['Users / members admin', 'System people administration', false],
        'team.manage' => ['Team members admin', 'System people administration', true],
        'volunteers.review' => ['Review volunteers', 'System people administration', true],
        'designations.manage' => ['Manage designations', 'Members & pricing', false],
        'plans.manage' => ['Membership pricing', 'Members & pricing', false],
        'email.manage' => ['Email notifications & SMTP', 'Email & payments', false],
        'gateways.manage' => ['Payment gateways', 'Email & payments', false],
        'policies.manage' => ['Policies & compliance', 'Policies & site', false],
        'site.manage' => ['Site configuration, themes & domains', 'Policies & site', false],
        'content.manage' => ['Website pages content', 'Policies & site', false],
    ],

    /*
    | Sidebar. 'module' links to a managed module, 'route' to a named route. 'permission'
    | (or 'super' => true) decides who sees the item. 'badge' names a count shown beside it.
    */
    'nav' => [
        ['label' => 'Financials & Accounting', 'icon' => 'coins', 'items' => [
            ['label' => 'Donations log', 'route' => 'donations.index', 'permission' => 'donations.manage', 'match' => 'donations.*'],
            ['label' => 'Campaigns', 'module' => 'campaigns'],
            ['label' => 'Membership payments', 'route' => 'payments.index', 'permission' => 'payments.view', 'match' => 'payments.*', 'badge' => 'payments'],
        ]],
        ['label' => 'Projects & Causes', 'icon' => 'projects', 'items' => [
            ['label' => 'Our projects', 'module' => 'projects'],
        ]],
        ['label' => 'About Organisation', 'icon' => 'building', 'items' => [
            ['label' => 'About us content', 'route' => 'pages.index', 'permission' => 'website.pages', 'match' => 'pages.*'],
            ['label' => 'Leadership desk', 'module' => 'leadership', 'permission' => 'team.manage'],
            ['label' => 'Vision & objectives', 'route' => 'pages.index', 'permission' => 'website.pages', 'match' => null],
            ['label' => 'Team & governing body', 'module' => 'governing', 'permission' => 'team.manage'],
            ['label' => 'Legal documents', 'module' => 'legal-documents'],
            ['label' => 'Partners & sponsors', 'module' => 'partners'],
        ]],
        ['label' => 'Events & Programs', 'icon' => 'calendar', 'items' => [
            ['label' => 'Upcoming events', 'module' => 'events'],
        ]],
        ['label' => 'Publishing & News', 'icon' => 'news', 'items' => [
            ['label' => 'News & activities', 'route' => 'posts.index', 'permission' => 'posts.access', 'match' => 'posts.*'],
            ['label' => 'Categories', 'route' => 'categories.index', 'permission' => 'posts.manage_all', 'match' => 'categories.*'],
            ['label' => 'Press releases', 'module' => 'press-releases'],
            ['label' => 'Awards & recognitions', 'module' => 'awards'],
            ['label' => 'Testimonials', 'module' => 'testimonials'],
        ]],
        ['label' => 'Media & Gallery', 'icon' => 'image', 'items' => [
            ['label' => 'Home sliders', 'module' => 'sliders'],
            ['label' => 'Photo gallery', 'module' => 'gallery'],
            ['label' => 'Video gallery', 'module' => 'videos'],
        ]],
        ['label' => 'Members & Volunteers', 'icon' => 'users', 'items' => [
            ['label' => 'Enrolled members', 'route' => 'members.index', 'permission' => 'members.view', 'match' => 'members.*'],
            ['label' => 'Add member', 'route' => 'members.create', 'permission' => 'members.create', 'match' => null],
            ['label' => 'Applications', 'route' => 'applications.index', 'permission' => 'members.view', 'match' => 'applications.*', 'badge' => 'applications'],
            ['label' => 'Membership pricing', 'module' => 'membership-plans'],
            ['label' => 'Manage designations', 'module' => 'designations'],
            ['label' => 'Volunteers', 'route' => 'volunteers.index', 'permission' => 'volunteers.review', 'match' => 'volunteers.*', 'badge' => 'volunteers'],
        ]],
        ['label' => 'Certificates & Letters', 'icon' => 'award', 'items' => [
            ['label' => 'Template management', 'route' => 'templates.index', 'permission' => 'templates.manage', 'match' => 'templates.*'],
            ['label' => 'Membership certs', 'module' => 'membership-certificates'],
            ['label' => 'Appointment letters', 'module' => 'appointment-letters'],
            ['label' => 'Card & letter settings', 'route' => 'card-settings.edit', 'permission' => 'card_settings.manage', 'match' => 'card-settings.*'],
        ]],
        ['label' => 'Email & Notifications', 'icon' => 'mail', 'items' => [
            ['label' => 'Send notifications', 'route' => 'notifications.index', 'permission' => 'email.manage', 'match' => 'notifications.*'],
            ['label' => 'SMTP server config', 'route' => 'smtp.edit', 'permission' => 'email.manage', 'match' => 'smtp.*'],
        ]],
        ['label' => 'Payment Gateways', 'icon' => 'card', 'items' => [
            ['label' => 'All gateways', 'route' => 'gateways.edit', 'permission' => 'gateways.manage', 'match' => 'gateways.*'],
            ['label' => 'Donation & payment settings', 'route' => 'payment-settings.edit', 'permission' => 'payment_settings.manage', 'match' => 'payment-settings.*'],
            ['label' => 'Application payments', 'route' => 'payments.index', 'permission' => 'payments.view', 'match' => null],
        ]],
        ['label' => 'Policies & Compliance', 'icon' => 'shield', 'items' => [
            ['label' => 'All policies', 'route' => 'policies.index', 'permission' => 'policies.manage', 'match' => 'policies.*'],
        ]],
        ['label' => 'Website Setup', 'icon' => 'globe', 'items' => [
            ['label' => 'Pages', 'route' => 'content.index', 'permission' => 'content.manage', 'match' => 'content.*'],
            ['label' => 'Home page', 'route' => 'home-sections.index', 'permission' => 'website.home', 'match' => 'home-sections.*'],
            ['label' => 'News ticker', 'route' => 'ticker.index', 'permission' => 'website.menus', 'match' => 'ticker.*'],
            ['label' => 'Menus & footer links', 'route' => 'site-links.index', 'permission' => 'website.menus', 'match' => 'site-links.*'],
        ]],
        ['label' => 'Online Store & Design', 'icon' => 'palette', 'items' => [
            ['label' => 'Site configuration', 'route' => 'site-config.edit', 'permission' => 'site.manage', 'match' => 'site-config.*'],
            ['label' => 'Contact messages', 'route' => 'content.messages.index', 'permission' => 'content.manage', 'match' => 'content.messages.*'],
            ['label' => 'Themes & layouts', 'route' => 'themes.index', 'permission' => 'site.manage', 'match' => 'themes.*'],
            ['label' => 'Domains & SSL', 'route' => 'domains.edit', 'permission' => 'site.manage', 'match' => 'domains.*'],
        ]],
        ['label' => 'System & Settings', 'icon' => 'sliders', 'items' => [
            ['label' => 'Organization settings', 'route' => 'settings.edit', 'permission' => 'settings.manage', 'match' => 'settings.*'],
            ['label' => 'Security permissions', 'route' => 'security.edit', 'super' => true, 'match' => 'security.*'],
            ['label' => 'Staff users', 'route' => 'users.index', 'permission' => 'users.manage', 'match' => 'users.*'],
            ['label' => 'User access', 'route' => 'access.index', 'super' => true, 'match' => 'access.*'],
            ['label' => 'Roles & permissions', 'route' => 'roles.index', 'super' => true, 'match' => 'roles.*'],
            ['label' => 'Login activity', 'route' => 'login-activity.index', 'super' => true, 'match' => 'login-activity.*'],
            ['label' => 'My profile', 'route' => 'profile.edit', 'always' => true, 'match' => 'profile.*'],
        ]],
    ],
];
