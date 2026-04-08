<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionsAndCitiesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRegions();
        $this->seedCities();
    }

    private function seedRegions(): void
    {
        $regions = [
            ['en' => 'Riyadh', 'ar' => 'الرياض'],
            ['en' => 'Makkah', 'ar' => 'مكة المكرمة'],
            ['en' => 'Madinah', 'ar' => 'المدينة المنورة'],
            ['en' => 'Eastern Province', 'ar' => 'المنطقة الشرقية'],
            ['en' => 'Asir', 'ar' => 'عسير'],
            ['en' => 'Tabuk', 'ar' => 'تبوك'],
            ['en' => 'Hail', 'ar' => 'حائل'],
            ['en' => 'Northern Borders', 'ar' => 'الحدود الشمالية'],
            ['en' => 'Jazan', 'ar' => 'جازان'],
            ['en' => 'Najran', 'ar' => 'نجران'],
            ['en' => 'Al-Baha', 'ar' => 'الباحة'],
            ['en' => 'Al-Jouf', 'ar' => 'الجوف'],
            ['en' => 'Qassim', 'ar' => 'القصيم'],
            // Additional regions for more data
            ['en' => 'Al Kharj', 'ar' => 'الخرج'],
            ['en' => 'Al Qunfudhah', 'ar' => 'القنفذة'],
            ['en' => 'Wadi Al Dawasir', 'ar' => 'وادي الدواسر'],
            ['en' => 'Al Aflaj', 'ar' => 'الأفلاج'],
            ['en' => 'Al Zulfi', 'ar' => 'الزلفي'],
            ['en' => 'Dawadmi', 'ar' => 'الدوادمي'],
            ['en' => 'Al Majmaah', 'ar' => 'المجمعة'],
            ['en' => 'Shaqra', 'ar' => 'شقراء'],
            ['en' => 'Al Ghat', 'ar' => 'الغاط'],
            ['en' => 'Hotat Bani Tamim', 'ar' => 'حوطة بني تميم'],
            ['en' => 'Al Hariq', 'ar' => 'الحريق'],
            ['en' => 'Afif', 'ar' => 'عفيف'],
            ['en' => 'Al Sulayyil', 'ar' => 'السليل'],
            ['en' => 'Layla', 'ar' => 'ليلى'],
            ['en' => 'Thadiq', 'ar' => 'ثادق'],
            ['en' => 'Huraymila', 'ar' => 'حريملاء'],
            ['en' => 'Rimah', 'ar' => 'رماح'],
        ];

        $maxId = DB::table('regions')->max('id') ?? 0;
        $now = now();
        $regionsData = [];
        $translationsData = [];

        foreach ($regions as $i => $region) {
            $regionId = $maxId + $i + 1;

            $regionsData[] = [
                'id' => $regionId,
                'created_at' => $now->copy()->subDays(rand(30, 365)),
                'updated_at' => $now,
            ];

            $translationsData[] = [
                'region_id' => $regionId,
                'locale' => 'en',
                'title' => $region['en'],
            ];

            $translationsData[] = [
                'region_id' => $regionId,
                'locale' => 'ar',
                'title' => $region['ar'],
            ];
        }

        DB::table('regions')->insert($regionsData);
        DB::table('region_translations')->insert($translationsData);

        echo 'Added '.count($regions)." regions with translations.\n";
    }

    private function seedCities(): void
    {
        // Get all region IDs
        $regionIds = DB::table('regions')->pluck('id')->toArray();

        if (empty($regionIds)) {
            echo "No regions found. Skipping cities.\n";

            return;
        }

        $cities = [
            // Major Saudi cities
            ['en' => 'Riyadh City', 'ar' => 'مدينة الرياض'],
            ['en' => 'Jeddah', 'ar' => 'جدة'],
            ['en' => 'Makkah City', 'ar' => 'مدينة مكة'],
            ['en' => 'Madinah City', 'ar' => 'مدينة المدينة'],
            ['en' => 'Dammam', 'ar' => 'الدمام'],
            ['en' => 'Khobar', 'ar' => 'الخبر'],
            ['en' => 'Dhahran', 'ar' => 'الظهران'],
            ['en' => 'Tabuk City', 'ar' => 'مدينة تبوك'],
            ['en' => 'Abha', 'ar' => 'أبها'],
            ['en' => 'Khamis Mushait', 'ar' => 'خميس مشيط'],
            ['en' => 'Hail City', 'ar' => 'مدينة حائل'],
            ['en' => 'Najran City', 'ar' => 'مدينة نجران'],
            ['en' => 'Jazan City', 'ar' => 'مدينة جازان'],
            ['en' => 'Yanbu', 'ar' => 'ينبع'],
            ['en' => 'Al Jubail', 'ar' => 'الجبيل'],
            ['en' => 'Taif', 'ar' => 'الطائف'],
            ['en' => 'Buraydah', 'ar' => 'بريدة'],
            ['en' => 'Qatif', 'ar' => 'القطيف'],
            ['en' => 'Al Hofuf', 'ar' => 'الهفوف'],
            ['en' => 'Al Mubarraz', 'ar' => 'المبرز'],
            // Additional cities
            ['en' => 'Al Ahsa', 'ar' => 'الأحساء'],
            ['en' => 'Hafar Al Batin', 'ar' => 'حفر الباطن'],
            ['en' => 'Sakaka', 'ar' => 'سكاكا'],
            ['en' => 'Arar', 'ar' => 'عرعر'],
            ['en' => 'Al Khafji', 'ar' => 'الخفجي'],
            ['en' => 'Ras Tanura', 'ar' => 'رأس تنورة'],
            ['en' => 'Rabigh', 'ar' => 'رابغ'],
            ['en' => 'Al Lith', 'ar' => 'الليث'],
            ['en' => 'Unaizah', 'ar' => 'عنيزة'],
            ['en' => 'Al Rass', 'ar' => 'الرس'],
            ['en' => 'Dawmat Al Jandal', 'ar' => 'دومة الجندل'],
            ['en' => 'Turaif', 'ar' => 'طريف'],
            ['en' => 'Rafha', 'ar' => 'رفحاء'],
            ['en' => 'Al Qurayyat', 'ar' => 'القريات'],
            ['en' => 'Wejh', 'ar' => 'الوجه'],
            ['en' => 'Duba', 'ar' => 'ضباء'],
            ['en' => 'Haql', 'ar' => 'حقل'],
            ['en' => 'Tayma', 'ar' => 'تيماء'],
            ['en' => 'Al Ula', 'ar' => 'العلا'],
            ['en' => 'Badr', 'ar' => 'بدر'],
            ['en' => 'Khaybar', 'ar' => 'خيبر'],
            ['en' => 'Al Hanakiyah', 'ar' => 'الحناكية'],
            ['en' => 'Mahd Al Thahab', 'ar' => 'مهد الذهب'],
            ['en' => 'Bisha', 'ar' => 'بيشة'],
            ['en' => 'Al Namas', 'ar' => 'النماص'],
            ['en' => 'Sarat Abidah', 'ar' => 'سراة عبيدة'],
            ['en' => 'Muhayil', 'ar' => 'محايل'],
            ['en' => 'Rijal Almaa', 'ar' => 'رجال ألمع'],
            ['en' => 'Ahad Rafidah', 'ar' => 'أحد رفيدة'],
            ['en' => 'Tanumah', 'ar' => 'تنومة'],
            // Gulf cities
            ['en' => 'Abu Dhabi', 'ar' => 'أبوظبي'],
            ['en' => 'Dubai', 'ar' => 'دبي'],
            ['en' => 'Sharjah', 'ar' => 'الشارقة'],
            ['en' => 'Kuwait City', 'ar' => 'مدينة الكويت'],
            ['en' => 'Manama', 'ar' => 'المنامة'],
            ['en' => 'Doha', 'ar' => 'الدوحة'],
            ['en' => 'Muscat', 'ar' => 'مسقط'],
            // Other Arab cities
            ['en' => 'Cairo', 'ar' => 'القاهرة'],
            ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
            ['en' => 'Amman', 'ar' => 'عمان'],
            ['en' => 'Beirut', 'ar' => 'بيروت'],
            ['en' => 'Damascus', 'ar' => 'دمشق'],
            ['en' => 'Baghdad', 'ar' => 'بغداد'],
            ['en' => 'Tunis', 'ar' => 'تونس'],
            ['en' => 'Casablanca', 'ar' => 'الدار البيضاء'],
            ['en' => 'Algiers', 'ar' => 'الجزائر'],
            ['en' => 'Tripoli', 'ar' => 'طرابلس'],
            ['en' => 'Khartoum', 'ar' => 'الخرطوم'],
            ['en' => 'Sana\'a', 'ar' => 'صنعاء'],
            // International cities
            ['en' => 'London', 'ar' => 'لندن'],
            ['en' => 'Paris', 'ar' => 'باريس'],
            ['en' => 'New York', 'ar' => 'نيويورك'],
            ['en' => 'Los Angeles', 'ar' => 'لوس أنجلوس'],
            ['en' => 'Tokyo', 'ar' => 'طوكيو'],
            ['en' => 'Beijing', 'ar' => 'بكين'],
            ['en' => 'Shanghai', 'ar' => 'شنغهاي'],
            ['en' => 'Mumbai', 'ar' => 'مومباي'],
            ['en' => 'Delhi', 'ar' => 'دلهي'],
            ['en' => 'Singapore', 'ar' => 'سنغافورة'],
            ['en' => 'Hong Kong', 'ar' => 'هونغ كونغ'],
            ['en' => 'Sydney', 'ar' => 'سيدني'],
            ['en' => 'Melbourne', 'ar' => 'ملبورن'],
            ['en' => 'Toronto', 'ar' => 'تورونتو'],
            ['en' => 'Berlin', 'ar' => 'برلين'],
            ['en' => 'Frankfurt', 'ar' => 'فرانكفورت'],
            ['en' => 'Munich', 'ar' => 'ميونخ'],
            ['en' => 'Rome', 'ar' => 'روما'],
            ['en' => 'Milan', 'ar' => 'ميلان'],
            ['en' => 'Madrid', 'ar' => 'مدريد'],
            ['en' => 'Barcelona', 'ar' => 'برشلونة'],
            ['en' => 'Amsterdam', 'ar' => 'أمستردام'],
            ['en' => 'Brussels', 'ar' => 'بروكسل'],
            ['en' => 'Vienna', 'ar' => 'فيينا'],
            ['en' => 'Zurich', 'ar' => 'زيورخ'],
            ['en' => 'Geneva', 'ar' => 'جنيف'],
            ['en' => 'Moscow', 'ar' => 'موسكو'],
            ['en' => 'Istanbul', 'ar' => 'إسطنبول'],
            ['en' => 'Ankara', 'ar' => 'أنقرة'],
        ];

        $maxId = DB::table('cities')->max('id') ?? 0;
        $now = now();
        $citiesData = [];
        $translationsData = [];

        foreach ($cities as $i => $city) {
            $cityId = $maxId + $i + 1;
            $regionId = $regionIds[array_rand($regionIds)];

            $citiesData[] = [
                'id' => $cityId,
                'region_id' => $regionId,
                'created_at' => $now->copy()->subDays(rand(1, 365)),
                'updated_at' => $now,
            ];

            $translationsData[] = [
                'city_id' => $cityId,
                'locale' => 'en',
                'title' => $city['en'],
            ];

            $translationsData[] = [
                'city_id' => $cityId,
                'locale' => 'ar',
                'title' => $city['ar'],
            ];
        }

        foreach (array_chunk($citiesData, 100) as $chunk) {
            DB::table('cities')->insert($chunk);
        }

        foreach (array_chunk($translationsData, 200) as $chunk) {
            DB::table('city_translations')->insert($chunk);
        }

        echo 'Added '.count($cities)." cities with translations.\n";
    }
}
