<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Classifieds\Database\Seeders\PropertyAdvisementsSeeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminRootSeeder::class,
            AdminPermissionSeeder::class,
            ProviderPermissionsSeeder::class,
            SettingsSeeder::class,
            RegionsAndCitiesSeeder::class,
            PropertyTypesSeeder::class,
            PropertyCategoriesSeeder::class,
            PropertyAdvisementsSeeder::class,
        ]);
    }
}
