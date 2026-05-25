<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\DeviceCategory;

class DeviceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'en' => 'Mobiles & Tablets',
                'ar' => 'موبايلات وتابلت',
                'children' => [
                    ['en' => 'Mobile Phones', 'ar' => 'هواتف محمولة'],
                    ['en' => 'Tablets', 'ar' => 'أجهزة لوحية'],
                    ['en' => 'Accessories', 'ar' => 'إكسسوارات'],
                ],
            ],
            [
                'en' => 'Laptops & Computers',
                'ar' => 'لابتوبات وكمبيوترات',
                'children' => [
                    ['en' => 'Laptops', 'ar' => 'لابتوبات'],
                    ['en' => 'Desktop Computers', 'ar' => 'أجهزة كمبيوتر مكتبية'],
                    ['en' => 'Computer Parts', 'ar' => 'قطع غيار كمبيوتر'],
                ],
            ],
            [
                'en' => 'TVs & Screens',
                'ar' => 'تليفزيونات وشاشات',
                'children' => [
                    ['en' => 'TVs', 'ar' => 'تليفزيونات'],
                    ['en' => 'Computer Screens', 'ar' => 'شاشات كمبيوتر'],
                ],
            ],
            [
                'en' => 'Audio & Headphones',
                'ar' => 'صوتيات وسماعات',
                'children' => [
                    ['en' => 'Headphones', 'ar' => 'سماعات رأس'],
                    ['en' => 'Speakers', 'ar' => 'مكبرات صوت'],
                    ['en' => 'Microphones', 'ar' => 'ميكروفونات'],
                ],
            ],
            [
                'en' => 'Cameras & Photography',
                'ar' => 'كاميرات وتصوير',
                'children' => [
                    ['en' => 'Cameras', 'ar' => 'كاميرات'],
                    ['en' => 'Camera Accessories', 'ar' => 'إكسسوارات كاميرات'],
                ],
            ],
            [
                'en' => 'Gaming',
                'ar' => 'جيمينج',
                'children' => [
                    ['en' => 'Consoles', 'ar' => 'أجهزة ألعاب'],
                    ['en' => 'Games', 'ar' => 'ألعاب'],
                    ['en' => 'Gaming Accessories', 'ar' => 'إكسسوارات جيمينج'],
                ],
            ],
            [
                'en' => 'Home Appliances',
                'ar' => 'أجهزة منزلية',
                'children' => [
                    ['en' => 'Kitchen Appliances', 'ar' => 'أجهزة مطبخ'],
                    ['en' => 'Cleaning Appliances', 'ar' => 'أجهزة تنظيف'],
                ],
            ],
            [
                'en' => 'Other Electronics',
                'ar' => 'إلكترونيات أخرى',
                'children' => [
                    ['en' => 'Smart Devices', 'ar' => 'أجهزة ذكية'],
                    ['en' => 'Miscellaneous', 'ar' => 'متنوعة'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = DeviceCategory::query()->create([
                'parent_id' => null,
            ]);

            $category->translateOrNew('en')->title = $categoryData['en'];
            $category->translateOrNew('ar')->title = $categoryData['ar'];
            $category->save();

            foreach ($categoryData['children'] as $childData) {
                $child = DeviceCategory::query()->create([
                    'parent_id' => $category->id,
                ]);

                $child->translateOrNew('en')->title = $childData['en'];
                $child->translateOrNew('ar')->title = $childData['ar'];
                $child->save();
            }
        }

        $this->command?->info('Created '.count($categories).' root device categories with children.');
    }
}
