<?php

/**
 * Every string the self-paced player, the certificate and the public
 * verification page show.
 *
 * Four groups in here are load-bearing rather than decorative, and each encodes
 * a decision the controller depends on:
 *
 * **The video states.** `mux` and `bunny` are in the schema because the
 * blueprint names them. Neither is contracted and neither has an adapter, so a
 * lesson pointing at one gets a named "not ready" panel rather than an empty
 * frame — an empty frame reads to a learner as a course that is broken, which
 * is a support email and a refund request rather than a provider that has not
 * been connected yet.
 *
 * **The quiz feedback.** Explanations are withheld until an attempt is passed
 * or the attempts are used up. With three attempts and ten questions, handing
 * back the mark scheme after the first failure is handing over the answers, so
 * the copy has to explain why somebody is looking at a score and not at a
 * worked solution.
 *
 * **The verification answers.** Valid, withdrawn and unknown are three
 * different sentences, and the difference matters most to the person holding
 * the certificate. A withdrawn certificate says "withdrawn"; it never quietly
 * becomes "we have never heard of this".
 *
 * **The empty states.** This site launches with no recorded lessons on most
 * courses. Every list here has a sentence for having nothing in it that is true
 * on the first day, rather than a page that renders as a silent gap.
 *
 * Keys are grouped by the thing they belong to rather than by the page they
 * happen to appear on, and anything with a number in it is a placeholder rather
 * than concatenation so a translator can put the number where their language
 * needs it. No key is ever also a group: a nested key whose parent is also a
 * key resolves to nothing and prints the key itself on the page.
 */
return [
    // A running time, in the two shapes a lesson ever needs. Separate from
    // Catalog's `duration`, which counts a taught course in days and hours: a
    // lesson is minutes, and reusing the longer forms here would print
    // "0 hours" against every seven-minute video in the library.
    'clock' => [
        'min'  => '{0} min',
        'hour' => '{0} h {1} m',
    ],

    // ── The course outline, /learn/{course} ─────────────────────────────────
    'player' => [
        'eyebrow'  => 'Your course',
        'title'    => 'Course player',
        'outline'  => 'Lessons',

        'resume'   => 'Resume',
        'start'    => 'Start the first lesson',
        'review'   => 'Review the course',

        // Two forms, because CodeIgniter's lang() does no pluralisation and
        // "1 lessons" on a one-lesson course is exactly the small wrongness
        // that makes a page look machine-generated.
        'percent'      => '{0}% complete',
        'lessons_done' => '{0} of {1} lessons complete',
        'lessons_one'  => '1 lesson',
        'lessons_many' => '{0} lessons',
        'runtime'      => '{0} of video',
        'not_started'  => 'Not started yet',

        'status_completed' => 'Completed',
        'status_started'   => 'In progress',
        'status_todo'      => 'Not started',

        'badge_preview' => 'Free preview',
        'badge_quiz'    => 'Quiz',
        'badge_final'   => 'Final assessment',
        'badge_files'   => '{0} files',
        'badge_file'    => '1 file',

        'module_untitled' => 'Lessons',

        // A course somebody has paid for, with nothing recorded against it yet.
        // Said plainly, with the two things they can actually do next, because
        // a blank page here looks like a purchase that went nowhere.
        'empty_heading' => 'The lessons for this course are not published yet',
        'empty_body'    => 'Your place is booked and nothing is lost. When the recordings go up they appear here, and everything you have already done is kept.',
        'empty_cta'     => 'Your courses',

        'certificate_heading'  => 'Certificate',
        'certificate_ready'    => 'Your certificate has been issued.',
        'certificate_view'     => 'Download your certificate',
        'certificate_pending'  => 'Finish every lesson and pass the final assessment to earn your certificate.',
        'certificate_no_final' => 'Finish every lesson to earn your certificate.',

        // The player links back into the account area but is not part of
        // its navigation, so it carries its own labels rather than reaching
        // into Account's. One less cross-module key to keep in step, and the
        // page still reads correctly if the account nav is renamed.
        'crumb_account'   => 'Your account',
        'crumb_courses'   => 'Your courses',
        'back_to_courses' => 'All your courses',
        'course_page'     => 'About this course',
    ],

    // ── One lesson, /learn/{course}/{lesson} ────────────────────────────────
    'lesson' => [
        'of'          => 'Lesson {0} of {1}',
        'previous'    => 'Previous lesson',
        'next'        => 'Next lesson',
        'outline'     => 'Back to the outline',
        'notes'       => 'Lesson notes',
        'transcript'  => 'Transcript',
        'transcript_none' => 'There is no transcript for this lesson yet.',
        'downloads'   => 'Exercise files',
        'downloads_none' => 'This lesson has no files to download.',
        'download'    => 'Download',

        'mark_complete' => 'Mark this lesson complete',
        'completed'     => 'Completed',
        'marking'       => 'Saving…',
        'mark_failed'   => 'That did not save. Check your connection and try again.',
        'progress_saved' => 'Your place is saved as you watch.',
        'resume_from'   => 'Picking up where you stopped, at {0}.',

        // The honest answer for a provider in the schema that nobody has
        // contracted yet. It names the state instead of showing a dead frame.
        'video_unconfigured_heading' => 'This recording is not ready yet',
        'video_unconfigured_body'    => 'The video for this lesson is hosted with a provider that is not connected to this site yet. The notes, the transcript and the exercise files below are all here in the meantime.',
        'video_missing_heading'      => 'This recording could not be found',
        'video_missing_body'         => 'The file for this lesson is missing from the library. We have logged it; the notes and files below are unaffected.',
        'video_no_js'                => 'Your browser cannot play this video.',
    ],

    // ── The quiz on a lesson ────────────────────────────────────────────────
    'quiz' => [
        'title'     => 'Check what you have learned',
        'final'     => 'Final assessment',
        'intro'     => 'Answer every question, then submit. Your answers are marked on our server.',
        'pass_mark' => 'Pass mark {0}%',
        'time_limit' => 'You have {0} minutes from opening this page.',

        'attempts_unlimited' => 'You can take this as many times as you like.',
        'attempts_left'      => '{0} attempts left.',
        'attempts_one'       => 'One attempt left.',
        'attempts_none'      => 'You have used all {0} attempts on this assessment. Get in touch and we will look at it with you.',

        'question'    => 'Question {0}',
        'choose_one'  => 'Choose one answer.',
        'choose_all'  => 'Choose every answer that applies.',
        'true'        => 'True',
        'false'       => 'False',
        'submit'      => 'Submit my answers',

        'result_passed' => 'You passed.',
        'result_failed' => 'Not this time.',
        'score'         => 'You scored {0}% — {1} of {2} marks.',
        'correct'       => 'Correct',
        'incorrect'     => 'Not correct',
        'try_again'     => 'Try again',

        // Why they are looking at a score rather than a mark scheme. Without
        // this the withholding reads as a bug.
        'explanations_locked' => 'The worked answers appear once you have passed, or once your attempts are used up. Showing them now would be showing you the answer sheet.',
        'explanation'         => 'Why',

        'certificate_issued' => 'That was the final assessment, and your certificate has been issued.',
        'expired'            => 'The time on this assessment ran out before it was submitted. Nothing has been marked and no attempt has been used — open the lesson again to start over.',
        'no_questions'       => 'This quiz has no questions in it yet.',
    ],

    // ── The free preview, /preview/{course}/{lesson} ────────────────────────
    'preview' => [
        'eyebrow' => 'Free preview',
        'title'   => '{0} — a free lesson from {1}',
        'meta'    => 'Watch {0} free, from the self-paced course {1}. Full transcript, no account needed.',
        'heading' => 'Watch this lesson free',
        'intro'   => 'One lesson from the course, in full, with nothing to sign up for.',

        'transcript'      => 'Transcript',
        'transcript_note' => 'The full text of this lesson, so you can read it rather than watch it.',
        'notes'           => 'Lesson notes',

        'inside_heading' => 'What else is in this course',
        'this_lesson'    => 'You are watching this',
        'inside_none'    => 'The rest of the lessons are being recorded.',

        'buy_heading'  => 'Get the whole course',
        'buy_body'     => 'Every lesson, the exercise files, the assessments and a certificate when you finish. Yours to come back to.',
        'buy_cta'      => 'See the course',
        'buy_price'    => 'Self-paced, {0}',
        'buy_no_price' => 'This course is not on sale in your currency yet. Change currency, or ask us and we will quote you.',

        'owned_heading' => 'You already have this course',
        'owned_cta'     => 'Open the full course',
    ],

    // ── The certificate, as a record and as a document ──────────────────────
    'certificate' => [
        'title'        => 'Certificate',
        'heading'      => 'Certificate of completion',
        'awarded_to'   => 'This is to certify that',
        'for'          => 'has completed',
        'issued'       => 'Issued',
        'serial'       => 'Certificate number',
        'verify_code'  => 'Verification code',
        'verify_at'    => 'Verify this certificate at',
        'mode'         => 'Studied',
        'hours'        => '{0} hours of taught content',
        'download'     => 'Download the PDF',
        'revoked'      => 'This certificate has been withdrawn.',

        // The line that keeps the school honest about what a certificate from
        // it is and is not. It appears on the document and on the verification
        // page, because those are the two places somebody decides what it is
        // worth.
        'scope_note'   => 'This certificate is issued by the school and records completion of its own course. It is not an Adobe certification: Adobe Certified Professional exams are set and awarded by Adobe through Certiport.',
    ],

    // ── /verify/{code}, the public record ───────────────────────────────────
    'verify' => [
        'title'   => 'Verify a certificate',
        'meta'    => 'Check whether a certificate issued by this school is genuine, and whether it still stands.',
        'eyebrow' => 'Certificate check',
        'heading' => 'Verify a certificate',
        'intro'   => 'Every certificate carries a code. Enter it, or scan the square on the document, and this page says what the record holds.',

        'form_label'  => 'Verification code',
        'form_hint'   => 'Printed on the certificate, under the QR square.',
        'form_submit' => 'Check this code',

        'valid_heading' => 'This certificate is genuine',
        'valid_body'    => 'The record below is the one this school holds.',

        'revoked_heading' => 'This certificate has been withdrawn',
        'revoked_body'    => 'It was issued, and it has since been withdrawn. It should not be relied on.',
        'revoked_on'      => 'Withdrawn on {0}',

        // The verification form is rate-limited, because a public endpoint that
        // answers "is this a real code" is a public endpoint somebody will feed
        // a dictionary to. The refusal is about the rate, never about the code,
        // so it can never be read as a hit.
        'throttled_heading' => 'Too many checks from here',
        'throttled_body'    => 'Wait a minute and try the code again. This is a limit on how often the form can be used, and it says nothing at all about the code you entered.',

        // Deliberately not "invalid". A mistyped code and a forged one look the
        // same from here, and only one of them is somebody doing something
        // wrong.
        'unknown_heading' => 'No certificate matches that code',
        'unknown_body'    => 'Check the code against the document — the letters and numbers are exactly as printed. If it still does not match, get in touch and we will check by hand.',

        'field_learner' => 'Awarded to',
        'field_course'  => 'Course',
        'field_issued'  => 'Issued',
        'field_serial'  => 'Certificate number',
        'field_mode'    => 'Studied',
        'field_hours'   => 'Taught hours',

        // What this page deliberately does not do. It answers one code at a
        // time and shows nothing that would let somebody walk the series.
        'privacy_note'  => 'This page answers one code at a time and shows only what is printed on the certificate itself.',
        'course_link'   => 'About this course',
    ],
];
