<?php

/*
|--------------------------------------------------------------------------
| Editable public pages
|--------------------------------------------------------------------------
| Every block below can be edited in Admin > Website pages. The 'default' is shown
| until the admin saves a value. Block types: text, richtext, image.
| Served at /page/{slug} when no database page with that slug exists.
*/

return [
    'about-organisation' => [
        'title' => 'About Us',
        'eyebrow' => 'Our Organisation',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'left'],
            'mission' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
            'vision' => ['label' => '', 'layout' => 'split', 'image' => 'left'],
            'focus' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
            'leadership' => ['label' => '', 'layout' => 'text'],
            'impact' => ['label' => '', 'layout' => 'text'],
            'why' => ['label' => '', 'layout' => 'text'],
            'programmes' => ['label' => 'Our Programmes', 'layout' => 'cards'],
        ],
        'blocks' => [
            'intro_image' => ['type' => 'image', 'label' => 'Intro image', 'default' => 'images/demo/about-intro.jpg', 'section' => 'intro'],
            'intro_title' => ['type' => 'text', 'label' => 'Intro heading', 'default' => 'Sarv Abhyudaya Foundation', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Sarv Abhyudaya Foundation is a registered non-profit organization rooted in the conviction that every human life holds infinite potential. Founded with the vision of building an inclusive, empowered, and self-reliant society, the Foundation is dedicated to serving underprivileged communities across India.</p><p>The Foundation operates from its Registered Office at C-42, First Floor, Jawahar Park, Khanpur, New Delhi, and its programmes span education, health, women empowerment, livelihood and social justice.</p>', 'section' => 'intro'],

            'mission_title' => ['type' => 'text', 'label' => 'Mission heading', 'default' => 'Our Mission', 'section' => 'mission'],
            'mission_image' => ['type' => 'image', 'label' => 'Mission image', 'default' => 'images/demo/about-mission.jpg', 'section' => 'mission'],
            'mission' => ['type' => 'richtext', 'label' => 'Mission text', 'default' => '<p>To empower lives through targeted interventions in education, healthcare, livelihood and social welfare, to bridge the gap between opportunity and need by creating access for the marginalized. To foster a culture of volunteerism, compassion and collective responsibility, and to promote gender equality so that women and girls receive equal dignity and opportunities.</p>', 'section' => 'mission'],

            'vision_title' => ['type' => 'text', 'label' => 'Vision heading', 'default' => 'Our Vision', 'section' => 'vision'],
            'vision_image' => ['type' => 'image', 'label' => 'Vision image', 'default' => 'images/demo/about-vision.jpg', 'section' => 'vision'],
            'vision' => ['type' => 'richtext', 'label' => 'Vision text', 'default' => '<p>A society where every individual, regardless of their background, enjoys access to education, health, livelihood and a life of dignity. The Foundation envisions a future where poverty is replaced by prosperity, ignorance by awareness, and helplessness by empowerment.</p>', 'section' => 'vision'],

            'focus_title' => ['type' => 'text', 'label' => 'Focus areas heading', 'default' => 'Our Focus Areas', 'section' => 'focus'],
            'focus_image' => ['type' => 'image', 'label' => 'Focus areas image', 'default' => 'images/demo/about-focus.jpg', 'section' => 'focus'],
            'focus' => ['type' => 'richtext', 'label' => 'Focus areas text', 'default' => '<p>The Foundation works across eight critical impact areas: education and literacy, women empowerment, health care, livelihood, child protection, senior citizen welfare, environmental conservation and legal aid. Each programme is built on community trust and long-term sustainability.</p>', 'section' => 'focus'],

            'leadership_title' => ['type' => 'text', 'label' => 'Leadership heading', 'default' => 'Our Leadership', 'section' => 'leadership'],
            'leadership_text' => ['type' => 'richtext', 'label' => 'Leadership text', 'default' => '<p>The Foundation is guided by its President, whose passion for social justice and grassroots development has inspired the Foundation’s work. The Foundation operates through a collective leadership model with dedicated teams for programmes, finance, legal and communications.</p>', 'section' => 'leadership'],

            'impact_title' => ['type' => 'text', 'label' => 'Impact heading', 'default' => 'Our Impact & Achievements', 'section' => 'impact'],
            'impact_text' => ['type' => 'richtext', 'label' => 'Impact text', 'default' => '<p>Since its inception, the Foundation has touched thousands of lives across Delhi and beyond. It has supported education for over 5,000 children, trained more than 2,500 women in vocational skills, screened over 15,000 people in health camps and planted 20,000 trees.</p>', 'section' => 'impact'],

            'why_title' => ['type' => 'text', 'label' => 'Why support heading', 'default' => 'Why Support Us', 'section' => 'why'],
            'why_text' => ['type' => 'richtext', 'label' => 'Why support text', 'default' => '<p>Supporting the Foundation means investing in people, not just charity. Complete transparency, tax benefits under Section 80G, a proven track record, community-led design, expert teams and low administrative overhead. When you support the Foundation, you become part of a movement to build a more just and compassionate India.</p>', 'section' => 'why'],

            'card1_title' => ['type' => 'text', 'label' => 'Card 1 heading', 'default' => 'Education Support', 'section' => 'programmes'],
            'card1_text' => ['type' => 'richtext', 'label' => 'Card 1 text', 'default' => '<p>Providing free education, study materials and scholarships to underprivileged children to ensure uninterrupted schooling.</p>', 'section' => 'programmes'],
            'card2_title' => ['type' => 'text', 'label' => 'Card 2 heading', 'default' => 'Medical Assistance', 'section' => 'programmes'],
            'card2_text' => ['type' => 'richtext', 'label' => 'Card 2 text', 'default' => '<p>Organising free health camps, distributing medicines and facilitating specialised treatment for those who cannot afford healthcare.</p>', 'section' => 'programmes'],
            'card3_title' => ['type' => 'text', 'label' => 'Card 3 heading', 'default' => 'Ration Distribution', 'section' => 'programmes'],
            'card3_text' => ['type' => 'richtext', 'label' => 'Card 3 text', 'default' => '<p>Distributing essential food ration kits to vulnerable families, migrant workers and daily wage earners in need.</p>', 'section' => 'programmes'],
            'card4_title' => ['type' => 'text', 'label' => 'Card 4 heading', 'default' => 'Youth Empowerment', 'section' => 'programmes'],
            'card4_text' => ['type' => 'richtext', 'label' => 'Card 4 text', 'default' => '<p>Empowering youth through skill development, career counselling and leadership training programmes.</p>', 'section' => 'programmes'],
            'card5_title' => ['type' => 'text', 'label' => 'Card 5 heading', 'default' => 'Women Empowerment', 'section' => 'programmes'],
            'card5_text' => ['type' => 'richtext', 'label' => 'Card 5 text', 'default' => '<p>Promoting women’s independence through vocational training, awareness programmes and support for survivors of violence.</p>', 'section' => 'programmes'],
            'card6_title' => ['type' => 'text', 'label' => 'Card 6 heading', 'default' => 'Health Support', 'section' => 'programmes'],
            'card6_text' => ['type' => 'richtext', 'label' => 'Card 6 text', 'default' => '<p>Organising regular health check-up camps, promoting hygiene, sanitation and preventive healthcare in urban slums.</p>', 'section' => 'programmes'],
            'card7_title' => ['type' => 'text', 'label' => 'Card 7 heading', 'default' => 'Environment Care', 'section' => 'programmes'],
            'card7_text' => ['type' => 'richtext', 'label' => 'Card 7 text', 'default' => '<p>Running tree plantation drives, waste management awareness and rainwater harvesting programmes in urban communities.</p>', 'section' => 'programmes'],
            'card8_title' => ['type' => 'text', 'label' => 'Card 8 heading', 'default' => 'Social Development', 'section' => 'programmes'],
            'card8_text' => ['type' => 'richtext', 'label' => 'Card 8 text', 'default' => '<p>Working towards holistic social development through policies, community participation and stakeholder engagement.</p>', 'section' => 'programmes'],
        ],
    ],
    'leadership-desk' => [
        'title' => 'Leadership Desk',
        'eyebrow' => 'Guiding Vision',
        'sections' => [
            'message' => ['label' => '', 'layout' => 'split', 'image' => 'left'],
        ],
        'blocks' => [
            'image' => ['type' => 'image', 'label' => 'President photo', 'default' => 'images/demo/leader-president.jpg', 'section' => 'message'],
            'badge' => ['type' => 'text', 'label' => 'Badge text', 'default' => "President's Message", 'section' => 'message'],
            'title' => ['type' => 'text', 'label' => 'Message heading', 'default' => 'Building Strong Communities Through Service', 'section' => 'message'],
            'intro' => ['type' => 'richtext', 'label' => 'Message text', 'default' => '<p>Sarv Abhyudaya Foundation was founded on the belief that every person deserves a better life, regardless of social status, caste, religion or income. Education, health, women empowerment, legal aid and social welfare are the foundation of our work.</p><p>I invite everyone, whether a volunteer, a donor or a member of the community, to join Sarv Abhyudaya Foundation and help build an India where no one is left behind.</p>', 'section' => 'message'],
            'closing' => ['type' => 'text', 'label' => 'Closing line', 'default' => 'Warm Regards,', 'section' => 'message'],
            'name' => ['type' => 'text', 'label' => 'President name', 'default' => 'Soumya Ranjan Biswal', 'section' => 'message'],
            'designation' => ['type' => 'text', 'label' => 'President designation', 'default' => 'President', 'section' => 'message'],
            'organisation' => ['type' => 'text', 'label' => 'Organisation name', 'default' => 'Sarv Abhyudaya Foundation', 'section' => 'message'],
        ],
    ],
    'management-team' => [
        'title' => 'Management Team',
        'eyebrow' => 'Leadership & Members',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
        ],
        'blocks' => [
            'intro_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Our Management Team', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Our management team leads day-to-day programmes, finance and partnerships. Team members are listed below.</p>', 'section' => 'intro'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null, 'section' => 'intro'],
        ],
    ],
    'our-general-members' => [
        'title' => 'Our General Members',
        'eyebrow' => 'Leadership & Members',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
        ],
        'blocks' => [
            'intro_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Our General Members', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>General members are the community leaders who carry the Foundation’s work into every district.</p>', 'section' => 'intro'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null, 'section' => 'intro'],
        ],
    ],
    'our-volunteers' => [
        'title' => 'Our Volunteers & Interns',
        'eyebrow' => 'The Backbone of Our Mission',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
        ],
        'blocks' => [
            'intro_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Our Dedicated Volunteers & Interns', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Volunteers and interns give their time to camps, distributions and classroom programmes across Delhi NCR.</p>', 'section' => 'intro'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null, 'section' => 'intro'],
        ],
    ],
    'our-supporters' => [
        'title' => 'Our Generous Donors',
        'eyebrow' => 'Support Us',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
        ],
        'blocks' => [
            'intro_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Our Generous Donors', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Every donation, large or small, funds a programme on the ground. Thank you to all our supporters.</p>', 'section' => 'intro'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null, 'section' => 'intro'],
        ],
    ],
    'news-activities' => [
        'title' => 'News & Activities',
        'eyebrow' => 'Latest Updates',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
        ],
        'blocks' => [
            'intro_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'News & Activities', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Read the latest news, camps, awards and field activities from the Foundation.</p>', 'section' => 'intro'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null, 'section' => 'intro'],
        ],
    ],
    'certifications' => [
        'title' => 'Certifications',
        'eyebrow' => 'Recognition',
        'blocks' => [
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Describe the certifications and recognition offered by the foundation.</p>'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null],
        ],
    ],
    'official-documents' => [
        'title' => 'Official Documents',
        'eyebrow' => 'Transparency',
        'sections' => [
            'intro' => ['label' => '', 'layout' => 'split', 'image' => 'right'],
        ],
        'blocks' => [
            'intro_title' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Official Documents', 'section' => 'intro'],
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Registration papers, audit reports and annual reports are published here for public access.</p>', 'section' => 'intro'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null, 'section' => 'intro'],
        ],
    ],
    'contact-us' => [
        'title' => 'Contact Us',
        'eyebrow' => 'Get In Touch',
        'blocks' => [
            'intro' => ['type' => 'richtext', 'label' => 'Intro text', 'default' => '<p>Write a short note to visitors above the contact details.</p>'],
            'image' => ['type' => 'image', 'label' => 'Page image', 'default' => null],
        ],
    ],
    'home' => [
        'title' => 'Home page',
        'eyebrow' => 'Welcome',
        'blocks' => [
            'about_image' => ['type' => 'image', 'label' => 'About Us image', 'default' => 'images/demo/home-about.jpg'],
            'about_text' => ['type' => 'richtext', 'label' => 'About Us text', 'default' => '<p>Sarv Abhyudaya Foundation is a registered non-profit organization rooted in the conviction that every human life holds infinite potential.</p>'],
            'objective_1_title' => ['type' => 'text', 'label' => 'Objective 1 title', 'default' => 'Medical Assistance'],
            'objective_1_text' => ['type' => 'text', 'label' => 'Objective 1 text', 'default' => 'Organizing free health camps, distributing medicines, and facilitating specialized treatment for those who cannot afford healthcare.'],
            'objective_1_image' => ['type' => 'image', 'label' => 'Objective 1 image', 'default' => null],
            'objective_2_title' => ['type' => 'text', 'label' => 'Objective 2 title', 'default' => 'Ration Distribution'],
            'objective_2_text' => ['type' => 'text', 'label' => 'Objective 2 text', 'default' => 'Distributing essential food ration kits to vulnerable families, migrant workers, and daily-wage earners in need.'],
            'objective_2_image' => ['type' => 'image', 'label' => 'Objective 2 image', 'default' => null],
            'objective_3_title' => ['type' => 'text', 'label' => 'Objective 3 title', 'default' => 'Youth Empowerment'],
            'objective_3_text' => ['type' => 'text', 'label' => 'Objective 3 text', 'default' => 'Empowering youth through skill development, career counselling, and leadership training programs.'],
            'objective_3_image' => ['type' => 'image', 'label' => 'Objective 3 image', 'default' => null],
            'h_events' => ['type' => 'text', 'label' => 'Heading: Upcoming Events', 'default' => 'Upcoming Events'],
            'h_activities' => ['type' => 'text', 'label' => 'Heading: Latest Activities', 'default' => 'Latest Activities'],
            'h_management' => ['type' => 'text', 'label' => 'Heading: Management Team', 'default' => 'Management Team'],
            'h_about' => ['type' => 'text', 'label' => 'Heading: About Us', 'default' => 'About Us'],
            'h_campaigns' => ['type' => 'text', 'label' => 'Heading: Active Campaigns', 'default' => 'Active Campaigns'],
            'h_objectives' => ['type' => 'text', 'label' => 'Heading: Our Objectives', 'default' => 'Our Objectives'],
            'h_awards' => ['type' => 'text', 'label' => 'Heading: Achievements & Awards', 'default' => 'Achievements & Awards'],
            'h_videos' => ['type' => 'text', 'label' => 'Heading: Our Videos', 'default' => 'Our Videos'],
            'h_gallery' => ['type' => 'text', 'label' => 'Heading: Gallery', 'default' => 'Gallery'],
            'h_news' => ['type' => 'text', 'label' => 'Heading: News', 'default' => 'News'],
            'h_donors' => ['type' => 'text', 'label' => 'Heading: Our Recent Donors', 'default' => 'Our Recent Donors'],
            'h_testimonials' => ['type' => 'text', 'label' => 'Heading: Members\' Testimonials', 'default' => 'Members\' Testimonials'],
            'h_partners' => ['type' => 'text', 'label' => 'Heading: Our Trusted Partners', 'default' => 'Our Trusted Partners'],
            'hero_image' => ['type' => 'image', 'label' => 'Hero banner image (shown when no slider is added)', 'default' => 'images/demo/home-hero.jpg'],
            'management_name' => ['type' => 'text', 'label' => 'Management Team: name (shown when no team member is added)', 'default' => 'Sandeep Rohtagi'],
            'management_role' => ['type' => 'text', 'label' => 'Management Team: designation', 'default' => 'National Secretary'],
            'management_image' => ['type' => 'image', 'label' => 'Management Team: photo', 'default' => 'images/demo/home-management.jpg'],
            'campaign1_title' => ['type' => 'text', 'label' => 'Campaign 1: title', 'default' => 'Arogya Sarv: Free Medical Camp Drive'],
            'campaign1_text' => ['type' => 'text', 'label' => 'Campaign 1: description', 'default' => 'Free healthcare camps for families in Delhi slums, covering check-ups, medicines and referrals.'],
            'campaign1_image' => ['type' => 'image', 'label' => 'Campaign 1: photo', 'default' => 'images/demo/home-campaign-1.jpg'],
            'campaign2_title' => ['type' => 'text', 'label' => 'Campaign 2: title', 'default' => 'Naari Shakti: Women Empowerment & Skill Training'],
            'campaign2_text' => ['type' => 'text', 'label' => 'Campaign 2: description', 'default' => 'Training 500 women from low-income backgrounds in tailoring, beauty and food processing.'],
            'campaign2_image' => ['type' => 'image', 'label' => 'Campaign 2: photo', 'default' => 'images/demo/home-campaign-2.jpg'],
            'campaign3_title' => ['type' => 'text', 'label' => 'Campaign 3: title', 'default' => 'Ration Mitra: Emergency Food Distribution'],
            'campaign3_text' => ['type' => 'text', 'label' => 'Campaign 3: description', 'default' => 'Ration kits for families in urban slums and migrant settlements who face hunger.'],
            'campaign3_image' => ['type' => 'image', 'label' => 'Campaign 3: photo', 'default' => 'images/demo/home-campaign-3.jpg'],
            'award1_title' => ['type' => 'text', 'label' => 'Award 1: title', 'default' => 'Global Hope Foundation Award'],
            'award1_date' => ['type' => 'text', 'label' => 'Award 1: date', 'default' => 'February 18, 2026'],
            'award1_text' => ['type' => 'text', 'label' => 'Award 1: description', 'default' => 'Awarded for rapid response and ration distribution during an emergency.'],
            'award1_image' => ['type' => 'image', 'label' => 'Award 1: certificate image', 'default' => 'images/demo/home-award-1.jpg'],
            'award2_title' => ['type' => 'text', 'label' => 'Award 2: title', 'default' => 'Best Community Impact Recognition'],
            'award2_date' => ['type' => 'text', 'label' => 'Award 2: date', 'default' => 'December 5, 2025'],
            'award2_text' => ['type' => 'text', 'label' => 'Award 2: description', 'default' => 'Honoured for green drives and sanitation campaigns with the Municipal Corporation.'],
            'award2_image' => ['type' => 'image', 'label' => 'Award 2: certificate image', 'default' => 'images/demo/home-award-2.jpg'],
            'award3_title' => ['type' => 'text', 'label' => 'Award 3: title', 'default' => 'National Empowerment Award 2025'],
            'award3_date' => ['type' => 'text', 'label' => 'Award 3: date', 'default' => 'October 12, 2025'],
            'award3_text' => ['type' => 'text', 'label' => 'Award 3: description', 'default' => 'Recognised for work on women’s self-reliance and vocational training.'],
            'award3_image' => ['type' => 'image', 'label' => 'Award 3: certificate image', 'default' => 'images/demo/home-award-3.jpg'],
            'news1_title' => ['type' => 'text', 'label' => 'News 1: title', 'default' => 'Lord Jagannath Story: Origin, Divine Form & Sacred Meaning'],
            'news1_image' => ['type' => 'image', 'label' => 'News 1: photo', 'default' => 'images/demo/home-news-1.jpg'],
            'news2_title' => ['type' => 'text', 'label' => 'News 2: title', 'default' => 'Srila Bhaktivinoda Thakur: Biography, Teachings & Householder’s Life'],
            'news2_image' => ['type' => 'image', 'label' => 'News 2: photo', 'default' => 'images/demo/home-news-2.jpg'],
            'news3_title' => ['type' => 'text', 'label' => 'News 3: title', 'default' => 'What Is Tilak? Meaning, Types, and How to Apply Tilak Correctly'],
            'news3_image' => ['type' => 'image', 'label' => 'News 3: photo', 'default' => 'images/demo/home-news-3.jpg'],
            'news4_title' => ['type' => 'text', 'label' => 'News 4: title', 'default' => '10 Most Powerful Krishna Mantras for Inner Peace and Success'],
            'news4_image' => ['type' => 'image', 'label' => 'News 4: photo', 'default' => 'images/demo/home-news-4.jpg'],
            'news5_title' => ['type' => 'text', 'label' => 'News 5: title', 'default' => 'The Path of Bhakti Yoga: Connecting with the Divine'],
            'news5_image' => ['type' => 'image', 'label' => 'News 5: photo', 'default' => 'images/demo/home-news-5.jpg'],
            'news6_title' => ['type' => 'text', 'label' => 'News 6: title', 'default' => 'Free Medical Camp at Khanpur Slum Screens 350 Residents'],
            'news6_image' => ['type' => 'image', 'label' => 'News 6: photo', 'default' => 'images/demo/home-news-6.jpg'],
            'gallery1_image' => ['type' => 'image', 'label' => 'Gallery photo 1', 'default' => 'images/demo/home-gallery-1.jpg'],
            'gallery2_image' => ['type' => 'image', 'label' => 'Gallery photo 2', 'default' => 'images/demo/home-gallery-2.jpg'],
            'gallery3_image' => ['type' => 'image', 'label' => 'Gallery photo 3', 'default' => 'images/demo/home-gallery-3.jpg'],
            'gallery4_image' => ['type' => 'image', 'label' => 'Gallery photo 4', 'default' => 'images/demo/home-gallery-4.jpg'],
            'gallery5_image' => ['type' => 'image', 'label' => 'Gallery photo 5', 'default' => 'images/demo/home-gallery-5.jpg'],
            'gallery6_image' => ['type' => 'image', 'label' => 'Gallery photo 6', 'default' => 'images/demo/home-gallery-6.jpg'],
            'gallery7_image' => ['type' => 'image', 'label' => 'Gallery photo 7', 'default' => 'images/demo/home-gallery-7.jpg'],
            'gallery8_image' => ['type' => 'image', 'label' => 'Gallery photo 8', 'default' => 'images/demo/home-gallery-8.jpg'],
            'gallery9_image' => ['type' => 'image', 'label' => 'Gallery photo 9', 'default' => 'images/demo/home-gallery-9.jpg'],
        ],
    ],
];
