<?php

/**
 * Every string the public shell shows: the home page, the navigation, the
 * footer, the 404, the help assistant and the shared form labels.
 *
 * This is the one language file the Translation Manager reads and writes —
 * TranslationSeeder and the admin importer are both bound to `Site.php` and the
 * group name "Site" — so anything an editor should be able to reword without a
 * deployment belongs here rather than in a module's own file.
 *
 * Keys are flat within their group on purpose. A nested key whose parent is
 * also a key ("home.hero" as a string, "home.hero.primary" as a lookup)
 * resolves to nothing and renders the key itself on the page, which is a bug
 * that has already shipped once on this codebase.
 */
return [
    // ── Navigation and chrome ───────────────────────────────────────────────
    'nav' => [
        'home'          => 'Home',
        'breadcrumb'    => 'Breadcrumb',
        'contact'       => 'Contact',
        'accessibility' => 'Accessibility',
        'courses'       => 'Courses',
        'schedule'      => 'Schedule',
        'menu'          => 'Menu',
        'account'       => 'My account',
        'cart'          => 'Basket',
        'search'        => 'Search',
        'about'         => 'About us',
        'faq'           => 'Questions',
    ],

    'footer' => [
        'explore'      => 'Explore',
        'connect'      => 'Follow us',
        'subjects'     => 'Browse by subject',
        'last_updated' => 'Last updated',
        'privacy'      => 'Privacy',
        'terms'        => 'Terms',
        'rights'       => 'All rights reserved.',
        'built'        => 'A Livezen Technologies company',
        'courses'      => 'Courses',
        'company'      => 'Company',
        'legal'        => 'Legal',
    ],

    // ── Home ────────────────────────────────────────────────────────────────
    'home' => [
        'tagline' => 'Adobe Creative Cloud and AI training',
        'meta'    => 'Instructor-led Adobe Creative Cloud and AI training, live online or in Colombo, plus a self-paced library. Real dates, published prices in LKR and USD, small classes.',

        'hero_eyebrow'   => 'Adobe and AI training from Livezen Technologies',
        'hero_heading'   => 'Learn the software you are expected to use at work',
        'hero_sub'       => 'Instructor-led classes in Adobe Creative Cloud and generative AI, live online or in Colombo, plus a self-paced library. Taught on real files by working practitioners, in small groups.',
        'hero_cta_adobe' => 'Browse Adobe courses',
        'hero_cta_ai'    => 'Browse AI courses',
        'hero_note'      => 'Priced in LKR for learners in Sri Lanka and in USD everywhere else. Teams can be invoiced.',
        'hero_next'      => 'Next dates',

        'facts_label'     => 'About the catalogue',
        'fact_courses'    => 'Courses in the catalogue',
        'fact_aligned'    => 'Mapped to Adobe Certified Professional objectives',
        'fact_class_size' => 'Maximum seats in a live class',
        'fact_delivery'   => 'Ways to take a course',

        'pillars_heading' => 'Two things we teach, properly',
        'pillars_intro'   => 'We teach Adobe Creative Cloud and generative AI, plus the design and digital skills that sit alongside them. Every course is built around work you will hand to somebody else: a print-ready file, an edited cut, a working automation.',
        'pillar_adobe_title' => 'Adobe Creative Cloud',
        'pillar_adobe_text'  => 'Photoshop, Illustrator, InDesign, Premiere Pro, After Effects, Lightroom, Acrobat Pro, Adobe Express and Adobe Firefly, at introductory and advanced level. You work on live files from the first hour: retouching a portrait, drawing a logo that survives being scaled, laying out a long document with an automatic contents page, cutting and grading a short film. Courses are mapped to the published objectives of the relevant Adobe Certified Professional exam.',
        'pillar_ai_title'    => 'AI and generative AI',
        'pillar_ai_text'     => 'Foundations for people with no technical background, prompt engineering, AI assistants at work, generative AI for designers, AI video and audio, marketing, automation and agents, building with LLM APIs, and governance. The emphasis is on judgement as much as technique: what these tools do well, where they fail, and how to check output before it reaches a client.',

        'upcoming_heading' => 'Upcoming dates',
        'upcoming_intro'   => 'Live online sessions run in Sri Lanka Standard Time and are scheduled to suit learners across South Asia, the Gulf and the UK. Classroom dates run in Colombo. Every live booking includes the class recording.',
        'upcoming_all'     => 'See the whole schedule',
        'upcoming_caption' => 'The next scheduled classes, with delivery mode, places remaining and price',
        'upcoming_none'    => 'No public dates are open for booking yet. Tell us which course you want and the weeks that suit you, and we will contact you as soon as a date is scheduled. Private and team sessions can be arranged on your own dates now.',

        'modes_heading' => 'Four ways to take a course',
        'modes_intro'   => 'The same syllabus, the same exercise files and the same instructor standard across every mode. Pick the one that fits your week.',
        'mode_live_online' => 'A scheduled class with a live instructor, in small groups, so you can ask a question and get an answer in the moment. You work in your own copy of the software and can share your screen when something goes wrong. Sessions are recorded and the recording is yours.',
        'mode_classroom'   => 'In-person teaching for people who work better away from their desk and their inbox. Machines and software are provided, or bring your own laptop and leave with everything set up the way you use it. Class sizes are capped so the instructor can sit beside you.',
        'mode_self_paced'  => 'The same material recorded and structured into short modules, with the exercise files, the workbook and the practice questions. Progress is tracked in your dashboard, so you can stop mid-module and pick up where you left off.',
        'mode_private'     => 'The course run for one organisation, online or at your premises, on dates you choose. The syllabus can be rebuilt around your own templates and the files your team actually works on.',

        'programmes_all' => 'All programmes',

        'why_heading' => 'Why learners choose us',
        'why_1_title' => 'You build something in every session',
        'why_1_text'  => 'Each class produces a finished artefact: a layered retouch, a logo in the formats a printer and a developer will each accept, a report with an automatic contents page, a working automation. Every outcome listed on a course page has a module behind it where you build the thing.',
        'why_2_title' => 'Taught by people who do the work',
        'why_2_text'  => 'Instructors are practitioners who still produce client work, so the answer to "what do you actually do on a real job" is a real answer rather than a slide. Classes are small enough for the instructor to look at your screen.',
        'why_3_title' => 'Mapped to the published exam objectives',
        'why_3_text'  => 'Adobe courses are built against the current Adobe Certified Professional objectives for that application. You finish knowing which objectives you are solid on and which need more practice before you book a seat at a test centre.',
        'why_4_title' => 'Dual pricing, no currency games',
        'why_4_text'  => 'Prices are shown in LKR for learners in Sri Lanka and in USD for everyone else, on the course page, before you reach the checkout. Companies can be invoiced and can book several seats on one purchase order.',
        'why_5_title' => 'The material stays with you',
        'why_5_text'  => 'Exercise files, the finished sample projects, the workbook and the class recording remain available for the period stated on the booking page, so a step you half-followed on the day is there to watch again.',
        'why_6_title' => 'Honest about AI',
        'why_6_text'  => 'We teach where generative tools help and where they do not, what must never be generated, and how to check output before it reaches a client. Judgement is on the syllabus, not just technique.',

        'featured_heading' => 'Popular courses',

        'corporate_heading' => 'Training for teams',
        'corporate_text'    => 'Run any course privately for your organisation, online or at your premises in Sri Lanka, on dates that suit your schedule. We can rebuild the exercises around your own brand templates and the files your team actually works on. You get attendance records, a certificate for each participant, and a single invoice with a purchase order reference.',
        'corporate_cta'     => 'Request a team quote',

        'blog_heading' => 'Guides and articles',
        'blog_all'     => 'All articles',
        'reading_min'  => '{0} min read',

        'news_heading'  => 'New dates and new courses',
        'news_text'     => 'One email a month: newly scheduled class dates, courses added to the catalogue, and a short practical guide or cheat sheet. No sales sequences, and we do not share your address with anyone.',
        'news_email'    => 'Your email address',
        'news_cta'      => 'Subscribe',
        'news_consent'  => 'I agree to receive email from MyLearnPlus. I can unsubscribe from any email, and can ask for my details to be deleted at any time.',
        'news_thanks'   => 'Thank you — you are on the list. The next one goes out at the start of the month.',
        'news_invalid'  => 'That email address does not look right.',
    ],

    // ── Search ──────────────────────────────────────────────────────────────
    'search' => [
        'title'   => 'Search',
        'meta'    => 'Search courses, dates, guides and pages.',
        'button'  => 'Search',
        'label'   => 'What are you looking for?',
        'none'    => 'Nothing matched that. Try a course name, an application, or a topic.',
        'results' => '{0} results for “{1}”',
        'prev'    => 'Previous',
        'next'    => 'Next',
        'page_of' => 'Page {0} of {1}',
        'type_page' => 'Page',
    ],

    // ── Contact ─────────────────────────────────────────────────────────────
    'contact' => [
        'name'    => 'Your name',
        'email'   => 'Your email address',
        'subject' => 'Subject',
        'message' => 'How can we help?',
        'send'    => 'Send',
        'success' => 'Thank you — we have your message and will reply within one working day.',
        'failed'  => 'Something went wrong sending that. Please try again, or email us directly.',
    ],

    // ── Careers (the school's own vacancies) ────────────────────────────────
    'careers' => [
        'title'         => 'Work with us',
        'meta'          => 'Teaching and support roles at MyLearnPlus.',
        'all'           => 'All vacancies',
        'none'          => 'No vacancies are open at the moment.',
        'about_role'    => 'About the role',
        'qualifications'=> 'What we are looking for',
        'skills'        => 'Skills',
        'experience'    => 'Experience',
        'education'     => 'Education',
        'deadline'      => 'Closing date',
        'apply_title'   => 'Apply for this role',
        'your_skills'   => 'Tell us about your relevant work',
        'cover_letter'  => 'Anything else we should know',
        'cv'            => 'Your CV',
        'phone'         => 'Telephone',
        'country'       => 'Country',
        'submit'        => 'Send application',
        'applied_title' => 'Application received',
        'applied_body'  => 'Thank you. We read every application and reply to everybody, whether or not we take it further.',
    ],

    // ── News / blog ─────────────────────────────────────────────────────────
    'news' => [
        'title'   => 'Guides and articles',
        'meta'    => 'Practical writing on Adobe Creative Cloud, generative AI, certification and design careers.',
        'eyebrow' => 'From the blog',
        'intro'   => 'Practical writing on the same subjects we teach.',
        'all'     => 'All articles',
        'back'    => 'Back to all articles',
        'read'    => 'Read the article',
        'related' => 'Related reading',
        'tags'    => 'Tagged',
        'none'    => 'Nothing published yet.',
        'newer'   => 'Newer',
        'older'   => 'Older',
    ],
    'announcements' => [
        'title' => 'Announcements',
        'meta'  => 'Course and schedule announcements from MyLearnPlus.',
    ],

    // ── 404 ─────────────────────────────────────────────────────────────────
    'notfound' => [
        'meta_title' => 'Page not found',
        'meta'       => 'That address does not exist on this site.',
        'eyebrow'    => 'Not found',
        'title'      => 'That page is not here',
        'body'       => 'The address may have changed, or it may never have existed. The catalogue and the schedule are the two places most links point at.',
        'try'        => 'Try one of these',
        'home'       => 'Home',
    ],

    // ── The human sitemap ───────────────────────────────────────────────────
    'sitemap' => [
        'title' => 'Site map',
        'meta'  => 'Every section of the site on one page.',
        'intro' => 'Every part of the site, in one list. Useful if the menu is not showing you what you expected.',
    ],

    // ── The help assistant ──────────────────────────────────────────────────
    'assistant' => [
        'title'       => 'Need help?',
        'status'      => 'Automated — answers from this website',
        'launcher'    => 'Need help?',
        'close'       => 'Close the help panel',
        'close_short' => 'Close',
        'today'       => 'Today',
        'greeting'    => 'Hello. I can answer from the course pages, the schedule and the FAQ on this site.',
        'prompt'      => 'People usually want one of these',
        'topic_courses'  => 'Which course should I take?',
        'topic_dates'    => 'When is the next class?',
        'topic_corporate'=> 'Training for my team',
        'placeholder' => 'Ask a question',
        'send'        => 'Send',
        'thinking'    => 'Looking…',
        'related'     => 'This might help',
        'source'      => 'From',
        'search_all'  => 'Search the whole site',
        'none'        => 'I could not find an answer to that on this site.',
        'none_help'   => 'A person can answer it properly — the contact page has the ways to reach us.',
        'error'       => 'Something went wrong looking that up. Please try again.',
        'note'        => 'Answers come from this site’s own pages. Nothing you type leaves our server.',
    ],

    // ── Shared form and booking messages ────────────────────────────────────
    'booking' => [
        'err_robot' => 'We could not verify that you are human. Please try again.',
        'err_past'  => 'That date has already passed.',
        'err_order' => 'Something went wrong. Nothing has been charged — please try again.',
    ],

    'whatsapp' => [
        'aria' => 'Chat with us on WhatsApp',
        'hint' => 'Chat on WhatsApp',
    ],

    'experience' => [
        'scroll' => 'Scroll',
        'theme'  => 'Switch between light and dark',
    ],
];
