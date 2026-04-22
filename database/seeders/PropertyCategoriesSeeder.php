<?php

namespace Database\Seeders;

use App\Models\PropertiyCategory;
use Illuminate\Database\Seeder;

class PropertyCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'en' => 'Residential',
                'ar' => 'سكني',
                'children' => [
                    ['en' => 'Family Housing', 'ar' => 'سكن عائلي'],
                    ['en' => 'Luxury Housing', 'ar' => 'سكن فاخر'],
                    ['en' => 'Affordable Housing', 'ar' => 'سكن اقتصادي'],
                ],
            ],
            [
                'en' => 'Commercial',
                'ar' => 'تجاري',
                'children' => [
                    ['en' => 'Retail', 'ar' => 'تجزئة'],
                    ['en' => 'Office', 'ar' => 'مكاتب'],
                    ['en' => 'Warehouse', 'ar' => 'مستودعات'],
                ],
            ],
            [
                'en' => 'Land',
                'ar' => 'أراضي',
                'children' => [
                    ['en' => 'Residential Land', 'ar' => 'أرض سكنية'],
                    ['en' => 'Commercial Land', 'ar' => 'أرض تجارية'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = PropertiyCategory::query()->create([
                'parent_id' => null,
                'is_active' => true,
            ]);

            $category->translateOrNew('en')->title = $categoryData['en'];
            $category->translateOrNew('ar')->title = $categoryData['ar'];
            $category->save();

            foreach ($categoryData['children'] as $childData) {
                $child = PropertiyCategory::query()->create([
                    'parent_id' => $category->id,
                    'is_active' => true,
                ]);

                $child->translateOrNew('en')->title = $childData['en'];
                $child->translateOrNew('ar')->title = $childData['ar'];
                $child->save();
            }
        }

        $this->command?->info('Created ' . count($categories) . ' root property categories with children.');
    }
}
