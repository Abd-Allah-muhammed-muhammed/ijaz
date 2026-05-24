<?php

namespace Modules\Classifieds\Database\Factories;

use App\Models\City;
use App\Models\PropertiyCategory;
use App\Models\PropertyType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Models\PropertyAdvisement;

/**
 * @extends Factory<PropertyAdvisement>
 */
class PropertyAdvisementFactory extends Factory
{
    protected $model = PropertyAdvisement::class;

    /** @var array<int, string> */
    private array $facades = ['north', 'south', 'east', 'west', 'north-east', 'north-west', 'south-east', 'south-west'];

    /** @var array<int, string> */
    private array $streetTypes = ['main', 'side', 'residential', 'commercial'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'normalized_title' => Str::slug($title),
            'description' => fake()->paragraph(3),
            'normalized_description' => fake()->paragraph(3),
            'image' => 'media/property-advisements/placeholder.jpg',
            'status' => fake()->randomElement(AdvisementStatusEnum::cases()),
            'operation' => fake()->randomElement(OperationEnum::cases()),
            'facade' => fake()->randomElement($this->facades),
            'street_width' => fake()->randomElement(['6m', '8m', '10m', '12m', '15m', '20m']),
            'street_type' => fake()->randomElement($this->streetTypes),
            'user_type' => User::class,
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'age' => fake()->numberBetween(1, 50),
            'area' => fake()->numberBetween(80, 1000),
            'price' => fake()->randomFloat(2, 100000, 5000000),
            'show_price' => fake()->boolean(80),
            'bedrooms_count' => fake()->numberBetween(1, 8),
            'bathrooms_count' => fake()->numberBetween(1, 5),
            'halls_count' => fake()->numberBetween(1, 4),
            'phone' => '9665'.fake()->numerify('########'),
            'license' => fake()->boolean(60) ? fake()->bothify('???-####') : null,
            'options' => null,
            'latitude' => (string) fake()->latitude(23, 26),
            'longitude' => (string) fake()->longitude(45, 50),
            'address' => fake()->address(),
            'property_type_id' => PropertyType::query()->inRandomOrder()->value('id') ?? PropertyType::factory(),
            'city_id' => City::query()->inRandomOrder()->value('id') ?? City::factory(),
            'region_id' => Region::query()->inRandomOrder()->value('id') ?? Region::factory(),
            'category_id' => PropertiyCategory::query()->inRandomOrder()->value('id') ?? PropertiyCategory::factory(),
        ];
    }

    /**
     * Set the status to published.
     */
    public function published(): static
    {
        return $this->state(['status' => AdvisementStatusEnum::PUBLISHED]);
    }

    /**
     * Set the status to pending.
     */
    public function pending(): static
    {
        return $this->state(['status' => AdvisementStatusEnum::PENDING]);
    }

    /**
     * Set the operation to sale.
     */
    public function forSale(): static
    {
        return $this->state(['operation' => OperationEnum::SALE]);
    }

    /**
     * Set the operation to rent.
     */
    public function forRent(): static
    {
        return $this->state(['operation' => OperationEnum::RENT]);
    }
}
