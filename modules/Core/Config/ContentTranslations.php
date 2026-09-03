<?php

/**
 * Content translation dictionary: English source => the same line in Sinhala
 * and Tamil.
 *
 * Seeders author content in English and content_locales() completes it from
 * here, so a wording lives in one place and adding a language is a matter of
 * adding a key rather than touching any seeder. A phrase that is missing falls
 * back to English at render time, which is why a partial dictionary is safe.
 *
 * What is here and what is not, deliberately:
 *
 *   Every title, label, category and short summary the Authority's records
 *   carry — the things that appear in menus, on cards, in tables and in the
 *   sidebar of a service page, where an untranslated string is conspicuous and
 *   makes a page look half-built.
 *
 *   The long editorial prose — the 500-word About Us and subject-area pages,
 *   the FAQ answers, the news articles — is not. Those are drafts for the
 *   Content Management Team to correct and sign off before they are worth
 *   translating, and translating a draft produces three drafts to correct
 *   instead of one. They are translated in the Translation Manager once the
 *   English is agreed, which is the sequence Clause 3.10 assumes when it makes
 *   Livezen responsible for "collecting, writing, preparing, translating and
 *   typing all content".
 */

return [
    // ── Services ────────────────────────────────────────────────────────────
    'Replanting subsidy' => [
        'si' => 'නැවත වගා කිරීමේ සහනාධාරය',
        'ta' => 'மீள்நடுகை மானியம்',
    ],
    'New planting subsidy' => [
        'si' => 'නව වගා කිරීමේ සහනාධාරය',
        'ta' => 'புதிய நடுகை மானியம்',
    ],
    'Fertilizer assistance' => [
        'si' => 'පොහොර ආධාර',
        'ta' => 'உர உதவி',
    ],
    'Soil conservation assistance' => [
        'si' => 'පාංශු සංරක්ෂණ ආධාර',
        'ta' => 'மண் பாதுகாப்பு உதவி',
    ],
    'Extension and technical advisory' => [
        'si' => 'ව්‍යාප්ති සහ තාක්ෂණික උපදේශන සේවා',
        'ta' => 'விரிவாக்கல் மற்றும் தொழினுட்ப ஆலோசனை',
    ],
    'Smallholder registration' => [
        'si' => 'කුඩා වතු ලියාපදිංචිය',
        'ta' => 'சிறு தோட்டப் பதிவு',
    ],
    'Tea Smallholder Development Society registration' => [
        'si' => 'තේ කුඩා වතු සංවර්ධන සමිති ලියාපදිංචිය',
        'ta' => 'தேயிலைச் சிறு தோட்ட அபிவிருத்திச் சங்கப் பதிவு',
    ],
    'Tea Shakthi programmes' => [
        'si' => 'තේ ශක්ති වැඩසටහන්',
        'ta' => 'தேயிலை சக்தி நிகழ்ச்சிகள்',
    ],
    'Training at the Hantana National Training Centre' => [
        'si' => 'හන්තාන ජාතික පුහුණු මධ්‍යස්ථානයේ පුහුණුව',
        'ta' => 'ஹந்தான தேசிய பயிற்சி நிலையத்தில் பயிற்சி',
    ],
    'Tea nursery registration' => [
        'si' => 'තේ තවාන් ලියාපදිංචිය',
        'ta' => 'தேயிலை நாற்றுமேடைப் பதிவு',
    ],
    'Right to Information request' => [
        'si' => 'තොරතුරු දැනගැනීමේ අයිතිය පිළිබඳ ඉල්ලීම',
        'ta' => 'தகவல் அறியும் உரிமைக் கோரிக்கை',
    ],
    'Recruitment and vacancies' => [
        'si' => 'බඳවා ගැනීම් සහ පුරප්පාඩු',
        'ta' => 'ஆட்சேர்ப்பும் வெற்றிடங்களும்',
    ],

    // ── Divisions and contact points ────────────────────────────────────────
    'Land Development & Extension Services' => [
        'si' => 'ඉඩම් සංවර්ධන සහ ව්‍යාප්ති සේවා අංශය',
        'ta' => 'நிலம் அபிவிருத்தி மற்றும் விரிவாக்கல் சேவைகள் பிரிவு',
    ],
    'Societies & Marketing' => [
        'si' => 'සමිති සහ අලෙවි අංශය',
        'ta' => 'சங்கங்கள் மற்றும் சந்தைப்படுத்தல் பிரிவு',
    ],
    'Extension & Training' => [
        'si' => 'ව්‍යාප්ති සහ පුහුණු අංශය',
        'ta' => 'விரிவாக்கல் மற்றும் பயிற்சிப் பிரிவு',
    ],
    'Administration' => [
        'si' => 'පරිපාලන අංශය',
        'ta' => 'நிர்வாகப் பிரிவு',
    ],
    'Finance' => [
        'si' => 'මූල්‍ය අංශය',
        'ta' => 'நிதிப் பிரிவு',
    ],
    'Internal Audit' => [
        'si' => 'අභ්‍යන්තර විගණන අංශය',
        'ta' => 'உள்ளக கணக்காய்வுப் பிரிவு',
    ],
    'Development' => [
        'si' => 'සංවර්ධන අංශය',
        'ta' => 'அபிவிருத்திப் பிரிவு',
    ],
    'Planning & Monitoring' => [
        'si' => 'සැලසුම් සහ අධීක්ෂණ අංශය',
        'ta' => 'திட்டமிடல் மற்றும் கண்காணிப்புப் பிரிவு',
    ],
    'ICT' => [
        'si' => 'තොරතුරු හා සන්නිවේදන තාක්ෂණ අංශය',
        'ta' => 'தகவல் மற்றும் தொடர்பாடல் தொழினுட்பப் பிரிவு',
    ],
    'Regional Administration' => [
        'si' => 'ප්‍රාදේශීය පරිපාලනය',
        'ta' => 'பிராந்திய நிர்வாகம்',
    ],
    'Board & Chairman’s Office' => [
        'si' => 'මණ්ඩලය සහ සභාපති කාර්යාලය',
        'ta' => 'சபையும் தலைவர் அலுவலகமும்',
    ],
    'Director General’s Office' => [
        'si' => 'අධ්‍යක්ෂ ජනරාල් කාර්යාලය',
        'ta' => 'பணிப்பாளர் நாயகம் அலுவலகம்',
    ],
    'Your Tea Inspector, or the Regional Manager of your district office.' => [
        'si' => 'ඔබගේ තේ පරීක්ෂක, නැතහොත් ඔබගේ දිස්ත්‍රික් කාර්යාලයේ ප්‍රාදේශීය කළමනාකරු.',
        'ta' => 'உங்கள் தேயிலை ஆய்வாளர், அல்லது உங்கள் மாவட்ட அலுவலகத்தின் பிராந்திய முகாமையாளர்.',
    ],
    'Your Tea Inspector.' => [
        'si' => 'ඔබගේ තේ පරීක්ෂක.',
        'ta' => 'உங்கள் தேயிலை ஆய்வாளர்.',
    ],
    'Your Regional Office.' => [
        'si' => 'ඔබගේ ප්‍රාදේශීය කාර්යාලය.',
        'ta' => 'உங்கள் பிராந்திய அலுவலகம்.',
    ],
    'The Regional Manager of your district office.' => [
        'si' => 'ඔබගේ දිස්ත්‍රික් කාර්යාලයේ ප්‍රාදේශීය කළමනාකරු.',
        'ta' => 'உங்கள் மாவட்ட அலுவலகத்தின் பிராந்திய முகாமையாளர்.',
    ],
    'Hantana National Training Centre.' => [
        'si' => 'හන්තාන ජාතික පුහුණු මධ්‍යස්ථානය.',
        'ta' => 'ஹந்தான தேசிய பயிற்சி நிலையம்.',
    ],
    'Administration Division, Head Office.' => [
        'si' => 'පරිපාලන අංශය, ප්‍රධාන කාර්යාලය.',
        'ta' => 'நிர்வாகப் பிரிவு, தலைமை அலுவலகம்.',
    ],
    'Information Officer, Head Office.' => [
        'si' => 'තොරතුරු නිලධාරී, ප්‍රධාන කාර්යාලය.',
        'ta' => 'தகவல் அலுவலர், தலைமை அலுவலகம்.',
    ],
    'No application fee.' => [
        'si' => 'අයදුම් ගාස්තුවක් නොමැත.',
        'ta' => 'விண்ணப்பக் கட்டணம் இல்லை.',
    ],
    'No fee.' => [
        'si' => 'ගාස්තුවක් නොමැත.',
        'ta' => 'கட்டணம் இல்லை.',
    ],
    'Free of charge.' => [
        'si' => 'නොමිලේ.',
        'ta' => 'இலவசம்.',
    ],
    'As stated in the current guidelines.' => [
        'si' => 'වත්මන් මාර්ගෝපදේශවල දක්වා ඇති පරිදි.',
        'ta' => 'தற்போதைய வழிகாட்டல்களில் குறிப்பிடப்பட்டவாறு.',
    ],
    'Varies by programme.' => [
        'si' => 'වැඩසටහන අනුව වෙනස් වේ.',
        'ta' => 'நிகழ்ச்சிக்கேற்ப மாறுபடும்.',
    ],
    'By programme.' => [
        'si' => 'වැඩසටහන අනුව.',
        'ta' => 'நிகழ்ச்சிக்கேற்ப.',
    ],

    // ── Offices ─────────────────────────────────────────────────────────────
    'Head Office — Battaramulla' => [
        'si' => 'ප්‍රධාන කාර්යාලය — බත්තරමුල්ල',
        'ta' => 'தலைமை அலுவலகம் — பத்தரமுல்ல',
    ],
    'Regional Office — Galle' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — ගාල්ල',
        'ta' => 'பிராந்திய அலுவலகம் — காலி',
    ],
    'Regional Office — Matara' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — මාතර',
        'ta' => 'பிராந்திய அலுவலகம் — மாத்தறை',
    ],
    'Regional Office — Ratnapura' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — රත්නපුර',
        'ta' => 'பிராந்திய அலுவலகம் — இரத்தினபுரி',
    ],
    'Regional Office — Kegalle' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — කෑගල්ල',
        'ta' => 'பிராந்திய அலுவலகம் — கேகாலை',
    ],
    'Regional Office — Kalutara' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — කළුතර',
        'ta' => 'பிராந்திய அலுவலகம் — களுத்துறை',
    ],
    'Regional Office — Kandy' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — මහනුවර',
        'ta' => 'பிராந்திய அலுவலகம் — கண்டி',
    ],
    'Regional Office — Nuwara Eliya' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — නුවරඑළිය',
        'ta' => 'பிராந்திய அலுவலகம் — நுவரெலியா',
    ],
    'Regional Office — Bandarawela (Uva)' => [
        'si' => 'ප්‍රාදේශීය කාර්යාලය — බණ්ඩාරවෙල (ඌව)',
        'ta' => 'பிராந்திய அலுவலகம் — பண்டாரவளை (ஊவா)',
    ],
    'Hantana National Training Centre' => [
        'si' => 'හන්තාන ජාතික පුහුණු මධ්‍යස්ථානය',
        'ta' => 'ஹந்தான தேசிய பயிற்சி நிலையம்',
    ],
    'Hantana National Training Centre, Kandy' => [
        'si' => 'හන්තාන ජාතික පුහුණු මධ්‍යස්ථානය, මහනුවර',
        'ta' => 'ஹந்தான தேசிய பயிற்சி நிலையம், கண்டி',
    ],
    'No. 70, Parliament Road, Pelawatte, Battaramulla' => [
        'si' => 'අංක 70, පාර්ලිමේන්තු පාර, පැලවත්ත, බත්තරමුල්ල',
        'ta' => 'இல. 70, பாராளுமன்ற வீதி, பெலவத்த, பத்தரமுல்ல',
    ],

    // ── Designations ────────────────────────────────────────────────────────
    'Chairman' => ['si' => 'සභාපති', 'ta' => 'தலைவர்'],
    'Director General' => ['si' => 'අධ්‍යක්ෂ ජනරාල්', 'ta' => 'பணிப்பாளர் நாயகம்'],
    'Deputy Director General (Development)' => [
        'si' => 'නියෝජ්‍ය අධ්‍යක්ෂ ජනරාල් (සංවර්ධන)',
        'ta' => 'பிரதிப் பணிப்பாளர் நாயகம் (அபிவிருத்தி)',
    ],
    'Deputy Director General (Administration)' => [
        'si' => 'නියෝජ්‍ය අධ්‍යක්ෂ ජනරාල් (පරිපාලන)',
        'ta' => 'பிரதிப் பணிப்பாளர் நாயகம் (நிர்வாகம்)',
    ],
    'Director (Extension & Training)' => [
        'si' => 'අධ්‍යක්ෂ (ව්‍යාප්ති සහ පුහුණු)',
        'ta' => 'பணிப்பாளர் (விரிவாக்கல் மற்றும் பயிற்சி)',
    ],
    'Director (Societies & Marketing)' => [
        'si' => 'අධ්‍යක්ෂ (සමිති සහ අලෙවි)',
        'ta' => 'பணிப்பாளர் (சங்கங்கள் மற்றும் சந்தைப்படுத்தல்)',
    ],
    'Chief Accountant' => ['si' => 'ප්‍රධාන ගණකාධිකාරී', 'ta' => 'பிரதம கணக்காளர்'],
    'Chief Internal Auditor' => ['si' => 'ප්‍රධාන අභ්‍යන්තර විගණක', 'ta' => 'பிரதம உள்ளக கணக்காய்வாளர்'],
    'Regional Manager' => ['si' => 'ප්‍රාදේශීය කළමනාකරු', 'ta' => 'பிராந்திய முகாமையாளர்'],
    'Centre Manager' => ['si' => 'මධ්‍යස්ථාන කළමනාකරු', 'ta' => 'நிலைய முகாமையாளர்'],
    'Information & Communication Technology Officer' => [
        'si' => 'තොරතුරු හා සන්නිවේදන තාක්ෂණ නිලධාරී',
        'ta' => 'தகவல் மற்றும் தொடர்பாடல் தொழினுட்ப அலுவலர்',
    ],
    'Information Officer (Right to Information)' => [
        'si' => 'තොරතුරු නිලධාරී (තොරතුරු දැනගැනීමේ අයිතිය)',
        'ta' => 'தகவல் அலுவலர் (தகவல் அறியும் உரிமை)',
    ],
    'To be confirmed' => ['si' => 'තහවුරු කිරීමට ඇත', 'ta' => 'உறுதிப்படுத்தப்பட வேண்டியுள்ளது'],

    // ── Download categories ─────────────────────────────────────────────────
    'Tender documents' => ['si' => 'ටෙන්ඩර් ලේඛන', 'ta' => 'கேள்விப்பத்திர ஆவணங்கள்'],
    'Publications' => ['si' => 'ප්‍රකාශන', 'ta' => 'வெளியீடுகள்'],
    'Acts' => ['si' => 'පනත්', 'ta' => 'சட்டங்கள்'],
    'Regulations' => ['si' => 'රෙගුලාසි', 'ta' => 'ஒழுங்குவிதிகள்'],
    'Annual reports' => ['si' => 'වාර්ෂික වාර්තා', 'ta' => 'வருடாந்த அறிக்கைகள்'],
    'Application forms' => ['si' => 'අයදුම්පත්', 'ta' => 'விண்ணப்பப் படிவங்கள்'],
    'Standards and guides' => ['si' => 'ප්‍රමිති සහ මාර්ගෝපදේශ', 'ta' => 'தரநிலைகளும் வழிகாட்டிகளும்'],
    'Recruitment notices' => ['si' => 'බඳවා ගැනීමේ දැන්වීම්', 'ta' => 'ஆட்சேர்ப்பு அறிவித்தல்கள்'],

    // ── News categories ─────────────────────────────────────────────────────
    'Announcements & Notices' => ['si' => 'නිවේදන සහ දැනුම්දීම්', 'ta' => 'அறிவித்தல்களும் அறிவிப்புகளும்'],
    'Press Releases' => ['si' => 'මාධ්‍ය නිවේදන', 'ta' => 'ஊடக அறிக்கைகள்'],
    'Events' => ['si' => 'උත්සව', 'ta' => 'நிகழ்வுகள்'],
    'Tenders' => ['si' => 'ටෙන්ඩර්', 'ta' => 'கேள்விப்பத்திரங்கள்'],
    'Circulars' => ['si' => 'චක්‍රලේඛ', 'ta' => 'சுற்றுநிருபங்கள்'],
    'Programmes & Projects' => ['si' => 'වැඩසටහන් සහ ව්‍යාපෘති', 'ta' => 'நிகழ்ச்சிகளும் திட்டங்களும்'],

    // ── Statistics ──────────────────────────────────────────────────────────
    'Registered tea smallholdings by district' => [
        'si' => 'දිස්ත්‍රික්කය අනුව ලියාපදිංචි තේ කුඩා වතු',
        'ta' => 'மாவட்ட ரீதியாகப் பதிவு செய்யப்பட்ட தேயிலைச் சிறு தோட்டங்கள்',
    ],
    'Field extension structure' => [
        'si' => 'ක්ෂේත්‍ර ව්‍යාප්ති ව්‍යූහය',
        'ta' => 'கள விரிவாக்கல் அமைப்பு',
    ],
    'Replanting and new planting progress' => [
        'si' => 'නැවත වගා කිරීමේ සහ නව වගා කිරීමේ ප්‍රගතිය',
        'ta' => 'மீள்நடுகை மற்றும் புதிய நடுகை முன்னேற்றம்',
    ],
    'Tea Small Holdings Development Authority' => [
        'si' => 'තේ කුඩා වතු සංවර්ධන අධිකාරිය',
        'ta' => 'தேயிலை சிறு தோட்ட அபிவிருத்தி அதிகார சபை',
    ],
    'District' => ['si' => 'දිස්ත්‍රික්කය', 'ta' => 'மாவட்டம்'],
    'Registered holdings' => ['si' => 'ලියාපදිංචි වතු', 'ta' => 'பதிவு செய்யப்பட்ட தோட்டங்கள்'],
    'Level' => ['si' => 'මට්ටම', 'ta' => 'மட்டம்'],
    'Number' => ['si' => 'සංඛ්‍යාව', 'ta' => 'எண்ணிக்கை'],
    'Year' => ['si' => 'වර්ෂය', 'ta' => 'ஆண்டு'],
    'hectares' => ['si' => 'හෙක්ටයාර්', 'ta' => 'ஹெக்டேயார்'],
    'holdings' => ['si' => 'වතු', 'ta' => 'தோட்டங்கள்'],
    'units' => ['si' => 'ඒකක', 'ta' => 'அலகுகள்'],

    // ── Training programmes ─────────────────────────────────────────────────
    'Good agricultural practice for tea smallholdings' => [
        'si' => 'තේ කුඩා වතු සඳහා යහපත් කෘෂිකාර්මික භාවිතයන්',
        'ta' => 'தேயிலைச் சிறு தோட்டங்களுக்கான நல்ல விவசாய நடைமுறைகள்',
    ],
    'Tea nursery management and cultivar selection' => [
        'si' => 'තේ තවාන් කළමනාකරණය සහ ප්‍රභේද තෝරාගැනීම',
        'ta' => 'தேயிலை நாற்றுமேடை முகாமைத்துவமும் ரகத் தெரிவும்',
    ],
    'Society management for office bearers' => [
        'si' => 'නිලධාරීන් සඳහා සමිති කළමනාකරණය',
        'ta' => 'பதவியாளர்களுக்கான சங்க முகாமைத்துவம்',
    ],
    'Soil conservation on sloping tea land' => [
        'si' => 'බෑවුම් සහිත තේ ඉඩම්වල පාංශු සංරක්ෂණය',
        'ta' => 'சரிவான தேயிலை நிலங்களில் மண் பாதுகாப்பு',
    ],
    'Value addition and marketing for smallholder groups' => [
        'si' => 'කුඩා වතු කණ්ඩායම් සඳහා අගය එකතු කිරීම සහ අලෙවිය',
        'ta' => 'சிறு தோட்டக் குழுக்களுக்கான பெறுமதி சேர்ப்பும் சந்தைப்படுத்தலும்',
    ],
    'No course fee.' => ['si' => 'පාඨමාලා ගාස්තුවක් නොමැත.', 'ta' => 'பாட நெறிக் கட்டணம் இல்லை.'],
    'No course fee. Accommodation and meals provided.' => [
        'si' => 'පාඨමාලා ගාස්තුවක් නොමැත. නවාතැන් සහ ආහාර සපයනු ලැබේ.',
        'ta' => 'பாட நெறிக் கட்டணம் இல்லை. தங்குமிடமும் உணவும் வழங்கப்படும்.',
    ],
    'No course fee. Lunch provided.' => [
        'si' => 'පාඨමාලා ගාස්තුවක් නොමැත. දිවා ආහාරය සපයනු ලැබේ.',
        'ta' => 'பாட நெறிக் கட்டணம் இல்லை. மதிய உணவு வழங்கப்படும்.',
    ],
    'As published with the programme.' => [
        'si' => 'වැඩසටහන සමඟ ප්‍රකාශයට පත් කර ඇති පරිදි.',
        'ta' => 'நிகழ்ச்சியுடன் வெளியிடப்பட்டவாறு.',
    ],
    'Registered smallholders and society office bearers' => [
        'si' => 'ලියාපදිංචි කුඩා වතු හිමියන් සහ සමිති නිලධාරීන්',
        'ta' => 'பதிவு செய்யப்பட்ட சிறு தோட்ட உரிமையாளர்களும் சங்கப் பதவியாளர்களும்',
    ],
    'Nursery operators, prospective operators and field staff' => [
        'si' => 'තවාන් පවත්වාගෙන යන්නන්, අපේක්ෂකයින් සහ ක්ෂේත්‍ර කාර්ය මණ්ඩලය',
        'ta' => 'நாற்றுமேடை நடத்துநர்கள், எதிர்பார்க்கும் நடத்துநர்கள் மற்றும் கள ஊழியர்கள்',
    ],
    'Chairpersons, secretaries and treasurers of registered societies' => [
        'si' => 'ලියාපදිංචි සමිතිවල සභාපතිවරුන්, ලේකම්වරුන් සහ භාණ්ඩාගාරිකවරුන්',
        'ta' => 'பதிவு செய்யப்பட்ட சங்கங்களின் தலைவர்கள், செயலாளர்கள் மற்றும் பொருளாளர்கள்',
    ],
    'Smallholders replanting on sloping land, and field staff' => [
        'si' => 'බෑවුම් සහිත ඉඩම්වල නැවත වගා කරන කුඩා වතු හිමියන් සහ ක්ෂේත්‍ර කාර්ය මණ්ඩලය',
        'ta' => 'சரிவான நிலங்களில் மீள்நடுகை செய்யும் சிறு தோட்ட உரிமையாளர்களும் கள ஊழியர்களும்',
    ],
    'Society groups and Tea Shakthi enterprise participants' => [
        'si' => 'සමිති කණ්ඩායම් සහ තේ ශක්ති ව්‍යවසාය සහභාගිවන්නන්',
        'ta' => 'சங்கக் குழுக்களும் தேயிலை சக்தி தொழில்முயற்சிப் பங்கேற்பாளர்களும்',
    ],

    // ── Notices ─────────────────────────────────────────────────────────────
    'Fertilizer issue for the current season — district issue notices published' => [
        'si' => 'මෙම කන්නය සඳහා පොහොර නිකුත් කිරීම — දිස්ත්‍රික් නිකුත් කිරීමේ දැනුම්දීම් ප්‍රකාශයට පත් කර ඇත',
        'ta' => 'நடப்புப் பருவத்துக்கான உர விநியோகம் — மாவட்ட விநியோக அறிவிப்புகள் வெளியிடப்பட்டுள்ளன',
    ],
    'Replanting subsidy applications are open' => [
        'si' => 'නැවත වගා කිරීමේ සහනාධාර අයදුම්පත් භාරගනු ලැබේ',
        'ta' => 'மீள்நடுகை மானிய விண்ணப்பங்கள் ஏற்கப்படுகின்றன',
    ],
    'Hantana National Training Centre — new programme calendar released' => [
        'si' => 'හන්තාන ජාතික පුහුණු මධ්‍යස්ථානය — නව වැඩසටහන් දිනදර්ශනය නිකුත් කර ඇත',
        'ta' => 'ஹந்தான தேசிய பயிற்சி நிலையம் — புதிய நிகழ்ச்சி நாட்காட்டி வெளியிடப்பட்டுள்ளது',
    ],

    'Issue notices for each district are published in the Announcements section. Collect your allocation from the issuing point named on your district’s notice, within the window stated.' => [
        'si' => 'එක් එක් දිස්ත්‍රික්කය සඳහා නිකුත් කිරීමේ දැනුම්දීම් නිවේදන අංශයේ ප්‍රකාශයට පත් කර ඇත. ඔබගේ දිස්ත්‍රික්කයේ දැනුම්දීමේ නම් කර ඇති නිකුත් කිරීමේ ස්ථානයෙන්, දක්වා ඇති කාලය තුළ ඔබගේ ප්‍රතිපාදනය ලබාගන්න.',
        'ta' => 'ஒவ்வொரு மாவட்டத்துக்குமான விநியோக அறிவிப்புகள் அறிவித்தல்கள் பகுதியில் வெளியிடப்பட்டுள்ளன. உங்கள் மாவட்ட அறிவிப்பில் குறிப்பிடப்பட்டுள்ள விநியோக இடத்திலிருந்து, குறிப்பிட்ட காலத்திற்குள் உங்கள் ஒதுக்கீட்டைப் பெற்றுக் கொள்ளுங்கள்.',
    ],
    'Applications for the replanting subsidy are being accepted at every Regional Office. Speak to your Tea Inspector before uprooting any block.' => [
        'si' => 'නැවත වගා කිරීමේ සහනාධාරය සඳහා අයදුම්පත් සෑම ප්‍රාදේශීය කාර්යාලයකදීම භාරගනු ලැබේ. කිසිදු බ්ලොක් එකක් උදුරා දැමීමට පෙර ඔබගේ තේ පරීක්ෂක සමඟ කතා කරන්න.',
        'ta' => 'மீள்நடுகை மானியத்துக்கான விண்ணப்பங்கள் ஒவ்வொரு பிராந்திய அலுவலகத்திலும் ஏற்கப்படுகின்றன. எந்தவொரு தொகுதியையும் அகற்றுவதற்கு முன் உங்கள் தேயிலை ஆய்வாளருடன் கலந்துரையாடுங்கள்.',
    ],
    'The training calendar for the coming quarter is published, with residential and non-residential programmes for smallholders and society office bearers.' => [
        'si' => 'කුඩා වතු හිමියන් සහ සමිති නිලධාරීන් සඳහා නේවාසික සහ නේවාසික නොවන වැඩසටහන් සහිතව, ඉදිරි කාර්තුව සඳහා පුහුණු දිනදර්ශනය ප්‍රකාශයට පත් කර ඇත.',
        'ta' => 'சிறு தோட்ட உரிமையாளர்களுக்கும் சங்கப் பதவியாளர்களுக்குமான தங்கியிருந்து மற்றும் தங்காமல் நிகழ்ச்சிகளுடன், வரவிருக்கும் காலாண்டுக்கான பயிற்சி நாட்காட்டி வெளியிடப்பட்டுள்ளது.',
    ],

    // ── Related organisations ───────────────────────────────────────────────
    'Ministry of Plantation and Community Infrastructure' => [
        'si' => 'වැවිලි හා ප්‍රජා යටිතල පහසුකම් අමාත්‍යාංශය',
        'ta' => 'பெருந்தோட்ட மற்றும் சமூக உட்கட்டமைப்பு அமைச்சு',
    ],
    'Sri Lanka Tea Board' => [
        'si' => 'ශ්‍රී ලංකා තේ මණ්ඩලය',
        'ta' => 'இலங்கை தேயிலை சபை',
    ],
    'Tea Research Institute of Sri Lanka' => [
        'si' => 'ශ්‍රී ලංකා තේ පර්යේෂණ ආයතනය',
        'ta' => 'இலங்கை தேயிலை ஆராய்ச்சி நிறுவனம்',
    ],
    'Sri Lanka Tea Factory Owners’ Association' => [
        'si' => 'ශ්‍රී ලංකා තේ කර්මාන්තශාලා හිමියන්ගේ සංගමය',
        'ta' => 'இலங்கை தேயிலைத் தொழிற்சாலை உரிமையாளர் சங்கம்',
    ],
    'Sri Lanka Government Web Portal' => [
        'si' => 'ශ්‍රී ලංකා රජයේ වෙබ් ද්වාරය',
        'ta' => 'இலங்கை அரசாங்க இணையவாயில்',
    ],
    'Local Languages Website' => [
        'si' => 'දේශීය භාෂා වෙබ් අඩවිය',
        'ta' => 'உள்ளூர் மொழிகள் இணையத்தளம்',
    ],
    'Government Information Centre (1919)' => [
        'si' => 'රජයේ තොරතුරු මධ්‍යස්ථානය (1919)',
        'ta' => 'அரச தகவல் மையம் (1919)',
    ],
    'Information and Communication Technology Agency' => [
        'si' => 'තොරතුරු හා සන්නිවේදන තාක්ෂණ නියෝජිතායතනය',
        'ta' => 'தகவல் மற்றும் தொடர்பாடல் தொழினுட்ப முகவரகம்',
    ],

    // ── Page titles ─────────────────────────────────────────────────────────
    'About the Authority' => ['si' => 'අධිකාරිය ගැන', 'ta' => 'அதிகார சபை பற்றி'],
    'Vision, Mission and Objectives' => [
        'si' => 'දැක්ම, මෙහෙවර සහ අරමුණු',
        'ta' => 'நோக்கு, பணிக்கூற்று மற்றும் நோக்கங்கள்',
    ],
    'Strategic Plan, Policies and Action Plan' => [
        'si' => 'උපාය මාර්ගික සැලැස්ම, ප්‍රතිපත්ති සහ ක්‍රියාකාරී සැලැස්ම',
        'ta' => 'மூலோபாயத் திட்டம், கொள்கைகள் மற்றும் செயற்திட்டம்',
    ],
    'Divisions and Functional Areas' => [
        'si' => 'අංශ සහ ක්‍රියාකාරී ක්ෂේත්‍ර',
        'ta' => 'பிரிவுகளும் செயற்பாட்டுத் துறைகளும்',
    ],
    'Organisational Structure' => ['si' => 'සංවිධාන ව්‍යූහය', 'ta' => 'நிறுவன அமைப்பு'],
    'Land Development and Extension Services' => [
        'si' => 'ඉඩම් සංවර්ධනය සහ ව්‍යාප්ති සේවා',
        'ta' => 'நிலம் அபிவிருத்தி மற்றும் விரிவாக்கல் சேவைகள்',
    ],
    'Societies Management — Tea Shakthi and Cooperatives' => [
        'si' => 'සමිති කළමනාකරණය — තේ ශක්ති සහ සමූපකාර',
        'ta' => 'சங்க முகாமைத்துவம் — தேயிலை சக்தியும் கூட்டுறவுகளும்',
    ],
    'Privacy Notice' => ['si' => 'රහස්‍යතා දැනුම්දීම', 'ta' => 'தனியுரிமை அறிவிப்பு'],
    'Terms of Use' => ['si' => 'භාවිත නියම', 'ta' => 'பயன்பாட்டு விதிமுறைகள்'],
    'Accessibility Statement' => ['si' => 'ප්‍රවේශ්‍යතා ප්‍රකාශය', 'ta' => 'அணுகல்தன்மை அறிக்கை'],

    // ── Section headings on the content pages ───────────────────────────────
    'Overview' => ['si' => 'දළ විශ්ලේෂණය', 'ta' => 'கண்ணோட்டம்'],
    'History' => ['si' => 'ඉතිහාසය', 'ta' => 'வரலாறு'],
    'What the Authority does' => ['si' => 'අධිකාරිය කරන කාර්යය', 'ta' => 'அதிகார சபையின் பணி'],
    'Vision' => ['si' => 'දැක්ම', 'ta' => 'நோக்கு'],
    'Mission' => ['si' => 'මෙහෙවර', 'ta' => 'பணிக்கூற்று'],
    'Objectives' => ['si' => 'අරමුණු', 'ta' => 'நோக்கங்கள்'],
    'Strategic direction' => ['si' => 'උපාය මාර්ගික දිශානතිය', 'ta' => 'மூலோபாய திசை'],
    'Strategic priorities' => ['si' => 'උපාය මාර්ගික ප්‍රමුඛතා', 'ta' => 'மூலோபாய முன்னுரிமைகள்'],
    'Policy framework' => ['si' => 'ප්‍රතිපත්ති රාමුව', 'ta' => 'கொள்கைக் கட்டமைப்பு'],
    'Action plan' => ['si' => 'ක්‍රියාකාරී සැලැස්ම', 'ta' => 'செயற்திட்டம்'],
    'Head Office divisions' => ['si' => 'ප්‍රධාන කාර්යාලයේ අංශ', 'ta' => 'தலைமை அலுவலகப் பிரிவுகள்'],
    'The field structure' => ['si' => 'ක්ෂේත්‍ර ව්‍යූහය', 'ta' => 'கள அமைப்பு'],
    'How the Authority is organised' => ['si' => 'අධිකාරිය සංවිධානය වී ඇති ආකාරය', 'ta' => 'அதிகார சபை ஒழுங்கமைக்கப்பட்டுள்ள விதம்'],
    'Reporting structure' => ['si' => 'වාර්තාකරණ ව්‍යූහය', 'ta' => 'அறிக்கையிடல் அமைப்பு'],
    'Services in this area' => ['si' => 'මෙම ක්ෂේත්‍රයේ සේවාවන්', 'ta' => 'இந்தத் துறையின் சேவைகள்'],
    'Society directory' => ['si' => 'සමිති නාමාවලිය', 'ta' => 'சங்க விபரப்பட்டியல்'],
    'What this area covers' => ['si' => 'මෙම ක්ෂේත්‍රය ආවරණය කරන දෑ', 'ta' => 'இந்தத் துறை உள்ளடக்குபவை'],
    'Why societies exist' => ['si' => 'සමිති පවතින්නේ ඇයි', 'ta' => 'சங்கங்கள் ஏன் உள்ளன'],
];
