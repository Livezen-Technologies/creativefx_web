<?php

/**
 * The strings the reviews page needs and `Catalog.reviews` does not yet carry.
 *
 * They belong beside `Catalog.reviews` and are kept in a bundle of their own so
 * that the reviews page can carry its own copy without a second hand editing
 * the shared catalogue bundle. Nothing depends on the split — CodeIgniter
 * resolves a bundle from the segment before the first dot, so `Reviews.form.body`
 * finds this file the same way `Catalog.reviews.title` finds Catalog.php — and
 * folding these into that file's `reviews` group later is a rename and nothing
 * else. It is worth doing: a key that goes missing in a merge is not an error,
 * it is a page that prints "Reviews.form.body" at a visitor.
 *
 * The copy carries the same rule the page does. A review is written by somebody
 * who took the course, a person reads it before it appears, and none of the
 * text here pretends otherwise or thanks anybody for a review that has not been
 * published yet.
 */
return [
    'form' => [
        // Said above the fields rather than under the button. Somebody who has
        // just written three paragraphs and only then learns their words are
        // queued for approval feels caught out; somebody told first does not.
        'moderated' => 'A person reads every review before it appears, and we publish it whether it is kind or not. The only ones we refuse are those that name another learner, or that are not about the course.',

        'rating'    => 'Your rating',
        'title'     => 'A headline (optional)',
        'body'      => 'Your review',

        // Asks for the specific thing that makes a review useful to the next
        // reader — what they came to do, and whether they can now do it —
        // because "tell us what you thought" reliably produces "it was good".
        'body_hint' => 'What you came to learn, what the class was actually like, and whether you can now do the thing you came for. Thirty characters or more.',

        'name'      => 'The name to publish it under (optional)',
        // The fallback is named so that leaving the field empty is a choice
        // rather than a gamble, and the promise about the address is made here
        // because this is the field where somebody would otherwise worry.
        'name_hint' => 'Left empty, your review appears as “A learner”. We never publish your email address.',
    ],

    // For a learner who has already reviewed everything they have taken. It
    // thanks them for what they did rather than apologising for having nothing
    // to offer them.
    'nothing_to_review' => 'You have reviewed every course you have taken with us. Thank you — the next person choosing between two courses is reading them.',

    // For a signed-out visitor. It states the rule rather than simply demanding
    // a sign-in: most people who read this page have no account here and never
    // will, and being told why the form is not theirs is more use than a login
    // screen they would bounce off.
    'signed_out'        => 'Reviews here are written by people who took the course, so leaving one means signing in to the account the booking was made with.',

    'throttled'         => 'That is more attempts than we expected within an hour. Wait a little, then try again.',
];
