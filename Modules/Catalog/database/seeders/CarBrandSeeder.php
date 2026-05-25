<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\CarBrand;

class CarBrandSeeder extends Seeder
{
    public function run(): void
    {
        $carBrands = [
            ['en' => 'Toyota', 'ar' => 'تويوتا'],
            ['en' => 'Honda', 'ar' => 'هوندا'],
            ['en' => 'Ford', 'ar' => 'فورد'],
            ['en' => 'Chevrolet', 'ar' => 'شيفروليه'],
            ['en' => 'BMW', 'ar' => 'بي إم دبليو'],
            ['en' => 'Mercedes-Benz', 'ar' => 'مرسيدس بنز'],
            ['en' => 'Audi', 'ar' => 'أودي'],
            ['en' => 'Nissan', 'ar' => 'نيسان'],
            ['en' => 'Hyundai', 'ar' => 'هيونداي'],
            ['en' => 'Kia', 'ar' => 'كيا'],
            ['en' => 'Volkswagen', 'ar' => 'فولكس فاجن'],
            ['en' => 'Porsche', 'ar' => 'بورش'],
            ['en' => 'Ferrari', 'ar' => 'فيراري'],
            ['en' => 'Lamborghini', 'ar' => 'لامبورغيني'],
            ['en' => 'Tesla', 'ar' => 'تيسلا'],
        ];

        foreach ($carBrands as $item) {
            $carBrand = CarBrand::query()->create([
                'is_active' => true,
            ]);

            $carBrand->translateOrNew('en')->name = $item['en'];
            $carBrand->translateOrNew('ar')->name = $item['ar'];
            $carBrand->save();
        }

        $this->command?->info('Created '.count($carBrands).' car brands.');
    }
}
