<?php

namespace Modules\Classifieds\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class InstituteAdvisementDTO
{
    /**
     * @param  array<mixed>|null  $options
     * @param  array<int, UploadedFile>|null  $files
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $type,
        public string $studyType,
        public int $specializationId,
        public int $cityId,
        public int $regionId,
        public ?float $price = null,
        public ?float $discountedPrice = null,
        public ?int $daysCount = null,
        public ?int $hoursCount = null,
        public ?string $goals = null,
        public ?string $paymentNotes = null,
        public ?string $courseUrl = null,
        public ?string $studyLevel = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $registrationUrl = null,
        public ?string $qualityUrl = null,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $registrationStart = null,
        public ?string $registrationEnd = null,
        public ?string $studyStart = null,
        public ?string $studyEnd = null,
        public ?array $options = null,
        public ?array $files = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = self::validatedInput($request);

        return new self(
            title: (string) $validated['title'],
            description: (string) $validated['description'],
            type: (string) $validated['type'],
            studyType: (string) $validated['study_type'],
            specializationId: (int) $validated['specialization_id'],
            cityId: (int) $validated['city_id'],
            regionId: (int) $validated['region_id'],
            price: isset($validated['price']) ? (float) $validated['price'] : null,
            discountedPrice: isset($validated['discounted_price']) ? (float) $validated['discounted_price'] : null,
            daysCount: isset($validated['days_count']) ? (int) $validated['days_count'] : null,
            hoursCount: isset($validated['hours_count']) ? (int) $validated['hours_count'] : null,
            goals: $validated['goals'] ?? null,
            paymentNotes: $validated['payment_notes'] ?? null,
            courseUrl: $validated['course_url'] ?? null,
            studyLevel: $validated['study_level'] ?? null,
            phone: $validated['phone'] ?? null,
            website: $validated['website'] ?? null,
            registrationUrl: $validated['registration_url'] ?? null,
            qualityUrl: $validated['quality_url'] ?? null,
            address: $validated['address'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            registrationStart: $validated['registration_start'] ?? null,
            registrationEnd: $validated['registration_end'] ?? null,
            studyStart: $validated['study_start'] ?? null,
            studyEnd: $validated['study_end'] ?? null,
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
            'type' => $this->type,
            'study_type' => $this->studyType,
            'specialization_id' => $this->specializationId,
            'city_id' => $this->cityId,
            'region_id' => $this->regionId,
            'price' => $this->price,
            'discounted_price' => $this->discountedPrice,
            'days_count' => $this->daysCount,
            'hours_count' => $this->hoursCount,
            'goals' => $this->goals,
            'payment_notes' => $this->paymentNotes,
            'course_url' => $this->courseUrl,
            'study_level' => $this->studyLevel,
            'phone' => $this->phone,
            'website' => $this->website,
            'registration_url' => $this->registrationUrl,
            'quality_url' => $this->qualityUrl,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'registration_start' => $this->registrationStart,
            'registration_end' => $this->registrationEnd,
            'study_start' => $this->studyStart,
            'study_end' => $this->studyEnd,
            'options' => $this->options,
        ];
    }
}
