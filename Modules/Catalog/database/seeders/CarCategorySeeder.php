<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\CarCategory;

class CarCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'en' => 'Sedan',
                'ar' => 'سيدان',
                'children' => [
                    ['en' => 'Compact Sedan', 'ar' => 'سيدان صغير'],
                    ['en' => 'Mid-Size Sedan', 'ar' => 'سيدان متوسط'],
                    ['en' => 'Full-Size Sedan', 'ar' => 'سيدان كامل الحجم'],
                    ['en' => 'Luxury Sedan', 'ar' => 'سيدان فاخر'],
                ],
            ],
            [
                'en' => 'SUV',
                'ar' => 'سيارات الدفع الرباعي',
                'children' => [
                    ['en' => 'Compact SUV', 'ar' => 'سيارات دفع رباعي صغيرة'],
                    ['en' => 'Mid-Size SUV', 'ar' => 'سيارات دفع رباعي متوسطة'],
                    ['en' => 'Full-Size SUV', 'ar' => 'سيارات دفع رباعي كاملة'],
                    ['en' => 'Luxury SUV', 'ar' => 'سيارات دفع رباعي فاخرة'],
                ],
            ],
            [
                'en' => 'Truck',
                'ar' => 'شاحنة',
                'children' => [
                    ['en' => 'Pickup Truck', 'ar' => 'شاحنة بيك أب'],
                    ['en' => 'Cargo Truck', 'ar' => 'شاحنة نقل'],
                    ['en' => 'Dump Truck', 'ar' => 'شاحنة قلابة'],
                ],
            ],
            [
                'en' => 'Hatchback',
                'ar' => 'هاتشباك',
                'children' => [
                    ['en' => 'Subcompact Hatchback', 'ar' => 'هاتشباك صغير جداً'],
                    ['en' => 'Compact Hatchback', 'ar' => 'هاتشباك صغير'],
                    ['en' => 'Mid-Size Hatchback', 'ar' => 'هاتشباك متوسط'],
                ],
            ],
            [
                'en' => 'Coupe',
                'ar' => 'كوبيه',
                'children' => [
                    ['en' => 'Sports Coupe', 'ar' => 'كوبيه رياضية'],
                    ['en' => 'Luxury Coupe', 'ar' => 'كوبيه فاخرة'],
                    ['en' => 'Grand Coupe', 'ar' => 'كوبيه كبيرة'],
                ],
            ],
            [
                'en' => 'Convertible',
                'ar' => 'مكشوفة',
                'children' => [
                    ['en' => 'Roadster', 'ar' => 'رودستر'],
                    ['en' => 'Cabriolet', 'ar' => 'كابريوليه'],
                ],
            ],
            [
                'en' => 'Wagon',
                'ar' => 'عربة',
                'children' => [
                    ['en' => 'Estate Wagon', 'ar' => 'عربة إستيت'],
                    ['en' => 'Shooting Brake', 'ar' => 'شوتينغ بريك'],
                ],
            ],
            [
                'en' => 'Van',
                'ar' => 'فان',
                'children' => [
                    ['en' => 'Minivan', 'ar' => 'ميني فان'],
                    ['en' => 'Cargo Van', 'ar' => 'فان نقل'],
                    ['en' => 'Passenger Van', 'ar' => 'فان ركاب'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = CarCategory::query()->create([
                'parent_id' => null,
            ]);

            $category->translateOrNew('en')->title = $categoryData['en'];
            $category->translateOrNew('ar')->title = $categoryData['ar'];
            $category->save();

            foreach ($categoryData['children'] as $childData) {
                $child = CarCategory::query()->create([
                    'parent_id' => $category->id,
                ]);

                $child->translateOrNew('en')->title = $childData['en'];
                $child->translateOrNew('ar')->title = $childData['ar'];
                $child->save();
            }
        }

        $this->command?->info('Created '.count($categories).' root car categories with children.');
    }
}
