<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Database\Seeders\DeviceCategorySeeder;
use Modules\Catalog\Database\Seeders\PropertyCategoriesSeeder;
use Modules\Catalog\Database\Seeders\PropertyTypesSeeder;
use Modules\Catalog\Database\Seeders\SpecializationSeeder;
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
            // Roles/permissions only — create the first admin with: php artisan admin:create
            RolePermissionSeeder::class,
            SystemSeeder::class,
            ProviderPermissionsSeeder::class,
            SettingsSeeder::class,
            RegionsAndCitiesSeeder::class,
            \Modules\Catalog\Database\Seeders\BanksSeeder::class,
            PropertyTypesSeeder::class,
            PropertyCategoriesSeeder::class,
            DeviceCategorySeeder::class,
            SpecializationSeeder::class,
            PropertyAdvisementsSeeder::class,
        ]);
    }
}
