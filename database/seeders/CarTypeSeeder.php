<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarType;
use Illuminate\Database\Seeder;

class CarTypeSeeder extends Seeder
{
    public function run(): void
    {
        $carTypes = [
            // Toyota
            ['brand' => 'Toyota', 'models' => [
                ['en' => 'Camry', 'ar' => 'كامري'],
                ['en' => 'Corolla', 'ar' => 'كورولا'],
                ['en' => 'RAV4', 'ar' => 'راف 4'],
                ['en' => 'Highlander', 'ar' => 'هاي لاندر'],
                ['en' => 'Prius', 'ar' => 'بريوس'],
                ['en' => 'Land Cruiser', 'ar' => 'لاند كروزر'],
            ]],
            // Honda
            ['brand' => 'Honda', 'models' => [
                ['en' => 'Civic', 'ar' => 'سيفيك'],
                ['en' => 'Accord', 'ar' => 'أكورد'],
                ['en' => 'CR-V', 'ar' => 'سي آر في'],
                ['en' => 'Pilot', 'ar' => 'بايلوت'],
                ['en' => 'HR-V', 'ar' => 'إتش آر في'],
            ]],
            // Ford
            ['brand' => 'Ford', 'models' => [
                ['en' => 'F-150', 'ar' => 'إف 150'],
                ['en' => 'Mustang', 'ar' => 'موستنج'],
                ['en' => 'Explorer', 'ar' => 'إكسبلورر'],
                ['en' => 'Focus', 'ar' => 'فوكس'],
                ['en' => 'Escape', 'ar' => 'إسكيب'],
            ]],
            // Chevrolet
            ['brand' => 'Chevrolet', 'models' => [
                ['en' => 'Silverado', 'ar' => 'سيلفرادو'],
                ['en' => 'Malibu', 'ar' => 'ماليبو'],
                ['en' => 'Equinox', 'ar' => 'إكوينوكس'],
                ['en' => 'Tahoe', 'ar' => 'تاهو'],
                ['en' => 'Camaro', 'ar' => 'كامارو'],
            ]],
            // BMW
            ['brand' => 'BMW', 'models' => [
                ['en' => '3 Series', 'ar' => 'سلسلة 3'],
                ['en' => '5 Series', 'ar' => 'سلسلة 5'],
                ['en' => 'X3', 'ar' => 'إكس 3'],
                ['en' => 'X5', 'ar' => 'إكس 5'],
                ['en' => 'i3', 'ar' => 'آي 3'],
            ]],
            // Mercedes-Benz
            ['brand' => 'Mercedes-Benz', 'models' => [
                ['en' => 'C-Class', 'ar' => 'فئة سي'],
                ['en' => 'E-Class', 'ar' => 'فئة إي'],
                ['en' => 'GLE', 'ar' => 'جي إل إي'],
                ['en' => 'S-Class', 'ar' => 'فئة إس'],
                ['en' => 'A-Class', 'ar' => 'فئة أي'],
            ]],
            // Audi
            ['brand' => 'Audi', 'models' => [
                ['en' => 'A3', 'ar' => 'أي 3'],
                ['en' => 'A4', 'ar' => 'أي 4'],
                ['en' => 'Q5', 'ar' => 'كيو 5'],
                ['en' => 'Q7', 'ar' => 'كيو 7'],
                ['en' => 'A6', 'ar' => 'أي 6'],
            ]],
            // Nissan
            ['brand' => 'Nissan', 'models' => [
                ['en' => 'Altima', 'ar' => 'ألتيما'],
                ['en' => 'Sentra', 'ar' => 'سنترا'],
                ['en' => 'Rogue', 'ar' => 'روغ'],
                ['en' => 'Pathfinder', 'ar' => 'باثفايندر'],
                ['en' => 'Titan', 'ar' => 'تيتان'],
            ]],
            // Hyundai
            ['brand' => 'Hyundai', 'models' => [
                ['en' => 'Elantra', 'ar' => 'إلانترا'],
                ['en' => 'Sonata', 'ar' => 'سوناتا'],
                ['en' => 'Tucson', 'ar' => 'توكسون'],
                ['en' => 'Santa Fe', 'ar' => 'سانتا في'],
                ['en' => 'Kona', 'ar' => 'كونا'],
            ]],
            // Kia
            ['brand' => 'Kia', 'models' => [
                ['en' => 'Forte', 'ar' => 'فورتي'],
                ['en' => 'Optima', 'ar' => 'أوبتيما'],
                ['en' => 'Sportage', 'ar' => 'سبورتاج'],
                ['en' => 'Sorento', 'ar' => 'سورينتو'],
                ['en' => 'Soul', 'ar' => 'سول'],
            ]],
        ];

        $totalCreated = 0;

        foreach ($carTypes as $brandData) {
            $brand = CarBrand::whereTranslation('name', $brandData['brand'])->first();

            if (! $brand) {
                $this->command?->warn("Brand '{$brandData['brand']}' not found, skipping its models.");

                continue;
            }

            foreach ($brandData['models'] as $modelData) {
                $carType = CarType::query()->create([
                    'car_brand_id' => $brand->id,
                    'is_active' => true,
                ]);

                $carType->translateOrNew('en')->name = $modelData['en'];
                $carType->translateOrNew('ar')->name = $modelData['ar'];
                $carType->save();

                $totalCreated++;
            }
        }

        $this->command?->info("Created {$totalCreated} car types for " . count($carTypes) . ' brands.');
    }
}
