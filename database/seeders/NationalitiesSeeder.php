<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalitiesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'AF', 'icon' => '🇦🇫', 'en' => 'Afghan', 'ar' => 'أفغاني'],
            ['code' => 'AL', 'icon' => '🇦🇱', 'en' => 'Albanian', 'ar' => 'ألباني'],
            ['code' => 'DZ', 'icon' => '🇩🇿', 'en' => 'Algerian', 'ar' => 'جزائري'],
            ['code' => 'AD', 'icon' => '🇦🇩', 'en' => 'Andorran', 'ar' => 'أندوري'],
            ['code' => 'AO', 'icon' => '🇦🇴', 'en' => 'Angolan', 'ar' => 'أنغولي'],
            ['code' => 'AR', 'icon' => '🇦🇷', 'en' => 'Argentine', 'ar' => 'أرجنتيني'],
            ['code' => 'AM', 'icon' => '🇦🇲', 'en' => 'Armenian', 'ar' => 'أرميني'],
            ['code' => 'AU', 'icon' => '🇦🇺', 'en' => 'Australian', 'ar' => 'أسترالي'],
            ['code' => 'AT', 'icon' => '🇦🇹', 'en' => 'Austrian', 'ar' => 'نمساوي'],
            ['code' => 'AZ', 'icon' => '🇦🇿', 'en' => 'Azerbaijani', 'ar' => 'أذربيجاني'],
            ['code' => 'BH', 'icon' => '🇧🇭', 'en' => 'Bahraini', 'ar' => 'بحريني'],
            ['code' => 'BD', 'icon' => '🇧🇩', 'en' => 'Bangladeshi', 'ar' => 'بنغلاديشي'],
            ['code' => 'BY', 'icon' => '🇧🇾', 'en' => 'Belarusian', 'ar' => 'بيلاروسي'],
            ['code' => 'BE', 'icon' => '🇧🇪', 'en' => 'Belgian', 'ar' => 'بلجيكي'],
            ['code' => 'BZ', 'icon' => '🇧🇿', 'en' => 'Belizean', 'ar' => 'بليزي'],
            ['code' => 'BJ', 'icon' => '🇧🇯', 'en' => 'Beninese', 'ar' => 'بنيني'],
            ['code' => 'BT', 'icon' => '🇧🇹', 'en' => 'Bhutanese', 'ar' => 'بوتاني'],
            ['code' => 'BO', 'icon' => '🇧🇴', 'en' => 'Bolivian', 'ar' => 'بوليفي'],
            ['code' => 'BA', 'icon' => '🇧🇦', 'en' => 'Bosnian', 'ar' => 'بوسني'],
            ['code' => 'BW', 'icon' => '🇧🇼', 'en' => 'Botswanan', 'ar' => 'بوتسواني'],
            ['code' => 'BR', 'icon' => '🇧🇷', 'en' => 'Brazilian', 'ar' => 'برازيلي'],
            ['code' => 'BN', 'icon' => '🇧🇳', 'en' => 'Bruneian', 'ar' => 'بروناوي'],
            ['code' => 'BG', 'icon' => '🇧🇬', 'en' => 'Bulgarian', 'ar' => 'بلغاري'],
            ['code' => 'BF', 'icon' => '🇧🇫', 'en' => 'Burkinabe', 'ar' => 'بوركيني'],
            ['code' => 'BI', 'icon' => '🇧🇮', 'en' => 'Burundian', 'ar' => 'بوروندي'],
            ['code' => 'KH', 'icon' => '🇰🇭', 'en' => 'Cambodian', 'ar' => 'كمبودي'],
            ['code' => 'CM', 'icon' => '🇨🇲', 'en' => 'Cameroonian', 'ar' => 'كاميروني'],
            ['code' => 'CA', 'icon' => '🇨🇦', 'en' => 'Canadian', 'ar' => 'كندي'],
            ['code' => 'CV', 'icon' => '🇨🇻', 'en' => 'Cape Verdean', 'ar' => 'كاب فيردي'],
            ['code' => 'CF', 'icon' => '🇨🇫', 'en' => 'Central African', 'ar' => 'أفريقي وسطى'],
            ['code' => 'TD', 'icon' => '🇹🇩', 'en' => 'Chadian', 'ar' => 'تشادي'],
            ['code' => 'CL', 'icon' => '🇨🇱', 'en' => 'Chilean', 'ar' => 'تشيلي'],
            ['code' => 'CN', 'icon' => '🇨🇳', 'en' => 'Chinese', 'ar' => 'صيني'],
            ['code' => 'CO', 'icon' => '🇨🇴', 'en' => 'Colombian', 'ar' => 'كولومبي'],
            ['code' => 'KM', 'icon' => '🇰🇲', 'en' => 'Comoran', 'ar' => 'قمري'],
            ['code' => 'CG', 'icon' => '🇨🇬', 'en' => 'Congolese', 'ar' => 'كونغولي'],
            ['code' => 'CR', 'icon' => '🇨🇷', 'en' => 'Costa Rican', 'ar' => 'كوستاريكي'],
            ['code' => 'HR', 'icon' => '🇭🇷', 'en' => 'Croatian', 'ar' => 'كرواتي'],
            ['code' => 'CU', 'icon' => '🇨🇺', 'en' => 'Cuban', 'ar' => 'كوبي'],
            ['code' => 'CY', 'icon' => '🇨🇾', 'en' => 'Cypriot', 'ar' => 'قبرصي'],
            ['code' => 'CZ', 'icon' => '🇨🇿', 'en' => 'Czech', 'ar' => 'تشيكي'],
            ['code' => 'DK', 'icon' => '🇩🇰', 'en' => 'Danish', 'ar' => 'دنماركي'],
            ['code' => 'DJ', 'icon' => '🇩🇯', 'en' => 'Djiboutian', 'ar' => 'جيبوتي'],
            ['code' => 'DO', 'icon' => '🇩🇴', 'en' => 'Dominican', 'ar' => 'دومينيكي'],
            ['code' => 'EC', 'icon' => '🇪🇨', 'en' => 'Ecuadorian', 'ar' => 'إكوادوري'],
            ['code' => 'EG', 'icon' => '🇪🇬', 'en' => 'Egyptian', 'ar' => 'مصري'],
            ['code' => 'SV', 'icon' => '🇸🇻', 'en' => 'Salvadoran', 'ar' => 'سلفادوري'],
            ['code' => 'GQ', 'icon' => '🇬🇶', 'en' => 'Equatorial Guinean', 'ar' => 'غيني استوائي'],
            ['code' => 'ER', 'icon' => '🇪🇷', 'en' => 'Eritrean', 'ar' => 'إريتري'],
            ['code' => 'EE', 'icon' => '🇪🇪', 'en' => 'Estonian', 'ar' => 'إستوني'],
            ['code' => 'ET', 'icon' => '🇪🇹', 'en' => 'Ethiopian', 'ar' => 'إثيوبي'],
            ['code' => 'FJ', 'icon' => '🇫🇯', 'en' => 'Fijian', 'ar' => 'فيجي'],
            ['code' => 'FI', 'icon' => '🇫🇮', 'en' => 'Finnish', 'ar' => 'فنلندي'],
            ['code' => 'FR', 'icon' => '🇫🇷', 'en' => 'French', 'ar' => 'فرنسي'],
            ['code' => 'GA', 'icon' => '🇬🇦', 'en' => 'Gabonese', 'ar' => 'غابوني'],
            ['code' => 'GM', 'icon' => '🇬🇲', 'en' => 'Gambian', 'ar' => 'غامبي'],
            ['code' => 'GE', 'icon' => '🇬🇪', 'en' => 'Georgian', 'ar' => 'جورجي'],
            ['code' => 'DE', 'icon' => '🇩🇪', 'en' => 'German', 'ar' => 'ألماني'],
            ['code' => 'GH', 'icon' => '🇬🇭', 'en' => 'Ghanaian', 'ar' => 'غاني'],
            ['code' => 'GR', 'icon' => '🇬🇷', 'en' => 'Greek', 'ar' => 'يوناني'],
            ['code' => 'GT', 'icon' => '🇬🇹', 'en' => 'Guatemalan', 'ar' => 'غواتيمالي'],
            ['code' => 'GN', 'icon' => '🇬🇳', 'en' => 'Guinean', 'ar' => 'غيني'],
            ['code' => 'GY', 'icon' => '🇬🇾', 'en' => 'Guyanese', 'ar' => 'غياني'],
            ['code' => 'HT', 'icon' => '🇭🇹', 'en' => 'Haitian', 'ar' => 'هايتي'],
            ['code' => 'HN', 'icon' => '🇭🇳', 'en' => 'Honduran', 'ar' => 'هندوراسي'],
            ['code' => 'HU', 'icon' => '🇭🇺', 'en' => 'Hungarian', 'ar' => 'مجري'],
            ['code' => 'IS', 'icon' => '🇮🇸', 'en' => 'Icelandic', 'ar' => 'آيسلندي'],
            ['code' => 'IN', 'icon' => '🇮🇳', 'en' => 'Indian', 'ar' => 'هندي'],
            ['code' => 'ID', 'icon' => '🇮🇩', 'en' => 'Indonesian', 'ar' => 'إندونيسي'],
            ['code' => 'IR', 'icon' => '🇮🇷', 'en' => 'Iranian', 'ar' => 'إيراني'],
            ['code' => 'IQ', 'icon' => '🇮🇶', 'en' => 'Iraqi', 'ar' => 'عراقي'],
            ['code' => 'IE', 'icon' => '🇮🇪', 'en' => 'Irish', 'ar' => 'أيرلندي'],
            ['code' => 'IL', 'icon' => '🇮🇱', 'en' => 'Israeli', 'ar' => 'إسرائيلي'],
            ['code' => 'IT', 'icon' => '🇮🇹', 'en' => 'Italian', 'ar' => 'إيطالي'],
            ['code' => 'JM', 'icon' => '🇯🇲', 'en' => 'Jamaican', 'ar' => 'جامايكي'],
            ['code' => 'JP', 'icon' => '🇯🇵', 'en' => 'Japanese', 'ar' => 'ياباني'],
            ['code' => 'JO', 'icon' => '🇯🇴', 'en' => 'Jordanian', 'ar' => 'أردني'],
            ['code' => 'KZ', 'icon' => '🇰🇿', 'en' => 'Kazakhstani', 'ar' => 'كازاخستاني'],
            ['code' => 'KE', 'icon' => '🇰🇪', 'en' => 'Kenyan', 'ar' => 'كيني'],
            ['code' => 'KR', 'icon' => '🇰🇷', 'en' => 'South Korean', 'ar' => 'كوري جنوبي'],
            ['code' => 'KW', 'icon' => '🇰🇼', 'en' => 'Kuwaiti', 'ar' => 'كويتي'],
            ['code' => 'KG', 'icon' => '🇰🇬', 'en' => 'Kyrgyz', 'ar' => 'قيرغيزي'],
            ['code' => 'LA', 'icon' => '🇱🇦', 'en' => 'Laotian', 'ar' => 'لاوسي'],
            ['code' => 'LV', 'icon' => '🇱🇻', 'en' => 'Latvian', 'ar' => 'لاتفي'],
            ['code' => 'LB', 'icon' => '🇱🇧', 'en' => 'Lebanese', 'ar' => 'لبناني'],
            ['code' => 'LY', 'icon' => '🇱🇾', 'en' => 'Libyan', 'ar' => 'ليبي'],
            ['code' => 'LT', 'icon' => '🇱🇹', 'en' => 'Lithuanian', 'ar' => 'ليتواني'],
            ['code' => 'LU', 'icon' => '🇱🇺', 'en' => 'Luxembourgish', 'ar' => 'لوكسمبورغي'],
            ['code' => 'MK', 'icon' => '🇲🇰', 'en' => 'Macedonian', 'ar' => 'مقدوني'],
            ['code' => 'MG', 'icon' => '🇲🇬', 'en' => 'Malagasy', 'ar' => 'مدغشقري'],
            ['code' => 'MW', 'icon' => '🇲🇼', 'en' => 'Malawian', 'ar' => 'مالاوي'],
            ['code' => 'MY', 'icon' => '🇲🇾', 'en' => 'Malaysian', 'ar' => 'ماليزي'],
            ['code' => 'MV', 'icon' => '🇲🇻', 'en' => 'Maldivian', 'ar' => 'مالديفي'],
            ['code' => 'ML', 'icon' => '🇲🇱', 'en' => 'Malian', 'ar' => 'مالي'],
            ['code' => 'MT', 'icon' => '🇲🇹', 'en' => 'Maltese', 'ar' => 'مالطي'],
            ['code' => 'MR', 'icon' => '🇲🇷', 'en' => 'Mauritanian', 'ar' => 'موريتاني'],
            ['code' => 'MU', 'icon' => '🇲🇺', 'en' => 'Mauritian', 'ar' => 'موريشي'],
            ['code' => 'MX', 'icon' => '🇲🇽', 'en' => 'Mexican', 'ar' => 'مكسيكي'],
            ['code' => 'MA', 'icon' => '🇲🇦', 'en' => 'Moroccan', 'ar' => 'مغربي'],
            ['code' => 'NL', 'icon' => '🇳🇱', 'en' => 'Dutch', 'ar' => 'هولندي'],
            ['code' => 'NZ', 'icon' => '🇳🇿', 'en' => 'New Zealander', 'ar' => 'نيوزيلندي'],
            ['code' => 'NG', 'icon' => '🇳🇬', 'en' => 'Nigerian', 'ar' => 'نيجيري'],
            ['code' => 'NO', 'icon' => '🇳🇴', 'en' => 'Norwegian', 'ar' => 'نرويجي'],
            ['code' => 'OM', 'icon' => '🇴🇲', 'en' => 'Omani', 'ar' => 'عماني'],
            ['code' => 'PK', 'icon' => '🇵🇰', 'en' => 'Pakistani', 'ar' => 'باكستاني'],
            ['code' => 'PS', 'icon' => '🇵🇸', 'en' => 'Palestinian', 'ar' => 'فلسطيني'],
            ['code' => 'PA', 'icon' => '🇵🇦', 'en' => 'Panamanian', 'ar' => 'بنمي'],
            ['code' => 'PY', 'icon' => '🇵🇾', 'en' => 'Paraguayan', 'ar' => 'باراغواي'],
            ['code' => 'PE', 'icon' => '🇵🇪', 'en' => 'Peruvian', 'ar' => 'بيروفي'],
            ['code' => 'PH', 'icon' => '🇵🇭', 'en' => 'Filipino', 'ar' => 'فلبيني'],
            ['code' => 'PL', 'icon' => '🇵🇱', 'en' => 'Polish', 'ar' => 'بولندي'],
            ['code' => 'PT', 'icon' => '🇵🇹', 'en' => 'Portuguese', 'ar' => 'برتغالي'],
            ['code' => 'QA', 'icon' => '🇶🇦', 'en' => 'Qatari', 'ar' => 'قطري'],
            ['code' => 'RO', 'icon' => '🇷🇴', 'en' => 'Romanian', 'ar' => 'روماني'],
            ['code' => 'RU', 'icon' => '🇷🇺', 'en' => 'Russian', 'ar' => 'روسي'],
            ['code' => 'RW', 'icon' => '🇷🇼', 'en' => 'Rwandan', 'ar' => 'رواندي'],
            ['code' => 'SA', 'icon' => '🇸🇦', 'en' => 'Saudi', 'ar' => 'سعودي'],
            ['code' => 'SN', 'icon' => '🇸🇳', 'en' => 'Senegalese', 'ar' => 'سنغالي'],
            ['code' => 'RS', 'icon' => '🇷🇸', 'en' => 'Serbian', 'ar' => 'صربي'],
            ['code' => 'SG', 'icon' => '🇸🇬', 'en' => 'Singaporean', 'ar' => 'سنغافوري'],
            ['code' => 'SK', 'icon' => '🇸🇰', 'en' => 'Slovak', 'ar' => 'سلوفاكي'],
            ['code' => 'SI', 'icon' => '🇸🇮', 'en' => 'Slovenian', 'ar' => 'سلوفيني'],
            ['code' => 'SO', 'icon' => '🇸🇴', 'en' => 'Somali', 'ar' => 'صومالي'],
            ['code' => 'ZA', 'icon' => '🇿🇦', 'en' => 'South African', 'ar' => 'جنوب أفريقي'],
            ['code' => 'ES', 'icon' => '🇪🇸', 'en' => 'Spanish', 'ar' => 'إسباني'],
            ['code' => 'LK', 'icon' => '🇱🇰', 'en' => 'Sri Lankan', 'ar' => 'سريلانكي'],
            ['code' => 'SD', 'icon' => '🇸🇩', 'en' => 'Sudanese', 'ar' => 'سوداني'],
            ['code' => 'SE', 'icon' => '🇸🇪', 'en' => 'Swedish', 'ar' => 'سويدي'],
            ['code' => 'CH', 'icon' => '🇨🇭', 'en' => 'Swiss', 'ar' => 'سويسري'],
            ['code' => 'SY', 'icon' => '🇸🇾', 'en' => 'Syrian', 'ar' => 'سوري'],
            ['code' => 'TW', 'icon' => '🇹🇼', 'en' => 'Taiwanese', 'ar' => 'تايواني'],
            ['code' => 'TZ', 'icon' => '🇹🇿', 'en' => 'Tanzanian', 'ar' => 'تنزاني'],
            ['code' => 'TH', 'icon' => '🇹🇭', 'en' => 'Thai', 'ar' => 'تايلاندي'],
            ['code' => 'TN', 'icon' => '🇹🇳', 'en' => 'Tunisian', 'ar' => 'تونسي'],
            ['code' => 'TR', 'icon' => '🇹🇷', 'en' => 'Turkish', 'ar' => 'تركي'],
            ['code' => 'UG', 'icon' => '🇺🇬', 'en' => 'Ugandan', 'ar' => 'أوغندي'],
            ['code' => 'UA', 'icon' => '🇺🇦', 'en' => 'Ukrainian', 'ar' => 'أوكراني'],
            ['code' => 'AE', 'icon' => '🇦🇪', 'en' => 'Emirati', 'ar' => 'إماراتي'],
            ['code' => 'GB', 'icon' => '🇬🇧', 'en' => 'British', 'ar' => 'بريطاني'],
            ['code' => 'US', 'icon' => '🇺🇸', 'en' => 'American', 'ar' => 'أمريكي'],
            ['code' => 'UY', 'icon' => '🇺🇾', 'en' => 'Uruguayan', 'ar' => 'أوروغواي'],
            ['code' => 'UZ', 'icon' => '🇺🇿', 'en' => 'Uzbek', 'ar' => 'أوزبكي'],
            ['code' => 'VE', 'icon' => '🇻🇪', 'en' => 'Venezuelan', 'ar' => 'فنزويلي'],
            ['code' => 'VN', 'icon' => '🇻🇳', 'en' => 'Vietnamese', 'ar' => 'فيتنامي'],
            ['code' => 'YE', 'icon' => '🇾🇪', 'en' => 'Yemeni', 'ar' => 'يمني'],
            ['code' => 'ZM', 'icon' => '🇿🇲', 'en' => 'Zambian', 'ar' => 'زامبي'],
            ['code' => 'ZW', 'icon' => '🇿🇼', 'en' => 'Zimbabwean', 'ar' => 'زيمبابوي'],
        ];

        $maxId = DB::table('nationalities')->max('id') ?? 0;
        $now = now();

        $nationalities = [];
        $translations = [];

        for ($i = 0; $i < 500; $i++) {
            $baseCountry = $countries[$i % count($countries)];
            $variation = (int) floor($i / count($countries));
            $nationalityId = $maxId + $i + 1;
            $code = $baseCountry['code'].($variation > 0 ? $variation : '');

            $nationalities[] = [
                'id' => $nationalityId,
                'icon' => $baseCountry['icon'],
                'code' => $code,
                'is_active' => 1,
                'created_at' => $now->copy()->subDays(rand(1, 365)),
                'updated_at' => $now,
            ];

            $translations[] = [
                'nationality_id' => $nationalityId,
                'locale' => 'en',
                'name' => $baseCountry['en'].($variation > 0 ? ' V'.$variation : ''),
            ];

            $translations[] = [
                'nationality_id' => $nationalityId,
                'locale' => 'ar',
                'name' => $baseCountry['ar'].($variation > 0 ? ' '.$variation : ''),
            ];
        }

        foreach (array_chunk($nationalities, 100) as $chunk) {
            DB::table('nationalities')->insert($chunk);
        }

        foreach (array_chunk($translations, 200) as $chunk) {
            DB::table('nationality_translations')->insert($chunk);
        }

        echo "Added 500 nationalities with 1000 translations.\n";
    }
}
