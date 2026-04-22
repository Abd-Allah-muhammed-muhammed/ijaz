<?php

namespace App\DTOs\PropertyAdvisement;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class PropertyAdvisementDTO
{
    /**
     * @param  array<int, UploadedFile>|null  $files
     * @param  array<mixed>|null  $options
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $operation,
        public int $propertyTypeId,
        public int $cityId,
        public int $regionId,
        public ?int $categoryId,
        public float $price,
        public bool $showPrice,
        public ?float $area,
        public ?int $bedroomsCount,
        public ?int $bathroomsCount,
        public ?int $hallsCount,
        public ?int $age,
        public ?string $facade,
        public ?float $streetWidth,
        public ?string $streetType,
        public ?string $phone,
        public ?string $license,
        public ?string $address,
        public ?float $latitude,
        public ?float $longitude,
        public ?array $options,
        public ?array $files,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = self::validatedInput($request);

        return new self(
            title: (string) $validated['title'],
            description: (string) $validated['description'],
            operation: (string) $validated['operation'],
            propertyTypeId: (int) $validated['property_type_id'],
            cityId: (int) $validated['city_id'],
            regionId: (int) $validated['region_id'],
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            price: (float) $validated['price'],
            showPrice: (bool) ($validated['show_price'] ?? false),
            area: isset($validated['area']) ? (float) $validated['area'] : null,
            bedroomsCount: isset($validated['bedrooms_count']) ? (int) $validated['bedrooms_count'] : null,
            bathroomsCount: isset($validated['bathrooms_count']) ? (int) $validated['bathrooms_count'] : null,
            hallsCount: isset($validated['halls_count']) ? (int) $validated['halls_count'] : null,
            age: isset($validated['age']) ? (int) $validated['age'] : null,
            facade: $validated['facade'] ?? null,
            streetWidth: isset($validated['street_width']) ? (float) $validated['street_width'] : null,
            streetType: $validated['street_type'] ?? null,
            phone: $validated['phone'] ?? null,
            license: $validated['license'] ?? null,
            address: $validated['address'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            options: $validated['options'] ?? null,
            files: $request->hasFile('files') ? $request->file('files') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function validatedInput(Request $request): array
    {
        if (method_exists($request, 'validated')) {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();

            return $validated;
        }

        /** @var array<string, mixed> $all */
        $all = $request->all();

        return $all;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'operation' => $this->operation,
            'property_type_id' => $this->propertyTypeId,
            'city_id' => $this->cityId,
            'region_id' => $this->regionId,
            'category_id' => $this->categoryId,
            'price' => $this->price,
            'show_price' => $this->showPrice,
            'area' => $this->area,
            'bedrooms_count' => $this->bedroomsCount,
            'bathrooms_count' => $this->bathroomsCount,
            'halls_count' => $this->hallsCount,
            'age' => $this->age,
            'facade' => $this->facade,
            'street_width' => $this->streetWidth,
            'street_type' => $this->streetType,
            'phone' => $this->phone,
            'license' => $this->license,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'options' => $this->options,
        ];
    }
}
