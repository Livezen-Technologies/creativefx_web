<?php

/**
 * Sinhala (සිංහල) translation of the English Reviews bundle.
 *
 * The English file — modules/Catalog/Language/en/Reviews.php — is the source of
 * truth for which keys exist. Keys are added there first, and this file follows.
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
    'form' => [
        'moderated' => 'සෑම සමාලෝචනයක්ම පළ වීමට පෙර පුද්ගලයෙක් එය කියවා බලයි. ප්‍රශංසාත්මක වුවත් නොවුවත් අප එය පළ කරමු. අප ප්‍රතික්ෂේප කරන්නේ තවත් ඉගෙනුම්කරුවෙකුගේ නම සඳහන් කරන, නැතහොත් පාඨමාලාව ගැන නොවන ඒවා පමණි.',

        'rating'    => 'ඔබේ ශ්‍රේණිගත කිරීම',
        'title'     => 'ශීර්ෂ පාඨයක් (අනිවාර්ය නොවේ)',
        'body'      => 'ඔබේ සමාලෝචනය',

        'body_hint' => 'ඔබ ඉගෙන ගැනීමට පැමිණි දේ, පන්තිය සැබවින්ම කෙබඳු වීද, සහ ඔබ පැමිණි කාර්යය දැන් ඔබට කළ හැකිද යන්න. අක්ෂර තිහක් හෝ ඊට වැඩියෙන්.',

        'name'      => 'සමාලෝචනය පළ වන නම (අනිවාර්ය නොවේ)',
        // The quoted fallback must read the same as Catalog.course.review_anon
        // in this locale; the hint is telling the reader what the review card
        // will literally say.
        'name_hint' => 'හිස්ව තැබුවහොත් ඔබේ සමාලෝචනය “ඉගෙනුම්කරුවෙක්” ලෙස පළ වේ. ඔබේ විද්‍යුත් තැපැල් ලිපිනය අප කිසිවිටෙක පළ නොකරමු.',
    ],

    'nothing_to_review' => 'අප සමඟ ඔබ හැදෑරූ සෑම පාඨමාලාවක් ගැනම ඔබ දැනටමත් සමාලෝචනයක් ලියා ඇත. ස්තුතියි — පාඨමාලා දෙකකින් එකක් තෝරා ගැනීමට සිටින ඊළඟ පුද්ගලයා කියවන්නේ ඒවාය.',

    'signed_out'        => 'මෙහි ඇති සමාලෝචන ලියා ඇත්තේ පාඨමාලාව හැදෑරූ අයයි. එබැවින් සමාලෝචනයක් තැබීමට නම්, වෙන්කිරීම කළ ගිණුමට පිවිසිය යුතුය.',

    'throttled'         => 'එය පැයක් ඇතුළත අප අපේක්ෂා කළාට වඩා උත්සාහ ගණනකි. ටිකක් රැඳී සිට නැවත උත්සාහ කරන්න.',
];
