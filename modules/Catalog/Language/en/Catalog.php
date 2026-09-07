<?php

/**
 * Every string the catalogue shows a visitor.
 *
 * Two conventions carried over from the rest of the platform: keys are grouped
 * by the thing they belong to rather than by the page they happen to appear on,
 * because the same label shows up on the course page, the card and the
 * schedule; and anything with a number in it is a placeholder rather than
 * concatenation, so a translator can put the number where their language needs
 * it.
 */
return [
    'mode' => [
        'live_online' => 'Live online',
        'classroom'   => 'In person',
        'self_paced'  => 'Self-paced',
        'private'     => 'Private',
    ],

    'level' => [
        1 => 'Beginner',
        2 => 'Intermediate',
        3 => 'Advanced',
    ],

    'duration' => [
        // Two forms each. CodeIgniter's lang() does no pluralisation, so a
        // single '{0} days' renders "1 days" on every one-day course in the
        // catalogue — which is small, and is exactly the kind of small thing
        // that makes a page look machine-generated.
        'day'   => '1 day',
        'days'  => '{0} days',
        'hour'  => '1 hour',
        'hours' => '{0} hours',
    ],

    'courses' => [
        'title'      => 'Courses',
        'training'   => 'training',
        'meta'       => 'Adobe Creative Cloud and AI training, live online, in person in Colombo, or self-paced. Real dates, real prices, small classes.',
        'all'        => 'All courses',
        'showing'    => 'Showing {0} of {1} courses',
        'none'       => 'No courses match those filters yet.',
        'none_hint'  => 'Try widening the level or the delivery mode, or browse everything.',
        'filters'    => 'Filter courses',
        'clear'      => 'Clear filters',
        'search'     => 'Search courses',
        'pillar'     => 'Subject',
        'level'      => 'Level',
        'mode'       => 'How you learn',
        'any'        => 'Any',
        'browse'     => 'Browse by subject',
    ],

    'pillar' => [
        'adobe'  => 'Adobe Creative Cloud',
        'ai'     => 'AI & Generative AI',
        'design' => 'Design & Digital',
    ],

    'card' => [
        'next'        => 'Next date',
        'or_ondemand' => 'or self-paced from {0}',
    ],

    'course' => [
        'overview'            => 'About this course',
        'outcomes'            => "What you'll be able to do",
        'audience'            => 'Who it is for',
        'prerequisites'       => 'What you need first',
        'dates'               => 'Upcoming dates',
        'curriculum'          => 'What we cover',
        'curriculum_total'    => 'About {0} hours of taught content',
        'instructor'          => 'Who teaches it',
        'instructor_more'     => 'Read more about the faculty',
        'included'            => "What's included",
        'certification'       => 'Certification',
        'certification_note'  => 'Adobe Certified Professional exams are set and awarded by Adobe through Certiport. This course prepares you for one; it does not award it, and no course can guarantee a pass.',
        'certification_hub'   => 'How Adobe certification works',
        'reviews'             => 'What learners say',
        'reviews_none'        => 'No reviews yet. This course is newly published, and we would rather show you nothing than show you something we wrote ourselves. Reviews appear here as learners leave them.',
        'review_anon'         => 'A learner',
        'rating_count'        => '{0} reviews',
        'rating_of'           => 'Rated {0} out of 5',
        'faq'                 => 'Questions people ask',
        'related'             => 'You might also want',
        'corporate_heading'   => 'Need this for a team?',
        'corporate_text'      => 'We run this course privately, on your dates, at your office or online, tailored to the work your team actually does. Tell us what you need and we will send a quote.',
        'corporate_cta'       => 'Get a quote',
    ],

    'panel' => [
        'mode_label'        => 'Choose how you want to learn',
        'from'              => 'From',
        'per_seat'          => 'per person, including materials',
        'price_on_request'  => 'Price on request in this currency.',
        'next_dates'        => 'Next dates',
        'all_dates'         => 'See all {0} dates',
        'no_dates'          => 'No dates are published for this option yet.',
        'self_paced_note'   => 'Start the moment you buy it, work at your own pace, and keep access for twelve months.',
        'seats'             => 'Number of seats',
        'book'              => 'Book now',
        'booking_for'       => 'Booking {0}. Change the date in the table below.',
        'request_quote'     => 'Request a quote',
        'email'             => 'Your email address',
        'tell_me'           => 'Tell me when a date is set',
        'assure_transfer'   => 'Free transfer to another date with 10 working days’ notice.',
        'assure_retake'     => 'One free retake within six months.',
        'assure_recording'  => 'Class recording and materials for 12 months.',
    ],

    'dates' => [
        'caption'   => 'Upcoming dates, with prices and places remaining',
        'when'      => 'When',
        'mode'      => 'How',
        'where'     => 'Where',
        'seats'     => 'Places',
        'price'     => 'Price',
        'book'      => 'Book',
        'online'    => 'Online',
        'confirmed' => 'Confirmed to run',
        'waitlist'  => 'Join the waitlist',
        'none'      => 'No dates are published for this course yet. Leave your email and we will tell you the moment one is — it is often what makes us schedule it.',
    ],

    'session' => [
        'any_time'           => 'Start any time',
        'unlimited'          => 'Always available',
        'full'               => 'Full',
        'seats_left'         => 'Only {0} left',
        'places_available'   => 'Places available',
        'title'              => '{0} — {1}',
        'joining'            => 'Joining instructions',
        'add_to_calendar'    => 'Add to calendar',
        'timezone_note'      => 'Times shown in {0}. Your calendar file converts them to your own timezone.',

        // A full class, a finished one and a withdrawn one are three different
        // facts and the reader can act on each. Saying nothing and quietly
        // showing a waitlist form leaves them to work out which they are
        // looking at.
        'not_bookable'       => 'This date is not open for booking.',
        'is_full'            => 'This date is full. Leave your address and we will write the moment a seat frees up or the next date opens.',
        'has_started'        => 'This date has already started. The next running of the same course is on the schedule.',
        'cancelled'          => 'This date has been cancelled. The course still runs — the other dates are on the schedule.',
        'withdrawn'          => 'This date has been withdrawn from sale. The other dates for this course are still open.',
    ],

    'schedule' => [
        'title'      => 'Schedule',
        'meta'       => 'Every upcoming Adobe and AI class, with prices, places and locations. Filter by month, subject or how you want to learn.',
        'intro'      => 'Every class we have scheduled. Filter it down, or leave your email against a course and we will tell you when a date is set.',
        'month'      => 'Month',
        'city'       => 'City',
        'all'        => 'All',
        'none'       => 'Nothing matches those filters.',
        'count'      => '{0} classes scheduled',
    ],

    'bundles' => [
        'certificates_title' => 'Certificate programmes',
        'certificates_meta'  => 'Multi-course programmes in graphic design, video, AI for creatives and AI for business — bought together, at a saving.',
        'certificates_intro' => 'Several courses, taken as one programme, with a through-line and a certificate at the end. Cheaper than buying them one at a time, and the courses are sequenced so each builds on the last.',
        'bootcamps_title'    => 'Bootcamps',
        'bootcamps_meta'     => 'Intensive multi-day courses that take you from the beginning to working independently in one block.',
        'bootcamps_intro'    => 'An introduction and an advanced course, back to back. The fastest route from nothing to working on your own.',
        'includes'           => 'Courses in this programme',
        'required'           => 'Required',
        'optional'           => 'Optional',
        'saving'             => 'Save {0} against buying these separately',
        'buy'                => 'Buy the programme',
        'no_seats'           => 'Buying a programme reserves your place on it, not a seat on a particular date. You choose your dates afterwards, from your account, and can take the courses in any order the sequence allows.',
        'none'               => 'No programmes are published yet.',
        'overview'           => 'About this programme',
        'view'               => 'See what is in it',
        'people'             => 'How many people',
        'separately'         => 'bought one course at a time',
        'courses_count'      => '{0} courses',
    ],

    'ondemand' => [
        'title'   => 'Self-paced courses',
        'meta'    => 'The on-demand library: video courses in Adobe Creative Cloud and AI, with exercise files, quizzes and a certificate.',
        'intro'   => 'Video courses you start the moment you buy them. Exercise files, transcripts, and twelve months of access.',
        'preview' => 'Watch a free lesson',
        // Two forms. lang() does no pluralisation, so a single '{0} lessons'
        // renders "1 lessons" on every one-lesson module.
        'lesson'  => '1 lesson',
        'lessons' => '{0} lessons',
        'none'    => 'The on-demand library is being recorded. Leave your email on any course and we will tell you when it lands.',
    ],

    'locations' => [
        'title'    => 'Where we teach',
        'meta'     => 'Adobe and AI training in Colombo, Kandy, Dubai and live online.',
        'intro'    => 'Classrooms, and the live online room. Every city page has its own upcoming dates.',
        'upcoming' => 'Coming up here',
        'none'     => 'Nothing is scheduled here at the moment.',
        'all'      => 'See the whole schedule',
        'eyebrow'  => 'Locations',
        'empty'    => 'No locations are published yet.',
        'courses_here'   => 'Courses we run here',
        'capacity'       => 'Class size',
        'capacity_note'  => 'Up to {0} people in the room',
        'timezone'       => 'Local time here',
        'map'            => 'Open the map',
        'address_note'   => 'The room and building access go out with the joining instructions for each date.',
        'online_note'    => 'There is no room to travel to. You get a joining link the day before, and the recording afterwards.',
    ],

    'instructors' => [
        'title'       => 'Who teaches here',
        'meta'        => 'The faculty behind MyLearnPlus courses in Adobe Creative Cloud and AI.',
        'intro'       => 'Practising designers, editors and engineers who teach a few days a month.',
        'teaches'     => 'Teaches',
        'placeholder' => 'This is a faculty profile rather than an individual. Named trainer profiles are published as each course is staffed.',
        'eyebrow'             => 'Faculty',
        'all'                 => 'See the whole faculty',
        'badge'               => 'Faculty profile',
        'credentials'         => 'Credentials',
        'links'               => 'Find them elsewhere',
        'none'                => 'No faculty profiles are published yet.',
        'placeholder_heading' => 'About this profile',
        'standard'            => 'What every instructor on this track must hold',
        // No pluralisation in lang(), so one form each rather than "1 courses".
        'teaches_count_one'   => 'Teaches 1 published course',
        'teaches_count'       => 'Teaches {0} published courses',
        'teaches_none'        => 'Not yet attached to a published course',
        'teaches_empty'       => 'This profile is not attached to a published course yet.',
    ],

    'reviews' => [
        'title'   => 'Reviews',
        'meta'    => 'What learners say about MyLearnPlus courses.',
        'intro'   => 'Every review here was left by somebody who took the course. We do not write them, buy them or edit them.',
        'none'    => 'No reviews have been published yet. The school is new, and we would rather have an empty page than an invented one.',
        'leave'   => 'Leave a review',
        'thanks'  => 'Thank you — your review has been sent for moderation.',
        'blocked' => 'Reviews can only be left by somebody who took the course.',
    ],

    'resources' => [
        'title'      => 'Free resources',
        'meta'       => 'Cheat sheets, prompt libraries and checklists for Adobe Creative Cloud and AI.',
        'intro'      => 'Things worth having whether or not you ever take a course with us.',
        'download'   => 'Download',
        'get'        => 'Send it to me',
        'email'      => 'Your email address',
        'consent'    => 'Email me occasional course news. You can unsubscribe in one click.',
        'sent'       => 'On its way. Check your inbox — and your spam folder, once.',
        'pending'    => 'This one is still being written. Leave your email and we will send it when it is ready.',
        'none'       => 'Nothing published yet.',
    ],

    'webinars' => [
        'title'     => 'Free webinars',
        'meta'      => 'Free live sessions on Adobe Creative Cloud and AI, with recordings afterwards.',
        'intro'     => 'An hour, free, live. Recordings go up afterwards.',
        'register'  => 'Register',
        'watch'     => 'Watch the recording',
        'upcoming'  => 'Coming up',
        'past'      => 'Past sessions',
        'none'      => 'Nothing scheduled at the moment.',
    ],

    'waitlist' => [
        'added'   => 'Thank you — we will email you the moment a date is set.',
        'invalid' => 'That email address does not look right.',
        // Not Site.assistant.error, which is what this used to borrow. That
        // string says something went wrong; being asked to wait a minute is not
        // something going wrong, and telling somebody their request failed when
        // it was merely too quick sends them to support over nothing.
        'throttled' => 'That is a few requests in quick succession. Wait a minute and try once more — nothing was lost.',
    ],

    'pillars' => [
        'adobe_title'  => 'Adobe Creative Cloud training',
        'adobe_meta'   => 'Photoshop, Illustrator, InDesign, Premiere Pro, After Effects and Firefly — live online, in person in Colombo, or self-paced.',
        'ai_title'     => 'AI and Generative AI training',
        'ai_meta'      => 'Prompt engineering, AI assistants, generative design, automation and governance — for individuals and for teams.',
        'cert_title'   => 'Adobe Certified Professional exam preparation',
        'cert_meta'    => 'How Adobe certification works, which exams exist, and which of our courses prepares you for each.',
        'explore'      => 'Explore the track',
        'all_courses'  => 'All {0} courses',

        // Added for the two pillar hubs and the certification hub. The pillar
        // pages carry their introductions here rather than in the CMS because
        // they are the two addresses the whole content plan links back to: a
        // page an editor can empty by accident is not one to hang the site's
        // internal linking on.
        'eyebrow'          => 'Training track',
        'at_a_glance'      => 'At a glance',

        // Counted things take an ICU plural rather than "{0} courses", because
        // a category with one course in it is the common case at launch and
        // "1 courses" beside it reads as a bug in the page.
        'count'            => '{0, plural, one{# course} other{# courses}}',

        'adobe_intro'      => 'Photoshop, Illustrator, InDesign, Premiere Pro, After Effects, Firefly and the rest of Creative Cloud, taught as a working craft rather than a tour of the menus. Live online in your own timezone, in the classroom in Colombo, or self-paced.',
        'adobe_body'       => [
            'Creative Cloud is not one skill. Somebody retouching a photograph, somebody laying out a hundred-page annual report and somebody cutting a two-minute film are three different jobs that happen to share a subscription. The track is arranged by application so you can find the one you actually need, and the levels within each application run in the order a working practitioner learns them rather than the order a manual is written in.',
            'Every course is built around the kind of files you would be handed at work: a brief, source material, a deadline, and an export that has to survive a printer or a platform. You leave with the finished work, the project files and a written process you can repeat on Monday. Classes are capped so the trainer can see your screen.',
            'Several of these courses are built against the published objectives of an Adobe Certified Professional exam. That certification is set by Adobe and delivered through Certiport. We prepare you for it; we do not award it, and the certification hub below explains exactly how the exam is booked and sat.',
        ],
        'adobe_categories' => 'Browse by application',

        'ai_intro'         => 'Prompt engineering, AI assistants, generative design, automation and governance, taught with your own work in front of you. For people who have to use these tools on Monday rather than form an opinion about them.',
        'ai_body'          => [
            'Most AI training is either a demonstration or a warning. This track is neither. It starts with what these tools actually are and why fluent text is not the same as correct text, and it ends with a process you have automated, a register of what your organisation is already using, and an agreed rule about what may be typed into a prompt.',
            'No coding is required. Two of the advanced courses use a short supplied JavaScript snippet, explained line by line, and nothing else in the track asks you to write a program. What the courses do assume is that you bring real work: a document you actually write, a process you actually run, a decision you actually have to defend.',
            'The tools change every few months, which is why the courses teach the discipline underneath them alongside the current interfaces: stating the task and the constraints, changing one variable at a time, keeping a record so a result can be reproduced next month. The interfaces are named and taught. The discipline is what still applies next year.',
        ],
        'ai_categories'    => 'Browse by topic',

        'categories_none'  => 'Nothing is published in this track yet. The catalogue is the fuller list, and it says honestly what is on it.',
        'featured'         => 'Where people start',
        'featured_intro'   => 'The courses most people take first. Every one of them has real dates and a real price on its own page.',
        'courses_none'     => 'No courses are published in this track yet.',

        'programmes'       => 'Take several together',
        'programmes_intro' => 'Courses from this track sold as one programme, sequenced so each builds on the last and priced below the sum of its parts. The saving is shown on each programme’s own page.',
        'programmes_none'  => 'No programmes are published for this track yet. The courses can be booked individually today, and a programme is only worth publishing once the sequence is settled.',
        'programme_courses' => '{0, plural, one{# course in the programme} other{# courses in the programme}}',

        'dates'            => 'The next dates in this track',
        'dates_intro'      => 'Real dates, with a real price and a real place. Every row books in one click.',
        'dates_none'       => 'Nothing is scheduled in this track at the moment. Tell us which course you want and we will schedule it if there is demand — that is usually what makes a date appear.',
        'schedule_all'     => 'See the whole schedule',

        'fact_courses'     => 'Courses published',
        'fact_dates'       => 'Dates open for booking',
        'fact_modes'       => 'How you can take them',
        'fact_class_size'  => 'Largest class size',

        'cert_card'        => 'Adobe Certified Professional',
        'cert_card_text'   => 'Several courses in this track are built against the published objectives of an Adobe Certified Professional exam. The exam is set by Adobe, sat through Certiport, and awarded by Adobe — not by us.',
        'cert_link'        => 'How Adobe certification works',

        'team_heading'     => 'Training a team?',
        'team_text'        => 'We run any course in this track privately, on your dates, at your office or online, built around the work your team actually does. Tell us what you need and we will send a quote and a proposed schedule.',
        'team_cta'         => 'Ask for a quote',

        // ── The certification hub ────────────────────────────────────────────
        // Every claim below is deliberately narrow. Adobe sets the objectives,
        // Certiport administers the exam, Adobe awards the credential, and this
        // school does none of those three things. Anything softer than that is
        // a sentence a buyer could act on and then discover was untrue.
        'cert_eyebrow'     => 'Adobe certification',
        'cert_intro'       => 'Adobe Certified Professional is Adobe’s own certification for its applications. Adobe writes the exam objectives, Certiport administers the exam, and Adobe awards the credential. We teach the objectives. This page explains how that works, and which of our courses prepares you for each exam.',

        'cert_disclaimer_heading' => 'Read this before you book anything',
        'cert_disclaimer'  => 'MyLearnPlus is a training provider. We do not award Adobe credentials, we do not administer Adobe exams, and no course anywhere can guarantee a pass. What we do is teach the published exam objectives, set practice tasks in the same shape as the real thing, and tell you honestly whether you look ready. The exam itself is booked, paid for and sat through Certiport, separately from any course fee.',

        'cert_how'         => 'How the certification works',
        'cert_body'        => [
            'The certification is per application. There is no single Adobe Certified Professional qualification: you sit an exam for Photoshop, or for Premiere Pro, or for whichever application you use, and you hold that credential for that application.',
            'The exams are practical. They ask you to work in the application against a brief with a clock running rather than to answer questions about it, which is why a course built around real files prepares you better than a revision guide does.',
            'Above the individual exams sit the Specialty Credentials. Those are not sat as an exam of their own: Adobe awards one when you hold two related professional certifications.',
        ],

        'cert_steps_heading' => 'From course to credential',
        'cert_steps'       => [
            'Take the course. Each course listed below is built against the published objectives for its exam and ends with a timed practice task in the same shape as the real one.',
            'Buy a voucher. Exam vouchers are bought from Certiport or an authorised reseller. They are not included in a course fee, and their price, validity and retake rules are set by Certiport and Adobe rather than by us.',
            'Book and sit the exam. Certiport administers it at an authorised testing centre, and, where Certiport offers it in your country, remotely with a proctor. You book directly with them; we have no part in scheduling it.',
            'Adobe awards the credential. If you pass, the credential and the badge come from Adobe. It is yours rather than ours, and it can be verified independently of this school.',
        ],

        'cert_exams'       => 'The exams, and what prepares you for each',
        'cert_exams_intro' => 'The certification currently covers ten applications. Beside each is the course we run that is built against its published objectives. Where we do not run one, it says so: we would rather tell you than sell you something adjacent.',
        'cert_covered'     => 'We run courses built against {0} of the {1} exams.',
        'cert_exam_sub'    => 'Adobe Certified Professional exam',
        'cert_exam_courses' => 'Courses that prepare you',
        'cert_exam_none'   => 'We do not run a course for this exam yet.',
        'cert_none'        => 'No course on the site currently states which exam it prepares for. Until one does, this page can only explain how the certification works, which it does above.',

        'cert_specialty'   => 'Specialty Credentials',
        'cert_specialty_intro' => 'Adobe also awards four Specialty Credentials. They are not separate exams: each is awarded when you hold two related professional certifications.',
        'cert_specialty_rule'  => 'Awarded on two related professional certifications',
        'cert_specialty_note'  => 'Adobe sets and updates which pairings count towards each credential. Check the current combinations on Adobe’s own certification pages before you buy exam vouchers, because a pairing that counted last year may not be the one that counts today.',
        'cert_adobe_link'  => 'Adobe’s certification pages',

        'cert_programmes'  => 'Prepare with a programme',
        'cert_programmes_intro' => 'Programmes that include one or more of the exam-aligned courses above, taken as a block rather than one at a time. The exam is still booked and sat through Certiport.',

        'cert_faq'         => 'Questions people ask about certification',
        'cert_faqs'        => [
            [
                'q' => 'Does MyLearnPlus award the Adobe Certified Professional certification?',
                'a' => 'No. Adobe awards it and Certiport administers the exam. We are a training provider: we teach the published objectives and set practice tasks in the same shape as the exam. The credential itself comes from Adobe and can be verified independently of us.',
            ],
            [
                'q' => 'Is the exam voucher included in the course fee?',
                'a' => 'No. Vouchers are bought separately from Certiport or an authorised reseller, and their price, validity and retake rules are set by Certiport and Adobe. We will tell you what one currently costs if you ask, but we do not sell them.',
            ],
            [
                'q' => 'Can you guarantee I will pass?',
                'a' => 'No, and it is worth being wary of anybody who says they can. What we can do is set a timed practice task in the shape of the real exam and tell you honestly whether you look ready. Some people are told to sit next week and some are told to spend another fortnight on export settings and colour management.',
            ],
            [
                'q' => 'Do I have to take a course before I can sit the exam?',
                'a' => 'No. The exams are open to anybody who books one through Certiport. A course is a way of preparing, not a prerequisite.',
            ],
            [
                'q' => 'Which exam should I sit first?',
                'a' => 'Usually the one for the application you already use most. The exams are practical and per application, so the fastest credential to earn is the one covering the work you do every day.',
            ],
        ],
    ],
];
