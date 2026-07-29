<?php

namespace Modules\Classifieds\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

/**
 * @extends Factory<CarAdvisement>
 */
final class CarAdvisementFactory extends Factory
{
    protected $model = CarAdvisement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(3);
        $description = $this->faker->paragraphs(3, asText: true);

        return [
            'title' => $title,
            'normalized_title' => Str::slug($title),
            'description' => $description,
            'normalized_description' => Str::slug($description),
            'image' => $this->faker->imageUrl(),
            'status' => AdvisementStatusEnum::PUBLISHED,
            'operation' => $this->faker->randomElement(OperationEnum::cases()),
            'usage_status' => $this->faker->randomElement(UsageStatusEnum::cases()),
            'user_type' => User::class,
            'user_id' => User::factory(),
            'car_brand_id' => CarBrand::factory(),
            'car_type_id' => CarType::factory(),
            'car_category_id' => CarCategory::factory(),
            'year' => $this->faker->numberBetween(1990, now()->year + 1),
            'mileage' => $this->faker->numberBetween(0, 250000),
            'transmission' => $this->faker->randomElement(['automatic', 'manual']),
            'fuel_type' => $this->faker->randomElement(['petrol', 'diesel', 'hybrid', 'electric']),
            'engine_size' => $this->faker->randomElement(['1.4', '1.6', '2.0', '2.5', '3.0']),
            'color' => $this->faker->randomElement(['black', 'white', 'silver', 'gray', 'red', 'blue', 'green']),
            'price' => $this->faker->numberBetween(10000, 500000),
            'show_price' => $this->faker->boolean(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'city_id' => City::factory(),
            'region_id' => Region::factory(),
            'options' => [],
        ];
    }

    public function published(): Factory
    {
        return $this->state([
            'status' => AdvisementStatusEnum::PUBLISHED,
        ]);
    }

    public function pending(): Factory
    {
        return $this->state([
            'status' => AdvisementStatusEnum::PENDING,
        ]);
    }

    public function forSale(): Factory
    {
        return $this->state([
            'operation' => OperationEnum::SALE,
        ]);
    }

    public function forRent(): Factory
    {
        return $this->state([
            'operation' => OperationEnum::RENT,
        ]);
    }

    public function forBuy(): Factory
    {
        return $this->state([
            'operation' => OperationEnum::BUY,
        ]);
    }

    public function newListing(): Factory
    {
        return $this->state([
            'usage_status' => UsageStatusEnum::NEW,
        ]);
    }

    public function used(): Factory
    {
        return $this->state([
            'usage_status' => UsageStatusEnum::USED,
        ]);
    }
}
