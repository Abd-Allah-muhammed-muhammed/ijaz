<?php

use Illuminate\Http\Request;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\Models\Specialization;
use Modules\Catalog\QueryFilters\CarBrand\CarBrandFilters;
use Modules\Catalog\QueryFilters\CarCategory\CarCategoryFilters;
use Modules\Catalog\QueryFilters\CarType\CarTypeFilters;
use Modules\Catalog\QueryFilters\DeviceCategory\DeviceCategoryFilters;
use Modules\Catalog\QueryFilters\ElectronicBrand\ElectronicBrandFilters;
use Modules\Catalog\QueryFilters\Filters\ParentFilter;
use Modules\Catalog\QueryFilters\Filters\TranslationSearchFilter;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;
use Modules\Catalog\QueryFilters\Specialization\SpecializationFilters;
use Tests\TestCase;

beforeEach(function (): void {
    app()->setLocale('ar');
});

it('matches arabic diacritic and hamza variants on normalized_title domains', function (string $domain): void {
    /** @var TestCase $this */
    $storedTitle = 'أَحْمَد للتطوير';
    $searchVariant = 'احمد للتطوير';

    $model = match ($domain) {
        'specialization' => tap(Specialization::create(['parent_id' => null, 'icon' => null]), function (Specialization $item) use ($storedTitle): void {
            $item->translations()->create(['locale' => 'ar', 'title' => $storedTitle]);
            $item->translations()->create(['locale' => 'en', 'title' => 'Ahmed Dev Unique']);
        }),
        'car_category' => tap(CarCategory::create(['parent_id' => null, 'icon' => null]), function (CarCategory $item) use ($storedTitle): void {
            $item->translations()->create(['locale' => 'ar', 'title' => $storedTitle]);
            $item->translations()->create(['locale' => 'en', 'title' => 'Ahmed Dev Unique']);
        }),
        'device_category' => tap(DeviceCategory::create(['parent_id' => null, 'icon' => null]), function (DeviceCategory $item) use ($storedTitle): void {
            $item->translations()->create(['locale' => 'ar', 'title' => $storedTitle]);
            $item->translations()->create(['locale' => 'en', 'title' => 'Ahmed Dev Unique']);
        }),
    };

    $noise = match ($domain) {
        'specialization' => Specialization::factory()->create(),
        'car_category' => CarCategory::factory()->create(),
        'device_category' => tap(DeviceCategory::create(['parent_id' => null, 'icon' => null]), function (DeviceCategory $item): void {
            $item->translations()->create(['locale' => 'en', 'title' => 'Other Device']);
            $item->translations()->create(['locale' => 'ar', 'title' => 'فئة أخرى']);
        }),
    };

    $filters = match ($domain) {
        'specialization' => new SpecializationFilters(new Request(['search' => $searchVariant])),
        'car_category' => new CarCategoryFilters(new Request(['search' => $searchVariant])),
        'device_category' => new DeviceCategoryFilters(new Request(['search' => $searchVariant])),
    };

    $query = match ($domain) {
        'specialization' => Specialization::query(),
        'car_category' => CarCategory::query(),
        'device_category' => DeviceCategory::query(),
    };

    $ids = $filters->apply($query)->pluck('id');

    expect($ids)->toContain($model->id)
        ->and($ids)->not->toContain($noise->id);
})->with([
    'specialization',
    'car_category',
    'device_category',
]);

it('matches arabic diacritic variants on electronic brand normalized_name', function (): void {
    /** @var TestCase $this */
    $brand = ElectronicBrand::create(['image' => null, 'is_active' => true]);
    $brand->translations()->create(['locale' => 'ar', 'name' => 'أَحْمَد إلكترونيات']);
    $brand->translations()->create(['locale' => 'en', 'name' => 'Ahmed Electronics Unique']);

    $other = ElectronicBrand::create(['image' => null, 'is_active' => true]);
    $other->translations()->create(['locale' => 'en', 'name' => 'Other Brand']);
    $other->translations()->create(['locale' => 'ar', 'name' => 'علامة أخرى']);

    $filters = new ElectronicBrandFilters(new Request(['search' => 'احمد الكترونيات']));
    $ids = $filters->apply(ElectronicBrand::query())->pluck('id');

    expect($ids)->toContain($brand->id)
        ->and($ids)->not->toContain($other->id);
});

it('searches car brand car type and property type on raw name without normalization', function (string $domain): void {
    /** @var TestCase $this */
    $needle = 'ZxqUniqueBrandName';

    $model = match ($domain) {
        'car_brand' => tap(CarBrand::factory()->create(), function (CarBrand $item) use ($needle): void {
            $item->translations()->where('locale', 'en')->update(['name' => $needle]);
        }),
        'car_type' => tap(CarType::factory()->create(), function (CarType $item) use ($needle): void {
            $item->translations()->where('locale', 'en')->update(['name' => $needle]);
        }),
        'property_type' => tap(PropertyType::factory()->create(), function (PropertyType $item) use ($needle): void {
            $item->translations()->where('locale', 'en')->update(['name' => $needle]);
        }),
    };

    $noise = match ($domain) {
        'car_brand' => CarBrand::factory()->create(),
        'car_type' => CarType::factory()->create(),
        'property_type' => PropertyType::factory()->create(),
    };

    $filters = match ($domain) {
        'car_brand' => new CarBrandFilters(new Request(['search' => 'ZxqUnique'])),
        'car_type' => new CarTypeFilters(new Request(['search' => 'ZxqUnique'])),
        'property_type' => new PropertyTypeFilters(new Request(['search' => 'ZxqUnique'])),
    };

    $query = match ($domain) {
        'car_brand' => CarBrand::query(),
        'car_type' => CarType::query(),
        'property_type' => PropertyType::query(),
    };

    $ids = $filters->apply($query)->pluck('id');

    expect($ids)->toContain($model->id)
        ->and($ids)->not->toContain($noise->id);
})->with([
    'car_brand',
    'car_type',
    'property_type',
]);

it('does not normalize raw name search so arabic diacritic variants miss on car brand', function (): void {
    /** @var TestCase $this */
    $brand = CarBrand::factory()->create();
    $brand->translations()->create(['locale' => 'ar', 'name' => 'أَحْمَد موتورز']);

    $filters = new CarBrandFilters(new Request(['search' => 'احمد موتورز']));
    $ids = $filters->apply(CarBrand::query())->pluck('id');

    // Behavior-preserving: Style C has no Normalize — folded search must not match stored diacritics.
    expect($ids)->not->toContain($brand->id);
});

it('shared parent filter scopes by parent_id or roots', function (): void {
    /** @var TestCase $this */
    $parent = Specialization::factory()->create();
    $child = Specialization::factory()->create(['parent_id' => $parent->id]);
    $root = Specialization::factory()->create();

    $children = (new ParentFilter($parent->id))->apply(Specialization::query())->pluck('id');
    $roots = (new ParentFilter(null))->apply(Specialization::query())->pluck('id');

    expect($children)->toContain($child->id)
        ->and($children)->not->toContain($root->id)
        ->and($roots)->toContain($parent->id)
        ->and($roots)->toContain($root->id)
        ->and($roots)->not->toContain($child->id);
});

it('translation search filter skips empty search terms', function (): void {
    /** @var TestCase $this */
    Specialization::factory()->create();

    $before = Specialization::query()->toSql();
    $after = (new TranslationSearchFilter(null, 'normalized_title'))
        ->apply(Specialization::query())
        ->toSql();

    expect($after)->toBe($before);
});
