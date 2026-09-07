<?php

/**
 * Sinhala (සිංහල) translation of the English Learning bundle.
 *
 * The English file — modules/Learning/Language/en/Learning.php — is the source
 * of truth for which keys exist. Keys are added there first, and this file
 * follows.
 *
 * CodeIgniter resolves a translation key by key, so a key missing from this
 * file silently renders the English string rather than erroring. That makes a
 * gap invisible on the page; scripts/check-language-keys.php --parity is what
 * reports it.
 *
 * Product, brand and exam names stay in English throughout, as does the
 * software vocabulary Sri Lankan technical writing already uses in English.
 * Mixed script is the normal register here.
 */
return [
    // {1} is zero-padded by the view, so "පැය 3 මිනිත්තු 05" is what prints.
    'clock' => [
        'min'  => 'මිනිත්තු {0}',
        'hour' => 'පැය {0} මිනිත්තු {1}',
    ],

    'player' => [
        'eyebrow'  => 'ඔබේ පාඨමාලාව',
        // A browser-title suffix. "player" stays in English: it is the word
        // used for this, and a Sinhala coinage would name nothing recognisable.
        'title'    => 'පාඨමාලා player',
        'outline'  => 'පාඩම්',

        'resume'   => 'දිගටම කරන්න',
        'start'    => 'පළමු පාඩම පටන් ගන්න',
        'review'   => 'පාඨමාලාව නැවත බලන්න',

        'percent'      => '{0}% නිම වී ඇත',
        // Sinhala counts "of N, M of them", so {1} leads and {0} follows.
        'lessons_done' => 'පාඩම් {1}න් {0}ක් නිම වී ඇත',
        'lessons_one'  => 'පාඩමක්',
        'lessons_many' => 'පාඩම් {0}ක්',
        'runtime'      => 'වීඩියෝ {0}ක්',
        'not_started'  => 'තවම පටන් ගෙන නැත',

        'status_completed' => 'නිම කර ඇත',
        'status_started'   => 'හදාරමින් පවතී',
        'status_todo'      => 'පටන් ගෙන නැත',

        'badge_preview' => 'නොමිලේ පෙරදසුන',
        'badge_quiz'    => 'ප්‍රශ්නාවලිය',
        'badge_final'   => 'අවසාන ඇගයීම',
        'badge_files'   => 'ගොනු {0}ක්',
        'badge_file'    => 'ගොනුවක්',

        'module_untitled' => 'පාඩම්',

        'empty_heading' => 'මෙම පාඨමාලාවේ පාඩම් තවම ප්‍රකාශයට පත් කර නැත',
        'empty_body'    => 'ඔබේ ආසනය වෙන් කර ඇති අතර කිසිවක් නැති වී නැත. පටිගත කිරීම් උඩුගත වූ විට ඒවා මෙහි පෙනෙනු ඇත, ඔබ දැනටමත් කර ඇති සියල්ල එසේම රැඳේ.',
        'empty_cta'     => 'ඔබේ පාඨමාලා',

        'certificate_heading'  => 'සහතිකය',
        'certificate_ready'    => 'ඔබේ සහතිකය නිකුත් කර ඇත.',
        'certificate_view'     => 'ඔබේ සහතිකය බාගන්න',
        'certificate_pending'  => 'ඔබේ සහතිකය ලබා ගැනීමට සෑම පාඩමක්ම නිම කර අවසාන ඇගයීමෙන් සමත් වන්න.',
        'certificate_no_final' => 'ඔබේ සහතිකය ලබා ගැනීමට සෑම පාඩමක්ම නිම කරන්න.',

        'crumb_account'   => 'ඔබේ ගිණුම',
        'crumb_courses'   => 'ඔබේ පාඨමාලා',
        'back_to_courses' => 'ඔබේ සියලු පාඨමාලා',
        'course_page'     => 'මෙම පාඨමාලාව ගැන',
    ],

    'lesson' => [
        // Sinhala counts "of N, the Mth one", so {1} leads and {0} follows.
        'of'          => 'පාඩම් {1}න් {0} වැනි එක',
        'previous'    => 'පෙර පාඩම',
        'next'        => 'ඊළඟ පාඩම',
        'outline'     => 'පාඩම් ලැයිස්තුවට ආපසු',
        'notes'       => 'පාඩම් සටහන්',
        'transcript'  => 'පිටපත',
        'transcript_none' => 'මෙම පාඩම සඳහා තවම පිටපතක් නැත.',
        'downloads'   => 'අභ්‍යාස ගොනු',
        'downloads_none' => 'මෙම පාඩමට බාගත කිරීමට ගොනු නැත.',
        'download'    => 'බාගන්න',

        'mark_complete' => 'මෙම පාඩම නිම කළ බව සලකුණු කරන්න',
        'completed'     => 'නිම කර ඇත',
        'marking'       => 'සුරකිමින්…',
        'mark_failed'   => 'එය සුරැකුණේ නැත. ඔබේ සම්බන්ධතාව පරීක්ෂා කර නැවත උත්සාහ කරන්න.',
        'progress_saved' => 'ඔබ බලන විටම ඔබ සිටින තැන සුරැකේ.',
        'resume_from'   => 'ඔබ නැවතුණු තැන වන {0} සිට දිගටම.',

        'video_unconfigured_heading' => 'මෙම පටිගත කිරීම තවම සූදානම් නැත',
        'video_unconfigured_body'    => 'මෙම පාඩමේ වීඩියෝව තිබෙන්නේ තවම මෙම අඩවියට සම්බන්ධ කර නොමැති සපයන්නෙකු ළඟය. එතෙක්, පහත සටහන්, පිටපත සහ අභ්‍යාස ගොනු සියල්ල මෙහි තිබේ.',
        'video_missing_heading'      => 'මෙම පටිගත කිරීම සොයාගත නොහැකි විය',
        'video_missing_body'         => 'මෙම පාඩමේ ගොනුව පුස්තකාලයේ නැත. අපි එය සටහන් කර ගෙන ඇත; පහත සටහන් සහ ගොනුවලට ඉන් බලපෑමක් නැත.',
        'video_no_js'                => 'ඔබේ browser එකට මෙම වීඩියෝව වාදනය කළ නොහැක.',
    ],

    'quiz' => [
        'title'     => 'ඔබ ඉගෙන ගත් දේ පරීක්ෂා කර බලන්න',
        'final'     => 'අවසාන ඇගයීම',
        'intro'     => 'සෑම ප්‍රශ්නයකටම පිළිතුරු දී, පසුව යොමු කරන්න. ඔබේ පිළිතුරු ලකුණු කරන්නේ අපගේ server එකේය.',
        'pass_mark' => 'සමත් ලකුණ {0}%',
        'time_limit' => 'මෙම පිටුව විවෘත කළ මොහොතේ සිට ඔබට මිනිත්තු {0}ක් තිබේ.',

        'attempts_unlimited' => 'ඔබට කැමති තරම් වාර ගණනක් මෙය කළ හැක.',
        'attempts_left'      => 'තවත් උත්සාහ {0}ක් ඉතිරියි.',
        'attempts_one'       => 'තවත් එක් උත්සාහයක් ඉතිරියි.',
        'attempts_none'      => 'මෙම ඇගයීම සඳහා තිබූ උත්සාහ {0}ම ඔබ භාවිත කර ඇත. අප හා සම්බන්ධ වන්න, අපි ඔබ සමඟ එය සොයා බලන්නෙමු.',

        'question'    => 'ප්‍රශ්නය {0}',
        'choose_one'  => 'එක් පිළිතුරක් තෝරන්න.',
        'choose_all'  => 'අදාළ වන සෑම පිළිතුරක්ම තෝරන්න.',
        'true'        => 'සත්‍යයි',
        'false'       => 'අසත්‍යයි',
        'submit'      => 'මගේ පිළිතුරු යොමු කරන්න',

        'result_passed' => 'ඔබ සමත් වී ඇත.',
        'result_failed' => 'මෙවර නම් නැත.',
        // "of {2} marks, {1}" — the total leads in Sinhala, so {2} precedes {1}.
        'score'         => 'ඔබ ලබා ඇත්තේ {0}%ක් — ලකුණු {2}න් {1}ක්.',
        'correct'       => 'නිවැරදියි',
        'incorrect'     => 'නිවැරදි නැත',
        'try_again'     => 'නැවත උත්සාහ කරන්න',

        'explanations_locked' => 'විස්තර සහිත පිළිතුරු පෙනෙන්නේ ඔබ සමත් වූ පසු, නැතහොත් ඔබේ උත්සාහ ඉවර වූ පසුය. දැන් ඒවා පෙන්වීම යනු ඔබට පිළිතුරු පත්‍රය පෙන්වීමයි.',
        'explanation'         => 'ඇයි',

        'certificate_issued' => 'එය අවසාන ඇගයීම වූ අතර, ඔබේ සහතිකය නිකුත් කර ඇත.',
        'expired'            => 'මෙම ඇගයීම යොමු කිරීමට පෙර ඒ සඳහා තිබූ කාලය ඉවර විය. කිසිවක් ලකුණු කර නැත, උත්සාහයක්ද වැය කර නැත — නැවත මුල සිට පටන් ගැනීමට පාඩම නැවත විවෘත කරන්න.',
        'no_questions'       => 'මෙම ප්‍රශ්නාවලියේ තවම ප්‍රශ්න නැත.',
    ],

    'preview' => [
        'eyebrow' => 'නොමිලේ පෙරදසුන',
        'title'   => '{0} — {1} පාඨමාලාවෙන් නොමිලේ පාඩමක්',
        // The course name leads in Sinhala, so {1} comes before {0} here.
        'meta'    => 'ස්වයං වේග පාඨමාලාව වන {1} හි {0} නොමිලේ බලන්න. සම්පූර්ණ පිටපත සමඟ, ගිණුමක් අවශ්‍ය නැත.',
        'heading' => 'මෙම පාඩම නොමිලේ බලන්න',
        'intro'   => 'පාඨමාලාවෙන් එක් පාඩමක්, සම්පූර්ණයෙන්ම, ලියාපදිංචි විය යුතු කිසිවක් නැතිව.',

        'transcript'      => 'පිටපත',
        'transcript_note' => 'මෙම පාඩමේ සම්පූර්ණ පෙළ — බලනවා වෙනුවට කියවීමට.',
        'notes'           => 'පාඩම් සටහන්',

        'inside_heading' => 'මෙම පාඨමාලාවේ තවත් තිබෙන දේ',
        'this_lesson'    => 'ඔබ බලමින් සිටින්නේ මෙයයි',
        'inside_none'    => 'ඉතිරි පාඩම් පටිගත කරමින් පවතී.',

        'buy_heading'  => 'සම්පූර්ණ පාඨමාලාව ගන්න',
        'buy_body'     => 'සෑම පාඩමක්ම, අභ්‍යාස ගොනු, ඇගයීම් සහ ඔබ නිම කළ විට සහතිකයක්. නැවත නැවත පැමිණ බැලීමට ඔබේම වේ.',
        'buy_cta'      => 'පාඨමාලාව බලන්න',
        'buy_price'    => 'ස්වයං වේගයෙන්, {0}',
        'buy_no_price' => 'මෙම පාඨමාලාව තවම ඔබේ මුදල් ඒකකයෙන් අලෙවියට නැත. මුදල් ඒකකය වෙනස් කරන්න, නැතහොත් අපෙන් අසන්න, අපි ඔබට මිලක් දෙන්නෙමු.',

        'owned_heading' => 'මෙම පාඨමාලාව දැනටමත් ඔබ සතුය',
        'owned_cta'     => 'සම්පූර්ණ පාඨමාලාව විවෘත කරන්න',
    ],

    'certificate' => [
        'title'        => 'සහතිකය',
        'heading'      => 'නිම කිරීමේ සහතිකය',
        // Sinhala is verb-final, so the sentence that runs across the document
        // splits as "මෙයින් සහතික කරනු ලබන්නේ / NAME / පහත පාඨමාලාව නිම කර ඇති
        // බවයි / COURSE" — 'for' carries the verb the English puts before it.
        'awarded_to'   => 'මෙයින් සහතික කරනු ලබන්නේ',
        'for'          => 'පහත පාඨමාලාව නිම කර ඇති බවයි',
        'issued'       => 'නිකුත් කළේ',
        'serial'       => 'සහතික අංකය',
        'verify_code'  => 'සත්‍යාපන කේතය',
        'verify_at'    => 'මෙම සහතිකය සත්‍යාපනය කළ හැක්කේ',
        'mode'         => 'හැදෑරූ ආකාරය',
        'hours'        => 'උගන්වන ලද අන්තර්ගතය පැය {0}ක්',
        'download'     => 'PDF එක බාගන්න',
        'revoked'      => 'මෙම සහතිකය ඉවත් කර ඇත.',

        'scope_note'   => 'මෙම සහතිකය නිකුත් කරන්නේ පාසල විසින් වන අතර, එයින් සටහන් වන්නේ එහිම පාඨමාලාවක් නිම කිරීමයි. මෙය Adobe සහතිකකරණයක් නොවේ: Adobe Certified Professional විභාග සකසන්නේ සහ පිරිනමන්නේ Certiport හරහා Adobe විසිනි.',
    ],

    'verify' => [
        'title'   => 'සහතිකයක් සත්‍යාපනය කරන්න',
        'meta'    => 'මෙම පාසල නිකුත් කළ සහතිකයක් සැබෑද, සහ එය තවමත් වලංගුද යන්න පරීක්ෂා කරන්න.',
        'eyebrow' => 'සහතික පරීක්ෂාව',
        'heading' => 'සහතිකයක් සත්‍යාපනය කරන්න',
        'intro'   => 'සෑම සහතිකයකම කේතයක් තිබේ. එය මෙහි ඇතුළත් කරන්න, නැතහොත් ලේඛනයේ ඇති චතුරස්‍රය scan කරන්න, එවිට වාර්තාවේ ඇති දේ මෙම පිටුව කියයි.',

        'form_label'  => 'සත්‍යාපන කේතය',
        'form_hint'   => 'සහතිකයේ, QR චතුරස්‍රයට යටින් මුද්‍රණය කර ඇත.',
        'form_submit' => 'මෙම කේතය පරීක්ෂා කරන්න',

        'valid_heading' => 'මෙම සහතිකය සැබෑය',
        'valid_body'    => 'පහත වාර්තාව මෙම පාසල සතුව ඇති එකමය.',

        'revoked_heading' => 'මෙම සහතිකය ඉවත් කර ඇත',
        'revoked_body'    => 'එය නිකුත් කරන ලද අතර, පසුව ඉවත් කරන ලදී. එය මත විශ්වාසය තැබිය යුතු නැත.',
        'revoked_on'      => '{0} දින ඉවත් කරන ලදී',

        'throttled_heading' => 'මෙතැනින් ලැබුණු පරීක්ෂා ගණන වැඩියි',
        'throttled_body'    => 'මිනිත්තුවක් රැඳී සිට කේතය නැවත උත්සාහ කරන්න. මෙය පෝරමය කොපමණ වාරයක් භාවිත කළ හැකිද යන්න මත ඇති සීමාවක් වන අතර, ඔබ ඇතුළත් කළ කේතය ගැන එයින් කිසිවක් කියැවෙන්නේ නැත.',

        'unknown_heading' => 'එම කේතයට ගැළපෙන සහතිකයක් නැත',
        'unknown_body'    => 'ලේඛනය සමඟ කේතය සසඳා බලන්න — අකුරු සහ ඉලක්කම් මුද්‍රණය කර ඇති ආකාරයටම විය යුතුය. එවිටත් නොගැළපේ නම්, අප හා සම්බන්ධ වන්න, අපි එය අතින් පරීක්ෂා කරන්නෙමු.',

        'field_learner' => 'පිරිනැමුණේ',
        'field_course'  => 'පාඨමාලාව',
        'field_issued'  => 'නිකුත් කළේ',
        'field_serial'  => 'සහතික අංකය',
        'field_mode'    => 'හැදෑරූ ආකාරය',
        'field_hours'   => 'උගන්වන ලද පැය',

        'privacy_note'  => 'මෙම පිටුව එක් වරකට එක් කේතයකට පිළිතුරු දෙන අතර, පෙන්වන්නේ සහතිකයේම මුද්‍රණය කර ඇති දේ පමණි.',
        'course_link'   => 'මෙම පාඨමාලාව ගැන',
    ],
];
