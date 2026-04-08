<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesAndSkillsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
    }

    private function seedCategories(): void
    {
        // Categories with their skills
        $categoriesWithSkills = [
            [
                'icon' => '🏠',
                'en' => 'Home Cleaning',
                'ar' => 'تنظيف المنازل',
                'desc_en' => 'Professional home cleaning services',
                'desc_ar' => 'خدمات تنظيف المنازل الاحترافية',
                'fees' => 5.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Deep Cleaning', 'ar' => 'تنظيف عميق'],
                    ['en' => 'Regular Cleaning', 'ar' => 'تنظيف منتظم'],
                    ['en' => 'Window Cleaning', 'ar' => 'تنظيف النوافذ'],
                    ['en' => 'Carpet Cleaning', 'ar' => 'تنظيف السجاد'],
                    ['en' => 'Kitchen Cleaning', 'ar' => 'تنظيف المطبخ'],
                    ['en' => 'Bathroom Cleaning', 'ar' => 'تنظيف الحمامات'],
                    ['en' => 'Post-Construction Cleaning', 'ar' => 'تنظيف ما بعد البناء'],
                    ['en' => 'Move-in/Move-out Cleaning', 'ar' => 'تنظيف عند الانتقال'],
                ],
            ],
            [
                'icon' => '🔧',
                'en' => 'Plumbing',
                'ar' => 'السباكة',
                'desc_en' => 'Professional plumbing services',
                'desc_ar' => 'خدمات السباكة الاحترافية',
                'fees' => 7.50,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Pipe Repair', 'ar' => 'إصلاح الأنابيب'],
                    ['en' => 'Drain Cleaning', 'ar' => 'تنظيف المجاري'],
                    ['en' => 'Faucet Installation', 'ar' => 'تركيب الصنابير'],
                    ['en' => 'Water Heater Repair', 'ar' => 'إصلاح سخانات المياه'],
                    ['en' => 'Toilet Repair', 'ar' => 'إصلاح المراحيض'],
                    ['en' => 'Leak Detection', 'ar' => 'كشف التسربات'],
                    ['en' => 'Sewer Line Services', 'ar' => 'خدمات خطوط الصرف'],
                    ['en' => 'Water Tank Cleaning', 'ar' => 'تنظيف خزانات المياه'],
                ],
            ],
            [
                'icon' => '⚡',
                'en' => 'Electrical',
                'ar' => 'الكهرباء',
                'desc_en' => 'Professional electrical services',
                'desc_ar' => 'خدمات الكهرباء الاحترافية',
                'fees' => 8.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Wiring Installation', 'ar' => 'تركيب الأسلاك'],
                    ['en' => 'Circuit Breaker Repair', 'ar' => 'إصلاح القواطع'],
                    ['en' => 'Lighting Installation', 'ar' => 'تركيب الإضاءة'],
                    ['en' => 'Outlet Installation', 'ar' => 'تركيب المقابس'],
                    ['en' => 'Electrical Panel Upgrade', 'ar' => 'ترقية اللوحة الكهربائية'],
                    ['en' => 'Generator Installation', 'ar' => 'تركيب المولدات'],
                    ['en' => 'Smart Home Wiring', 'ar' => 'أسلاك المنزل الذكي'],
                    ['en' => 'Electrical Safety Inspection', 'ar' => 'فحص السلامة الكهربائية'],
                ],
            ],
            [
                'icon' => '❄️',
                'en' => 'AC & Cooling',
                'ar' => 'التكييف والتبريد',
                'desc_en' => 'Air conditioning services',
                'desc_ar' => 'خدمات التكييف والتبريد',
                'fees' => 6.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'AC Installation', 'ar' => 'تركيب المكيفات'],
                    ['en' => 'AC Repair', 'ar' => 'إصلاح المكيفات'],
                    ['en' => 'AC Maintenance', 'ar' => 'صيانة المكيفات'],
                    ['en' => 'Duct Cleaning', 'ar' => 'تنظيف المجاري الهوائية'],
                    ['en' => 'Refrigerant Recharge', 'ar' => 'إعادة شحن الفريون'],
                    ['en' => 'Thermostat Installation', 'ar' => 'تركيب منظم الحرارة'],
                    ['en' => 'Central AC Services', 'ar' => 'خدمات التكييف المركزي'],
                    ['en' => 'Cooling System Design', 'ar' => 'تصميم أنظمة التبريد'],
                ],
            ],
            [
                'icon' => '🎨',
                'en' => 'Painting',
                'ar' => 'الدهان',
                'desc_en' => 'Professional painting services',
                'desc_ar' => 'خدمات الدهان الاحترافية',
                'fees' => 5.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Interior Painting', 'ar' => 'دهان داخلي'],
                    ['en' => 'Exterior Painting', 'ar' => 'دهان خارجي'],
                    ['en' => 'Wall Texturing', 'ar' => 'تنسيق الجدران'],
                    ['en' => 'Wallpaper Installation', 'ar' => 'تركيب ورق الجدران'],
                    ['en' => 'Cabinet Painting', 'ar' => 'دهان الخزائن'],
                    ['en' => 'Deck Staining', 'ar' => 'صبغ الأسطح الخشبية'],
                    ['en' => 'Epoxy Flooring', 'ar' => 'أرضيات الإيبوكسي'],
                    ['en' => 'Decorative Finishes', 'ar' => 'التشطيبات الديكورية'],
                ],
            ],
            [
                'icon' => '🪚',
                'en' => 'Carpentry',
                'ar' => 'النجارة',
                'desc_en' => 'Professional carpentry services',
                'desc_ar' => 'خدمات النجارة الاحترافية',
                'fees' => 7.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Furniture Assembly', 'ar' => 'تجميع الأثاث'],
                    ['en' => 'Custom Cabinets', 'ar' => 'خزائن مخصصة'],
                    ['en' => 'Door Installation', 'ar' => 'تركيب الأبواب'],
                    ['en' => 'Window Frames', 'ar' => 'إطارات النوافذ'],
                    ['en' => 'Shelving Installation', 'ar' => 'تركيب الأرفف'],
                    ['en' => 'Wood Flooring', 'ar' => 'الأرضيات الخشبية'],
                    ['en' => 'Deck Building', 'ar' => 'بناء الأسطح الخشبية'],
                    ['en' => 'Furniture Repair', 'ar' => 'إصلاح الأثاث'],
                ],
            ],
            [
                'icon' => '🌿',
                'en' => 'Gardening & Landscaping',
                'ar' => 'الحدائق والتنسيق',
                'desc_en' => 'Garden and landscape services',
                'desc_ar' => 'خدمات الحدائق والتنسيق',
                'fees' => 5.50,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Lawn Mowing', 'ar' => 'قص العشب'],
                    ['en' => 'Tree Trimming', 'ar' => 'تقليم الأشجار'],
                    ['en' => 'Garden Design', 'ar' => 'تصميم الحدائق'],
                    ['en' => 'Irrigation Installation', 'ar' => 'تركيب الري'],
                    ['en' => 'Planting Services', 'ar' => 'خدمات الزراعة'],
                    ['en' => 'Pest Control', 'ar' => 'مكافحة الآفات'],
                    ['en' => 'Hardscaping', 'ar' => 'تنسيق الحجر'],
                    ['en' => 'Pool Landscaping', 'ar' => 'تنسيق المسابح'],
                ],
            ],
            [
                'icon' => '🚚',
                'en' => 'Moving & Relocation',
                'ar' => 'النقل والترحيل',
                'desc_en' => 'Moving and relocation services',
                'desc_ar' => 'خدمات النقل والترحيل',
                'fees' => 4.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Local Moving', 'ar' => 'نقل محلي'],
                    ['en' => 'Long Distance Moving', 'ar' => 'نقل بعيد المدى'],
                    ['en' => 'Packing Services', 'ar' => 'خدمات التغليف'],
                    ['en' => 'Furniture Disassembly', 'ar' => 'فك الأثاث'],
                    ['en' => 'Storage Services', 'ar' => 'خدمات التخزين'],
                    ['en' => 'Office Relocation', 'ar' => 'نقل المكاتب'],
                    ['en' => 'Piano Moving', 'ar' => 'نقل البيانو'],
                    ['en' => 'Vehicle Transport', 'ar' => 'نقل المركبات'],
                ],
            ],
            [
                'icon' => '🔒',
                'en' => 'Security Systems',
                'ar' => 'أنظمة الأمان',
                'desc_en' => 'Security installation services',
                'desc_ar' => 'خدمات تركيب أنظمة الأمان',
                'fees' => 8.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'CCTV Installation', 'ar' => 'تركيب كاميرات المراقبة'],
                    ['en' => 'Alarm Systems', 'ar' => 'أنظمة الإنذار'],
                    ['en' => 'Access Control', 'ar' => 'التحكم في الدخول'],
                    ['en' => 'Intercom Systems', 'ar' => 'أنظمة الاتصال الداخلي'],
                    ['en' => 'Smart Locks', 'ar' => 'الأقفال الذكية'],
                    ['en' => 'Fire Alarm Systems', 'ar' => 'أنظمة إنذار الحريق'],
                    ['en' => 'Security Consulting', 'ar' => 'استشارات أمنية'],
                    ['en' => 'Guard Services', 'ar' => 'خدمات الحراسة'],
                ],
            ],
            [
                'icon' => '🛠️',
                'en' => 'Appliance Repair',
                'ar' => 'إصلاح الأجهزة',
                'desc_en' => 'Home appliance repair services',
                'desc_ar' => 'خدمات إصلاح الأجهزة المنزلية',
                'fees' => 6.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Refrigerator Repair', 'ar' => 'إصلاح الثلاجات'],
                    ['en' => 'Washing Machine Repair', 'ar' => 'إصلاح الغسالات'],
                    ['en' => 'Dryer Repair', 'ar' => 'إصلاح المجففات'],
                    ['en' => 'Oven Repair', 'ar' => 'إصلاح الأفران'],
                    ['en' => 'Dishwasher Repair', 'ar' => 'إصلاح غسالات الصحون'],
                    ['en' => 'Microwave Repair', 'ar' => 'إصلاح الميكروويف'],
                    ['en' => 'TV Repair', 'ar' => 'إصلاح التلفزيونات'],
                    ['en' => 'Small Appliance Repair', 'ar' => 'إصلاح الأجهزة الصغيرة'],
                ],
            ],
            [
                'icon' => '🏗️',
                'en' => 'Construction',
                'ar' => 'البناء والتشييد',
                'desc_en' => 'Construction and renovation services',
                'desc_ar' => 'خدمات البناء والتجديد',
                'fees' => 10.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Home Renovation', 'ar' => 'تجديد المنازل'],
                    ['en' => 'Room Addition', 'ar' => 'إضافة غرف'],
                    ['en' => 'Kitchen Remodeling', 'ar' => 'إعادة تصميم المطبخ'],
                    ['en' => 'Bathroom Remodeling', 'ar' => 'إعادة تصميم الحمام'],
                    ['en' => 'Roofing', 'ar' => 'أعمال السقف'],
                    ['en' => 'Foundation Repair', 'ar' => 'إصلاح الأساسات'],
                    ['en' => 'Masonry Work', 'ar' => 'أعمال البناء'],
                    ['en' => 'Demolition', 'ar' => 'الهدم'],
                ],
            ],
            [
                'icon' => '🧹',
                'en' => 'Commercial Cleaning',
                'ar' => 'التنظيف التجاري',
                'desc_en' => 'Commercial cleaning services',
                'desc_ar' => 'خدمات التنظيف التجاري',
                'fees' => 6.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Office Cleaning', 'ar' => 'تنظيف المكاتب'],
                    ['en' => 'Retail Store Cleaning', 'ar' => 'تنظيف المتاجر'],
                    ['en' => 'Restaurant Cleaning', 'ar' => 'تنظيف المطاعم'],
                    ['en' => 'Warehouse Cleaning', 'ar' => 'تنظيف المستودعات'],
                    ['en' => 'Medical Facility Cleaning', 'ar' => 'تنظيف المرافق الطبية'],
                    ['en' => 'School Cleaning', 'ar' => 'تنظيف المدارس'],
                    ['en' => 'Industrial Cleaning', 'ar' => 'التنظيف الصناعي'],
                    ['en' => 'Event Venue Cleaning', 'ar' => 'تنظيف قاعات المناسبات'],
                ],
            ],
            [
                'icon' => '🚗',
                'en' => 'Auto Services',
                'ar' => 'خدمات السيارات',
                'desc_en' => 'Automotive services',
                'desc_ar' => 'خدمات السيارات',
                'fees' => 5.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Car Wash', 'ar' => 'غسيل السيارات'],
                    ['en' => 'Oil Change', 'ar' => 'تغيير الزيت'],
                    ['en' => 'Tire Services', 'ar' => 'خدمات الإطارات'],
                    ['en' => 'Battery Services', 'ar' => 'خدمات البطارية'],
                    ['en' => 'Car Detailing', 'ar' => 'تلميع السيارات'],
                    ['en' => 'AC Repair (Auto)', 'ar' => 'إصلاح مكيف السيارة'],
                    ['en' => 'Brake Services', 'ar' => 'خدمات الفرامل'],
                    ['en' => 'Roadside Assistance', 'ar' => 'المساعدة على الطريق'],
                ],
            ],
            [
                'icon' => '💻',
                'en' => 'IT & Tech Support',
                'ar' => 'الدعم التقني',
                'desc_en' => 'IT and technical support services',
                'desc_ar' => 'خدمات الدعم التقني',
                'fees' => 7.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Computer Repair', 'ar' => 'إصلاح الحاسوب'],
                    ['en' => 'Network Setup', 'ar' => 'إعداد الشبكات'],
                    ['en' => 'Virus Removal', 'ar' => 'إزالة الفيروسات'],
                    ['en' => 'Data Recovery', 'ar' => 'استعادة البيانات'],
                    ['en' => 'Software Installation', 'ar' => 'تثبيت البرامج'],
                    ['en' => 'Smart Home Setup', 'ar' => 'إعداد المنزل الذكي'],
                    ['en' => 'WiFi Installation', 'ar' => 'تركيب الواي فاي'],
                    ['en' => 'Printer Setup', 'ar' => 'إعداد الطابعات'],
                ],
            ],
            [
                'icon' => '👶',
                'en' => 'Childcare',
                'ar' => 'رعاية الأطفال',
                'desc_en' => 'Childcare services',
                'desc_ar' => 'خدمات رعاية الأطفال',
                'fees' => 4.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Babysitting', 'ar' => 'مجالسة الأطفال'],
                    ['en' => 'Nanny Services', 'ar' => 'خدمات المربية'],
                    ['en' => 'Tutoring', 'ar' => 'الدروس الخصوصية'],
                    ['en' => 'After School Care', 'ar' => 'رعاية ما بعد المدرسة'],
                    ['en' => 'Special Needs Care', 'ar' => 'رعاية ذوي الاحتياجات'],
                    ['en' => 'Homework Help', 'ar' => 'المساعدة في الواجبات'],
                    ['en' => 'Activity Planning', 'ar' => 'تخطيط الأنشطة'],
                    ['en' => 'Night Care', 'ar' => 'الرعاية الليلية'],
                ],
            ],
            [
                'icon' => '👴',
                'en' => 'Elder Care',
                'ar' => 'رعاية المسنين',
                'desc_en' => 'Elderly care services',
                'desc_ar' => 'خدمات رعاية المسنين',
                'fees' => 5.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Companion Care', 'ar' => 'الرعاية المرافقة'],
                    ['en' => 'Personal Care', 'ar' => 'الرعاية الشخصية'],
                    ['en' => 'Medication Reminders', 'ar' => 'تذكير بالأدوية'],
                    ['en' => 'Meal Preparation', 'ar' => 'إعداد الوجبات'],
                    ['en' => 'Transportation', 'ar' => 'النقل والمواصلات'],
                    ['en' => 'Light Housekeeping', 'ar' => 'التنظيف الخفيف'],
                    ['en' => 'Respite Care', 'ar' => 'الرعاية المؤقتة'],
                    ['en' => '24-Hour Care', 'ar' => 'الرعاية على مدار الساعة'],
                ],
            ],
            [
                'icon' => '🐕',
                'en' => 'Pet Services',
                'ar' => 'خدمات الحيوانات الأليفة',
                'desc_en' => 'Pet care services',
                'desc_ar' => 'خدمات رعاية الحيوانات الأليفة',
                'fees' => 4.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Dog Walking', 'ar' => 'تمشية الكلاب'],
                    ['en' => 'Pet Sitting', 'ar' => 'مجالسة الحيوانات'],
                    ['en' => 'Pet Grooming', 'ar' => 'تجميل الحيوانات'],
                    ['en' => 'Pet Training', 'ar' => 'تدريب الحيوانات'],
                    ['en' => 'Pet Transportation', 'ar' => 'نقل الحيوانات'],
                    ['en' => 'Veterinary Visits', 'ar' => 'زيارات بيطرية'],
                    ['en' => 'Pet Boarding', 'ar' => 'إيواء الحيوانات'],
                    ['en' => 'Aquarium Maintenance', 'ar' => 'صيانة أحواض السمك'],
                ],
            ],
            [
                'icon' => '🎉',
                'en' => 'Event Services',
                'ar' => 'خدمات المناسبات',
                'desc_en' => 'Event planning and services',
                'desc_ar' => 'تخطيط وخدمات المناسبات',
                'fees' => 6.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Event Planning', 'ar' => 'تخطيط المناسبات'],
                    ['en' => 'Catering', 'ar' => 'خدمات الطعام'],
                    ['en' => 'Decoration', 'ar' => 'الديكور والتزيين'],
                    ['en' => 'Photography', 'ar' => 'التصوير'],
                    ['en' => 'Videography', 'ar' => 'تصوير الفيديو'],
                    ['en' => 'DJ Services', 'ar' => 'خدمات DJ'],
                    ['en' => 'Tent & Chair Rental', 'ar' => 'تأجير الخيام والكراسي'],
                    ['en' => 'Entertainment', 'ar' => 'الترفيه'],
                ],
            ],
            [
                'icon' => '✂️',
                'en' => 'Beauty & Personal Care',
                'ar' => 'التجميل والعناية الشخصية',
                'desc_en' => 'Beauty and personal care services',
                'desc_ar' => 'خدمات التجميل والعناية الشخصية',
                'fees' => 5.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Hair Styling', 'ar' => 'تصفيف الشعر'],
                    ['en' => 'Makeup', 'ar' => 'المكياج'],
                    ['en' => 'Manicure & Pedicure', 'ar' => 'العناية بالأظافر'],
                    ['en' => 'Facial Treatment', 'ar' => 'علاج الوجه'],
                    ['en' => 'Massage', 'ar' => 'المساج'],
                    ['en' => 'Henna', 'ar' => 'الحناء'],
                    ['en' => 'Bridal Services', 'ar' => 'خدمات العروس'],
                    ['en' => 'Men\'s Grooming', 'ar' => 'العناية بالرجال'],
                ],
            ],
            [
                'icon' => '🏋️',
                'en' => 'Fitness & Training',
                'ar' => 'اللياقة والتدريب',
                'desc_en' => 'Fitness and training services',
                'desc_ar' => 'خدمات اللياقة والتدريب',
                'fees' => 5.00,
                'fees_type' => 'percentage',
                'skills' => [
                    ['en' => 'Personal Training', 'ar' => 'التدريب الشخصي'],
                    ['en' => 'Yoga Instruction', 'ar' => 'تعليم اليوغا'],
                    ['en' => 'Swimming Lessons', 'ar' => 'دروس السباحة'],
                    ['en' => 'Martial Arts', 'ar' => 'الفنون القتالية'],
                    ['en' => 'Nutrition Coaching', 'ar' => 'استشارات التغذية'],
                    ['en' => 'Group Fitness', 'ar' => 'اللياقة الجماعية'],
                    ['en' => 'Sports Coaching', 'ar' => 'تدريب رياضي'],
                    ['en' => 'Rehabilitation', 'ar' => 'إعادة التأهيل'],
                ],
            ],
        ];

        $maxCategoryId = DB::table('categories')->max('id') ?? 0;
        $maxSkillId = DB::table('skills')->max('id') ?? 0;
        $now = now();

        $categoriesData = [];
        $categoryTranslations = [];
        $skillsData = [];
        $skillTranslations = [];

        $categoryId = $maxCategoryId;
        $skillId = $maxSkillId;

        foreach ($categoriesWithSkills as $category) {
            $categoryId++;

            $categoriesData[] = [
                'id' => $categoryId,
                'icon' => $category['icon'],
                'parent_id' => null,
                'fees' => $category['fees'],
                'fees_type' => $category['fees_type'],
                'created_at' => $now->copy()->subDays(rand(30, 365)),
                'updated_at' => $now,
            ];

            $categoryTranslations[] = [
                'category_id' => $categoryId,
                'locale' => 'en',
                'title' => $category['en'],
                'description' => $category['desc_en'],
            ];

            $categoryTranslations[] = [
                'category_id' => $categoryId,
                'locale' => 'ar',
                'title' => $category['ar'],
                'description' => $category['desc_ar'],
            ];

            // Add skills for this category
            foreach ($category['skills'] as $skill) {
                $skillId++;

                $skillsData[] = [
                    'id' => $skillId,
                    'category_id' => $categoryId,
                    'created_at' => $now->copy()->subDays(rand(1, 365)),
                    'updated_at' => $now,
                ];

                $skillTranslations[] = [
                    'skill_id' => $skillId,
                    'locale' => 'en',
                    'title' => $skill['en'],
                ];

                $skillTranslations[] = [
                    'skill_id' => $skillId,
                    'locale' => 'ar',
                    'title' => $skill['ar'],
                ];
            }
        }

        // Insert categories
        foreach (array_chunk($categoriesData, 50) as $chunk) {
            DB::table('categories')->insert($chunk);
        }

        foreach (array_chunk($categoryTranslations, 100) as $chunk) {
            DB::table('category_translations')->insert($chunk);
        }

        // Insert skills
        foreach (array_chunk($skillsData, 100) as $chunk) {
            DB::table('skills')->insert($chunk);
        }

        foreach (array_chunk($skillTranslations, 200) as $chunk) {
            DB::table('skill_translations')->insert($chunk);
        }

        echo 'Added '.count($categoriesData).' categories with '.count($skillsData)." skills.\n";
    }
}
