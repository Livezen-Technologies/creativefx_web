<?php

/**
 * Every string the learner's front door shows.
 *
 * Three things in here are load-bearing rather than decorative, and each
 * encodes a decision the controller depends on:
 *
 * **The sign-in failures.** Each reason `LearnerAuth::attempt()` can return has
 * its own honest sentence, except `bad_credentials`, which is deliberately
 * vague. A message that distinguished "no such account" from "wrong password"
 * would turn this form into a way of asking whether somebody has bought a
 * course here, and for a training school that is the customer list.
 *
 * **The registration and reset confirmations.** They are worded so the same
 * sentence is true whether or not the address is already registered — the
 * response is byte-for-byte identical in both cases, so the copy has to be a
 * sentence that does not quietly become a disclosure when read carefully.
 *
 * **The "we cannot send email yet" notes.** The site launches before its SMTP
 * account exists. Telling somebody to check an inbox nothing was sent to is
 * the kind of small lie that costs a booking, so the state is named rather
 * than hidden. It depends on the site, never on the address typed, so it
 * cannot be used to probe for accounts.
 *
 * Keys are flat within their group, following the rest of the platform: a
 * nested key whose parent is also a key resolves to nothing and prints the key
 * itself on the page.
 */
return [
    // Read by LearnerFilter before it sends anybody here, so it lives at the
    // top level rather than inside `login` — the filter has no view and no
    // page of its own to group it under.
    'sign_in_required' => 'Sign in to open that page.',

    'login' => [
        'title'    => 'Sign in',
        'meta'     => 'Sign in to reach your courses, joining links, certificates and invoices.',
        'eyebrow'  => 'Your account',
        'heading'  => 'Sign in',
        'intro'    => 'Your courses, your joining links, your certificates and your invoices.',
        'email'    => 'Email address',
        'password' => 'Password',
        'submit'   => 'Sign in',
        'forgot'   => 'Forgotten your password?',

        'no_account' => 'No account yet?',
        'create'     => 'Create one',
        // The commonest confused arrival: somebody whose employer booked their
        // seat. They have an account and have never chosen a password, so the
        // sign-in form can only ever refuse them until they are told this.
        'invited'    => 'If a colleague booked your place, an account is already waiting for you. Set a password to open it.',

        'welcome' => 'Welcome back, {0}.',

        'err_credentials' => 'That email address and password do not match.',
        'err_throttled'   => 'Too many sign-in attempts. Wait a minute, then try again.',
        'err_suspended'   => 'This account has been suspended. Get in touch and we will look into it.',
        'err_unverified'  => 'Confirm your email address before signing in. The link is in your inbox.',
    ],

    'register' => [
        'title'   => 'Create an account',
        'meta'    => 'Create a MyLearnPlus account to book a course and to keep your materials, certificates and invoices in one place.',
        'eyebrow' => 'Your account',
        'heading' => 'Create an account',
        'intro'   => 'One account holds every course you book, the materials that go with it, your certificates and your invoices.',

        'name'             => 'Full name',
        'name_hint'        => 'As it should appear on your certificate.',
        'email'            => 'Email address',
        'email_hint'       => 'Joining links, materials and certificates are sent here.',
        'password'         => 'Password',
        'password_hint'    => 'At least 12 characters. A short phrase you will remember beats a short word you will not.',
        'password_confirm' => 'Confirm password',
        'country'          => 'Country',
        'country_hint'     => 'It sets the currency you are quoted in and the timezone class times are shown in.',

        'marketing'      => 'Email me about new courses, dates and offers',
        'marketing_hint' => 'Optional, and separate from the emails about a course you have booked. You can stop at any time.',
        'terms'          => 'I accept the terms and the privacy notice',
        'terms_link'     => 'terms',
        'privacy_link'   => 'privacy notice',

        'submit'       => 'Create account',
        'have_account' => 'Already have an account?',
        'sign_in'      => 'Sign in',

        'errors_summary' => 'There is something to fix below.',
        'err_terms'      => 'You need to accept the terms before we can create the account.',
        'err_country'    => 'Choose the country you are in.',
        'err_password'   => 'Use at least 12 characters.',
        'err_password_long'  => 'That is longer than the password field can protect. Use 72 characters or fewer.',
        'err_password_match' => 'The two passwords are not the same.',
        'throttled'      => 'Too many attempts from here. Wait a few minutes, then try again.',
        'failed'         => 'Something went wrong at our end and the account was not created. Nothing has been charged. Please try again.',

        // True whichever branch the controller took, which is the point: an
        // address that is already registered gets a letter as well, so this
        // sentence never becomes a way of finding out which one happened.
        'sent_heading' => 'Check your inbox',
        'sent'         => 'We have sent a message to {0}. Open it to finish setting up your account.',
        'sent_spam'    => 'Nothing after a few minutes? Look in the spam folder, and check the address is spelled the way you meant.',
        'sent_again'   => 'Use a different address',

        'mail_off' => 'This site cannot send email yet, so no message went out. Get in touch and we will confirm your address by hand.',
    ],

    'verify' => [
        'ok' => 'Your email address is confirmed, and you are signed in.',
        // No offer to resend, because there is nothing on the site that resends
        // one yet. Promising a button that does not exist is how a support
        // email starts.
        'invalid' => 'That link is no longer valid. Confirmation links last 24 hours and work once. If you still need to confirm your address, get in touch and we will send another.',
    ],

    'forgot' => [
        'title'   => 'Reset your password',
        'meta'    => 'Ask for a link to set a new password on your MyLearnPlus account.',
        'eyebrow' => 'Your account',
        'heading' => 'Reset your password',
        'intro'   => 'Tell us the address you booked with and we will send a link to set a new password.',
        'email'   => 'Email address',
        'submit'  => 'Send the link',
        'back'    => 'Back to sign in',
        'invited' => 'If somebody booked a place for you, this is also how you open the account for the first time.',

        'sent_heading' => 'Check your inbox',
        // Says "if", and means it. The response is the same whether or not the
        // address is registered, so the sentence has to be true either way.
        'sent'         => 'If {0} has an account here, a link to set a new password is on its way. It lasts two hours.',
        'sent_spam'    => 'Nothing after a few minutes? Look in the spam folder, and check the address is spelled the way you meant.',

        'throttled' => 'Too many requests from here. Wait a few minutes, then try again.',
        'mail_off'  => 'This site cannot send email yet, so no link could be sent. Get in touch and we will help you back in.',
    ],

    'reset' => [
        'title'   => 'Choose a new password',
        'meta'    => 'Set a new password on your MyLearnPlus account.',
        'eyebrow' => 'Your account',
        'heading' => 'Choose a new password',
        'intro'   => 'At least 12 characters. Once it is saved you will be signed in.',

        'password'         => 'New password',
        'password_confirm' => 'Confirm new password',
        'submit'           => 'Save and sign in',
        'ask_again'        => 'Ask for a new link',

        'ok'      => 'Your password has been changed, and you are signed in.',
        'invalid' => 'That link is no longer valid. Reset links last two hours and work once.',
    ],

    'logout' => [
        'ok' => 'You are signed out.',
    ],

    // ── The letters ─────────────────────────────────────────────────────────
    // Kept here rather than in a view because there is no Account view
    // directory for email: the shell every transactional letter on this site
    // is rendered into already exists in the Learning module, and a second
    // copy of it would be a second thing to restyle.
    'mail' => [
        'greeting'   => 'Hello {0},',

        'verify_subject' => '{0} — confirm your email address',
        'verify_body'    => 'An account was created at {0} with this address. Confirm the address and it is ready to use.',
        'verify_cta'     => 'Confirm my email address',
        'verify_expiry'  => 'The link works once and lasts 24 hours.',
        'verify_ignore'  => 'If this was not you, ignore this message. The account cannot be used until the link is opened.',

        'exists_subject'  => '{0} — you already have an account',
        'exists_body'     => 'Somebody tried to create an account at {0} with this address. You already have one, so we have not made a second.',
        'exists_invited'  => 'A place on a course has already been booked for you at {0} using this address, so an account is waiting rather than needing to be created. Ask for a password on the form below and it is yours.',
        'exists_cta'      => 'Sign in',
        'exists_invited_cta' => 'Choose a password',
        'exists_reset'    => 'If it was you and the password will not come to mind, ask for a new one:',
        'exists_reset_cta' => 'Set a new password',
        'exists_ignore'   => 'If this was not you, nothing has changed and there is nothing you need to do.',

        'reset_subject' => '{0} — set a new password',
        'reset_body'    => 'Use the button below to set a new password on your account.',
        'reset_cta'     => 'Set a new password',
        'reset_expiry'  => 'The link works once and lasts two hours.',
        'reset_ignore'  => 'If you did not ask for this, ignore it. Your current password still works and nothing has changed.',

        'link_fallback' => 'If the button does not work, copy this address into your browser:',
    ],

    // ── The signed-in account ────────────────────────────────────────────
    //
    // These nine groups were written alongside the dashboard and were lost
    // once: this file has two authors — the sign-in flow and the account
    // area — and a whole-file write from one silently dropped the other's
    // work. 211 keys went missing and every dashboard page printed its own
    // key names at the learner. Anything added here must be merged in, not
    // written over the top.
    'nav' => [
        'title'        => 'Your account',
        'signed_in_as' => 'Signed in as',
        'overview'     => 'Overview',
        'courses'      => 'My courses',
        'certificates' => 'Certificates',
        'invoices'     => 'Invoices',
        'profile'      => 'Profile',
        'sign_out'     => 'Sign out',
    ],

    'dashboard' => [
        'title'          => 'Your account',
        'greeting'       => 'Your account',
        'greeting_named' => 'Hello, {0}',
        'intro'          => 'What is coming up, where you had got to, and anything that needs you.',

        // Only facts already in the database appear under this heading. See the
        // view: a panel that manufactures tasks is a panel people learn to skip.
        'attention'              => 'Needs your attention',
        'unverified'             => 'Your email address has not been confirmed yet.',
        'unverified_action'      => 'Confirm it',
        'unpaid'                 => 'Order {0} is waiting for payment of {1}.',
        'unpaid_action'          => 'Pay or see the details',
        'class_cancelled'        => '{0} on {1} has been cancelled by us.',
        'class_cancelled_action' => 'See what happens next',
        'transfer_pending'       => 'Your request to move {0} to another date is with us.',

        'upcoming'         => 'Coming up',
        'upcoming_none'    => 'You have no classes booked at the moment.',
        'joining_details'  => 'Joining details',

        'in_progress'      => 'In progress',
        'progress_label'   => 'Progress through {0}',
        'percent_complete' => '{0}% complete',
        'resume'           => 'Carry on',
        'start'            => 'Start',

        'empty_heading'  => 'Nothing here yet',
        'empty_body'     => 'Once you book a class or buy a self-paced course, it appears here with its dates, its joining link and everything that comes with it.',
        'browse_courses' => 'Browse courses',
        'see_dates'      => 'See upcoming dates',
    ],

    'courses' => [
        'title'         => 'My courses',
        'intro'         => 'Everything you have booked, including what has already run.',
        'self_paced'    => 'Self-paced',
        'past'          => 'Already happened',
        'past_note'     => 'These stay here: the recording, the materials and the certificate live with the course.',
        'past_caption'  => 'Courses you have already taken',
        'col_course'    => 'Course',
        'col_when'      => 'When',
        'col_status'    => 'Status',
        'col_actions'   => 'Actions',
        'open'          => 'Open',
        'empty_heading' => 'You have not booked anything yet',
        'empty_body'    => 'Pick a course and a date, and it will be here with its joining details.',
        // The self-paced licence, said where the learner can act on it. A term
        // that runs out without warning is worse than no term at all.
        'access_until'   => 'Access until {0}',
        'access_soon'    => 'Access ends on {0} — {1} days left',
        'access_ended'   => 'Access ended on {0}',
        'access_renew'   => 'Buy another twelve months',
    ],

    'status' => [
        // Not "Attended": the register may not have been marked, and the
        // account must not claim somebody was in a room on the school's behalf.
        'past'        => 'Date has passed',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
        'transferred' => 'Moved to another date',
    ],

    'live' => [
        'title' => 'Joining details',

        'when'         => 'When',
        'when_caption' => 'The days of this class, in both timezones',
        'col_day'      => 'Day',
        // {0} is the class's own timezone identifier, e.g. Asia/Colombo.
        'col_class_time' => 'Class time ({0})',
        'col_your_time'  => 'Your time ({0})',
        'col_utc'        => 'In UTC',
        'day_n'          => 'Day {0}',
        'times_tbc'      => 'Times to be confirmed',
        'in_your_time'   => 'in your timezone',
        'in_utc'         => 'UTC',
        'set_timezone'        => 'We do not have your timezone, so the second column is UTC.',
        'set_timezone_action' => 'Set your timezone',

        'where'         => 'Where',
        'where_pending' => 'The location for this class has not been set yet. We will email you as soon as it is.',
        'join_intro'    => 'Use this link to join the class. It opens a few minutes before we start.',
        'join_now'      => 'Join the class',
        'join_this_day' => 'Join this day',
        'join_note'     => 'Please do not pass this link on. A seat belongs to one person, and we check who is in the room.',
        'join_pending'  => 'Your joining link is not ready yet. It appears here, and is emailed to you, before the class.',
        'provider'      => 'Runs on {0}.',
        'directions'    => 'Directions and map',

        'prepare' => 'What to have ready',

        'materials'         => 'Materials',
        'materials_none'    => 'Nothing has been published for this course yet. Anything the instructor shares appears here.',
        'material_untitled' => 'Download',
        'download'          => 'Download',
        'open_library'      => 'Open the course library',
        'watch_recording'   => 'Watch the recording',
        'recording_pending' => 'The recording is not up yet. It appears here once it has been processed.',

        'cancelled_heading' => 'This class has been cancelled',
        'cancelled_body'    => 'We have cancelled this date. You are owed either a free move to another date or a full refund — reply to your booking email and tell us which you would prefer.',
        'cancelled_action'  => 'See other dates',

        'back_to_courses' => 'Back to my courses',
    ],

    'transfer' => [
        'heading'   => 'Move to another date',
        // {0} is RescheduleRequestModel::FREE_NOTICE_BUSINESS_DAYS.
        'free_note'    => 'You are giving {0} or more working days\' notice, so moving to another date is free.',
        'fee_note'     => 'Moving is free with {0} or more working days\' notice. Inside that, a transfer fee of {1} applies.',
        'fee_note_tbc' => 'Moving is free with {0} or more working days\' notice. Inside that a transfer fee applies, and we will confirm the amount when we look at your request.',

        'open_form'       => 'Ask to move this booking',
        'to_date'         => 'Which date would you like?',
        'to_date_any'     => 'Any later date — please suggest one',
        'no_alternatives' => 'There are no other dates in the calendar for this course yet. Ask anyway and we will find you one.',
        'reason'          => 'Anything we should know',
        'reason_hint'     => 'Optional. It helps us find you a date that works.',
        'submit'          => 'Send the request',
        'submit_note'     => 'This sends a request. Nothing moves until we confirm it, and your current seat is held until then.',

        'sent_free'         => 'Your request has been sent. Moving is free at this notice, and we will confirm your new date.',
        'sent_fee'          => 'Your request has been sent. A transfer fee applies at this notice, and we will confirm it with your new date.',
        'already_requested' => 'You already have a request on this booking. We are looking at it.',

        'pending_heading' => 'Your transfer request is with us',
        'pending_body'    => 'Nothing has moved yet, and your current seat is still held. We will email you once it is settled.',
        'pending_fee'     => 'Transfer fee: {0}.',

        'err_not_transferable' => 'That booking cannot be moved.',
        'err_bad_date'         => 'That date is not available for this course. Please pick another.',
    ],

    'certificates' => [
        'title' => 'Certificates',
        'intro' => 'Your certificates, and the link anybody can use to check one is real.',

        'issued' => 'Issued',
        'serial' => 'Serial',
        'hours'  => 'Hours',
        'mode'   => 'Studied',

        'download'   => 'Download PDF',
        'verify'     => 'Verification page',
        'share_note' => 'The verification page is public and safe to send to an employer. It shows the name, the course and the date, and it keeps working if the PDF is ever lost.',

        'revoked'      => 'Withdrawn on {0}.',
        'revoked_note' => 'That certificate has been withdrawn, so it cannot be downloaded. Its verification page still explains why.',
        'download_failed' => 'We could not produce that certificate just now. Please try again, and tell us if it keeps happening.',

        'empty_heading' => 'No certificates yet',
        'empty_body'    => 'A certificate is issued once a course is finished — attendance for a taught class, the final assessment for a self-paced one.',
        'empty_action'  => 'See my courses',

        // House rule: a course prepares somebody for the exam. Adobe awards the
        // credential, through Certiport, and the school never claims otherwise.
        'acp_note' => 'These are our own certificates of completion. Adobe Certified Professional is awarded by Adobe through Certiport, and is sat as a separate exam.',
        'acp_link' => 'How the Adobe exam works',
    ],

    'invoices' => [
        'title' => 'Invoices',
        'intro' => 'Everything you have been billed for, and the documents to go with it.',

        'caption'       => 'Your orders and invoices',
        'col_reference' => 'Reference',
        'col_date'      => 'Date',
        'col_total'     => 'Total',
        'col_status'    => 'Status',
        'col_actions'   => 'Actions',

        'proforma'      => 'Proforma',
        'view'          => 'Invoice',
        'view_proforma' => 'Proforma',
        'order'         => 'Order',

        'status_paid'           => 'Paid',
        'status_awaiting'       => 'Awaiting payment',
        'status_part_refunded'  => 'Partly refunded',
        'status_refunded'       => 'Refunded',
        'status_cancelled'      => 'Cancelled',

        'note' => 'An invoice number is issued when the payment is confirmed. Until then the document is a proforma against the order number, which is what a finance department needs in order to pay it.',

        'empty_heading' => 'Nothing to show yet',
        'empty_body'    => 'Invoices appear here as soon as you have bought something.',
    ],

    'profile' => [
        'title' => 'Profile',
        'intro' => 'Your details, how we reach you, and your password.',

        'details'          => 'Your details',
        'email'            => 'Email address',
        'email_verified'   => 'Confirmed.',
        'email_unverified' => 'Not confirmed yet.',
        'email_change'     => 'To change the address on your account, get in touch and we will move it across.',
        'first_name'       => 'First name',
        'last_name'        => 'Last name',
        'phone'            => 'Phone',
        'company'          => 'Company',
        'job_title'        => 'Job title',
        'country'          => 'Country',
        'country_help'     => 'Sets the currency you are quoted in.',
        'not_set'          => 'Not set',

        'preferences'   => 'Time and language',
        'timezone'      => 'Timezone',
        'timezone_help' => 'Class times are shown in your timezone alongside the classroom\'s.',
        'locale'        => 'Language',

        'marketing'      => 'Email me about new courses, dates and offers.',
        'marketing_note' => 'You can turn this off at any time. It does not affect emails about a course you have booked.',

        'password'      => 'Password',
        'password_note' => 'Leave these blank unless you want to change it.',
        'current_password'  => 'Current password',
        'new_password'      => 'New password',
        'confirm_password'  => 'Confirm new password',
        'password_min'      => 'At least {0} characters.',

        'save'                 => 'Save changes',
        'saved'                => 'Your profile has been saved.',
        'saved_with_password'  => 'Your profile and your password have been changed.',
        'errors_summary'       => 'There are {0} things to fix below.',
        'err_current_password' => 'That is not your current password.',
    ],
];
