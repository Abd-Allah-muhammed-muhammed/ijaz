<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypesSeeder extends Seeder
{
  public function run(): void
  {
    $propertyTypes = [
      ['en' => 'Villa', 'ar' => 'فيلا'],
      ['en' => 'Apartment', 'ar' => 'شقة'],
      ['en' => 'Land', 'ar' => 'أرض'],
      ['en' => 'Commercial', 'ar' => 'تجاري'],
      ['en' => 'House', 'ar' => 'منزل'],
      ['en' => 'Studio', 'ar' => 'استوديو'],
    ];

    foreach ($propertyTypes as $item) {
      $propertyType = PropertyType::query()->create([
        'is_active' => true,
      ]);

      $propertyType->translateOrNew('en')->name = $item['en'];
      $propertyType->translateOrNew('ar')->name = $item['ar'];
      $propertyType->save();
    }

    $this->command?->info('Created ' . count($propertyTypes) . ' property types.');
  }
}
