<?php

namespace Modules\Classifieds\Database\Seeders;

use App\Models\City;
use App\Models\PropertiyCategory;
use App\Models\PropertyType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Models\PropertyAdvisement;

class PropertyAdvisementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()->pluck('id')->toArray();
        $cityIds = City::query()->pluck('id')->toArray();
        $regionIds = Region::query()->pluck('id')->toArray();
        $propertyTypeIds = PropertyType::query()->pluck('id')->toArray();
        $categoryIds = PropertiyCategory::query()->whereNotNull('parent_id')->pluck('id')->toArray();

        if (empty($userIds)) {
            $userIds = User::factory(3)->create()->pluck('id')->toArray();
        }

        if (empty($cityIds) || empty($regionIds) || empty($propertyTypeIds) || empty($categoryIds)) {
            throw new \Exception('Missing required data. Please ensure cities, regions, property_types, and child propertiy_categories exist before running this seeder.');
        }

        $advisements = [
            [
                'title' => 'Modern Villa For Sale in Riyadh',
                'description' => 'A stunning modern villa with 5 bedrooms, private swimming pool, and a spacious garden. Located in a prestigious neighborhood with easy access to highways.',
                'status' => AdvisementStatusEnum::PUBLISHED,
                'operation' => OperationEnum::SALE,
                'facade' => 'north',
                'street_width' => '15m',
                'street_type' => 'main',
                'age' => 5,
                'area' => 600,
                'price' => 3500000,
                'show_price' => true,
                'bedrooms_count' => 5,
                'bathrooms_count' => 4,
                'halls_count' => 2,
                'phone' => '966501234567',
                'license' => 'LIC-2024-001',
                'latitude' => '24.7136',
                'longitude' => '46.6753',
                'address' => 'Al-Nakheel District, Riyadh',
            ],
            [
                'title' => 'Apartment For Rent in Jeddah',
                'description' => 'A spacious 3-bedroom apartment with sea view, fully furnished. Located in a prime area near shopping centers and restaurants.',
                'status' => AdvisementStatusEnum::PUBLISHED,
                'operation' => OperationEnum::RENT,
                'facade' => 'west',
                'street_width' => '10m',
                'street_type' => 'residential',
                'age' => 3,
                'area' => 180,
                'price' => 85000,
                'show_price' => true,
                'bedrooms_count' => 3,
                'bathrooms_count' => 2,
                'halls_count' => 1,
                'phone' => '966502345678',
                'license' => null,
                'latitude' => '21.4858',
                'longitude' => '39.1925',
                'address' => 'Al-Corniche, Jeddah',
            ],
            [
                'title' => 'Commercial Land For Sale',
                'description' => 'A prime commercial land piece located near the main highway, suitable for building a shopping complex or commercial center.',
                'status' => AdvisementStatusEnum::PUBLISHED,
                'operation' => OperationEnum::SALE,
                'facade' => 'south',
                'street_width' => '20m',
                'street_type' => 'commercial',
                'age' => 0,
                'area' => 1200,
                'price' => 8000000,
                'show_price' => false,
                'bedrooms_count' => 0,
                'bathrooms_count' => 0,
                'halls_count' => 0,
                'phone' => '966503456789',
                'license' => 'LIC-2024-002',
                'latitude' => '24.6877',
                'longitude' => '46.7219',
                'address' => 'King Fahd Road, Riyadh',
            ],
            [
                'title' => 'Studio Apartment For Rent - Near University',
                'description' => 'A cozy studio apartment, recently renovated, perfect for students or young professionals. Close to public transportation.',
                'status' => AdvisementStatusEnum::PENDING,
                'operation' => OperationEnum::RENT,
                'facade' => 'east',
                'street_width' => '8m',
                'street_type' => 'side',
                'age' => 10,
                'area' => 60,
                'price' => 24000,
                'show_price' => true,
                'bedrooms_count' => 1,
                'bathrooms_count' => 1,
                'halls_count' => 0,
                'phone' => '966504567890',
                'license' => null,
                'latitude' => '24.7789',
                'longitude' => '46.7199',
                'address' => 'Al-Malaz, Riyadh',
            ],
            [
                'title' => 'Luxury Duplex For Buy',
                'description' => 'A luxurious duplex apartment with premium finishes, private rooftop terrace, and panoramic city views. Fully equipped kitchen and smart home system.',
                'status' => AdvisementStatusEnum::PUBLISHED,
                'operation' => OperationEnum::BUY,
                'facade' => 'north-west',
                'street_width' => '12m',
                'street_type' => 'residential',
                'age' => 2,
                'area' => 350,
                'price' => 1800000,
                'show_price' => true,
                'bedrooms_count' => 4,
                'bathrooms_count' => 3,
                'halls_count' => 2,
                'phone' => '966505678901',
                'license' => 'LIC-2024-003',
                'latitude' => '24.7497',
                'longitude' => '46.6872',
                'address' => 'Al-Sulaimaniyah, Riyadh',
            ],
        ];

        PropertyAdvisement::withoutEvents(function () use ($advisements, $userIds, $propertyTypeIds, $cityIds, $regionIds, $categoryIds): void {
            foreach ($advisements as $data) {
                $title = $data['title'];

                PropertyAdvisement::query()->create(array_merge($data, [
                    'normalized_title' => Str::slug($title),
                    'normalized_description' => strip_tags($data['description']),
                    'image' => 'media/property-advisements/placeholder.jpg',
                    'user_type' => User::class,
                    'user_id' => $userIds[array_rand($userIds)],
                    'property_type_id' => $propertyTypeIds[array_rand($propertyTypeIds)],
                    'city_id' => $cityIds[array_rand($cityIds)],
                    'region_id' => $regionIds[array_rand($regionIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'options' => null,
                ]));
            }
        });

        $this->command->info('Created '.count($advisements).' property advisements.');
    }
}
