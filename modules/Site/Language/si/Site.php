<?php

/**
 * Sinhala.
 *
 * Deliberately partial, and the gaps are the point.
 *
 * What is here: the interface — navigation, buttons, form labels, the words a
 * reader needs to operate the site rather than to be persuaded by it. These are
 * standard terms with settled Sinhala equivalents, and getting them right makes
 * the site usable in Sinhala today.
 *
 * What is NOT here: the long editorial prose. The home page's pillar copy, the
 * "why us" section, the assistant's wording — anything that is a piece of
 * writing rather than a label. CodeIgniter falls back to English for a missing
 * key, so those render in English until a native Sinhala writer has produced
 * them and the school has signed them off.
 *
 * That is the honest sequence. A machine-rendered paragraph of Sinhala on a
 * commercial page is worse than the English it replaced: the reader cannot tell
 * whether the school is careless or simply hard to understand, and either
 * conclusion costs a booking. Translating a draft also produces two drafts to
 * correct instead of one.
 *
 * The Translation Manager (Admin → Translations) is where the rest is added,
 * without a deployment.
 */
return [
    'nav' => [
        'home'          => 'මුල් පිටුව',
        'breadcrumb'    => 'මාර්ගය',
        'contact'       => 'සම්බන්ධ වන්න',
        'accessibility' => 'ප්‍රවේශ්‍යතාව',
        'courses'       => 'පාඨමාලා',
        'schedule'      => 'කාලසටහන',
        'menu'          => 'මෙනුව',
        'account'       => 'මගේ ගිණුම',
        'cart'          => 'කරත්තය',
        'search'        => 'සොයන්න',
    ],

    'footer' => [
        'explore'      => 'ගවේෂණය කරන්න',
        'connect'      => 'අප අනුගමනය කරන්න',
        'subjects'     => 'විෂය අනුව බලන්න',
        'last_updated' => 'අවසන් වරට යාවත්කාලීන කළේ',
        'privacy'      => 'රහස්‍යතාව',
        'terms'        => 'නියම',
        'courses'      => 'පාඨමාලා',
        'company'      => 'සමාගම',
        'legal'        => 'නීතිමය',
    ],

    'home' => [
        'hero_cta_adobe' => 'Adobe පාඨමාලා බලන්න',
        'hero_cta_ai'    => 'AI පාඨමාලා බලන්න',
        'hero_next'      => 'ඊළඟ දින',
        'upcoming_heading' => 'ඉදිරි දින',
        'upcoming_all'   => 'සම්පූර්ණ කාලසටහන බලන්න',
        'modes_heading'  => 'පාඨමාලාවක් හැදෑරීමට ක්‍රම හතරක්',
        'programmes_all' => 'සියලු වැඩසටහන්',
        'blog_all'       => 'සියලු ලිපි',
        'news_email'     => 'ඔබගේ විද්‍යුත් තැපැල් ලිපිනය',
        'news_cta'       => 'දායක වන්න',
    ],

    'search' => [
        'title'   => 'සොයන්න',
        'button'  => 'සොයන්න',
        'label'   => 'ඔබ සොයන්නේ කුමක්ද?',
        'prev'    => 'පෙර',
        'next'    => 'ඊළඟ',
    ],

    'contact' => [
        'name'    => 'ඔබගේ නම',
        'email'   => 'විද්‍යුත් තැපැල් ලිපිනය',
        'subject' => 'විෂය',
        'message' => 'අපට උදව් කළ හැක්කේ කෙසේද?',
        'send'    => 'යවන්න',
    ],

    'careers' => [
        'title'    => 'අප සමඟ වැඩ කරන්න',
        'all'      => 'සියලු පුරප්පාඩු',
        'deadline' => 'අයදුම් කිරීමේ අවසන් දිනය',
        'phone'    => 'දුරකථනය',
        'country'  => 'රට',
        'cv'       => 'ඔබගේ ජීව දත්ත පත්‍රය',
        'submit'   => 'අයදුම්පත යවන්න',
    ],

    'news' => [
        'title' => 'මාර්ගෝපදේශ සහ ලිපි',
        'all'   => 'සියලු ලිපි',
        'back'  => 'සියලු ලිපි වෙත ආපසු',
        'read'  => 'ලිපිය කියවන්න',
        'tags'  => 'ටැග්',
        'newer' => 'නවතම',
        'older' => 'පැරණි',
    ],

    'notfound' => [
        'title' => 'එම පිටුව මෙහි නැත',
        'home'  => 'මුල් පිටුව',
    ],

    'assistant' => [
        'launcher'    => 'උදව් අවශ්‍යද?',
        'close_short' => 'වසන්න',
        'send'        => 'යවන්න',
        'placeholder' => 'ප්‍රශ්නයක් අසන්න',
    ],

    'sitemap' => [
        'title' => 'අඩවි සිතියම',
    ],

    'experience' => [
        'scroll' => 'පහළට',
    ],
];
