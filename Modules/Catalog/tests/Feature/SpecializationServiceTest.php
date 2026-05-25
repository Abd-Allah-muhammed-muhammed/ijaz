<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Http\Requests\Dashboard\SpecializationRequest;
use Modules\Catalog\Models\Specialization;
use Tests\TestCase;

beforeEach(function (): void {
    Storage::fake();
});

it('creates a specialization with multi-locale translations', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);

    $dto = new StoreSpecializationDTO(
        translations: [
            ['locale' => 'en', 'title' => 'Web Development'],
            ['locale' => 'ar', 'title' => 'تطوير الويب'],
        ],
        icon: null,
        parentId: null,
    );

    $specialization = $service->store($dto);

    expect($specialization->translate('en')->title)->toBe('Web Development')
        ->and($specialization->translate('ar')->title)->toBe('تطوير الويب')
        ->and($specialization->parent_id)->toBeNull();
});

it('builds StoreSpecializationDTO from request', function (): void {
    $request = SpecializationRequest::create('/', 'POST', [
        'translations' => [
            'en' => ['title' => 'Cyber Security'],
            'ar' => ['title' => 'الأمن السيبراني'],
        ],
        'parent_id' => null,
    ]);
    $request->setContainer(app())->validateResolved();

    $dto = StoreSpecializationDTO::fromRequest($request);

    expect($dto->translations)->toHaveCount(2)
        ->and($dto->translations[0])->toMatchArray(['locale' => 'en', 'title' => 'Cyber Security'])
        ->and($dto->translations[1])->toMatchArray(['locale' => 'ar', 'title' => 'الأمن السيبراني']);
});

it('updates a specialization and replaces translations', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);
    $specialization = Specialization::factory()->create();

    $dto = new UpdateSpecializationDTO(
        translations: [
            ['locale' => 'en', 'title' => 'Updated EN Title'],
            ['locale' => 'ar', 'title' => 'العنوان المحدث'],
        ],
        icon: null,
        parentId: null,
    );

    $updated = $service->update($specialization, $dto);

    expect($updated->fresh()->translate('en')->title)->toBe('Updated EN Title')
        ->and($updated->fresh()->translate('ar')->title)->toBe('العنوان المحدث');
});

it('stores icon to storage when provided', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);
    Storage::fake();

    $request = SpecializationRequest::create('/', 'POST', [
        'translations' => [
            'en' => ['title' => 'Languages'],
            'ar' => ['title' => 'اللغات'],
        ],
        'parent_id' => null,
    ], [], [
        'icon' => UploadedFile::fake()->image('icon.png'),
    ]);
    $request->setContainer(app())->validateResolved();

    $dto = StoreSpecializationDTO::fromRequest($request);
    $specialization = $service->store($dto);

    expect($specialization->icon)->toStartWith('specializations/')
        ->and(Storage::exists($specialization->icon))->toBeTrue();
});

it('paginates specializations and filters root specializations', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);
    $parent = Specialization::factory()->create();
    Specialization::factory()->count(2)->create(['parent_id' => $parent->id]);
    Specialization::factory()->create();

    $paginator = $service->index(new Request);

    expect($paginator->total())->toBe(2);
});

it('filters specializations by parent id', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);
    $parent = Specialization::factory()->create();
    Specialization::factory()->count(3)->create(['parent_id' => $parent->id]);

    $paginator = $service->index(new Request(['parent_id' => $parent->id]));

    expect($paginator->total())->toBe(3);
});

it('returns only root specializations excluding a given id', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);
    $first = Specialization::factory()->create();
    $second = Specialization::factory()->create();
    Specialization::factory()->create(['parent_id' => $first->id]);

    $roots = $service->getRootSpecializations(excludeId: $first->id);

    expect($roots)->toHaveCount(1)
        ->and($roots->first()->id)->toBe($second->id);
});

it('prevents deleting a specialization that has children', function (): void {
    /** @var TestCase $this */
    $service = app(SpecializationServiceInterface::class);
    $parent = Specialization::factory()->create();
    Specialization::factory()->create(['parent_id' => $parent->id]);

    expect(fn () => $service->destroy($parent))
        ->toThrow(Exception::class);
});

it('deletes a specialization and its stored icon', function (): void {
    /** @var TestCase $this */
    Storage::fake();
    $service = app(SpecializationServiceInterface::class);

    $request = SpecializationRequest::create('/', 'POST', [
        'translations' => [
            'en' => ['title' => 'Design'],
            'ar' => ['title' => 'تصميم'],
        ],
        'parent_id' => null,
    ], [], [
        'icon' => UploadedFile::fake()->image('icon.png'),
    ]);
    $request->setContainer(app())->validateResolved();

    $specialization = $service->store(StoreSpecializationDTO::fromRequest($request));
    $iconPath = $specialization->icon;

    expect(Storage::exists($iconPath))->toBeTrue();

    $service->destroy($specialization);

    expect(Storage::exists($iconPath))->toBeFalse()
        ->and(Specialization::find($specialization->id))->toBeNull();
});
