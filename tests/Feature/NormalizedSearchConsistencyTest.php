<?php

use App\Support\TranslationSearch;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\ElectronicBrand\ListElectronicBrandsForSelectAction;
use Modules\Catalog\Actions\PropertyCategory\ListPropertyCategoriesForSelectAction;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\Repositories\PropertyCategoryRepository;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\Repositories\PropertyAdvisementRepository;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;
use Modules\Marketplace\Repositories\CategoryRepository;
use Modules\Marketplace\Repositories\SkillRepository;

beforeEach(function (): void {
    app()->setLocale('en');
});

test('regions dashboard and select/api search both match partial normalized titles', function (): void {
    $matching = app(RegionRepositoryInterface::class)->create([
        'en' => ['title' => 'Northern Frontier'],
        'ar' => ['title' => 'الحدود الشمالية'],
    ]);
    app(RegionRepositoryInterface::class)->create([
        'en' => ['title' => 'Eastern Province'],
        'ar' => ['title' => 'المنطقة الشرقية'],
    ]);

    $dashboard = app(RegionRepositoryInterface::class)->paginate(
        Request::create('/', 'GET', ['search' => 'no'])
    );
    $select = app(RegionRepositoryInterface::class)->listForSelect('no');
    $api = app(RegionRepositoryInterface::class)->paginateForApi('no');

    expect($dashboard->pluck('id'))->toContain($matching->id)
        ->and($select->pluck('id'))->toContain($matching->id)
        ->and($api->pluck('id'))->toContain($matching->id);
});

test('cities dashboard and select/api search both match partial normalized titles', function (): void {
    $region = Region::factory()->create();
    $matching = app(CityRepositoryInterface::class)->create($region->id, [
        'en' => ['title' => 'Northern City'],
        'ar' => ['title' => 'مدينة شمالية'],
    ]);
    app(CityRepositoryInterface::class)->create($region->id, [
        'en' => ['title' => 'Southern City'],
        'ar' => ['title' => 'مدينة جنوبية'],
    ]);

    $dashboard = app(CityRepositoryInterface::class)->paginate(
        Request::create('/', 'GET', ['search' => 'no'])
    );
    $select = app(CityRepositoryInterface::class)->listForSelect('no', $region->id);
    $api = app(CityRepositoryInterface::class)->paginateForApiByRegion($region, 'no');

    expect($dashboard->pluck('id'))->toContain($matching->id)
        ->and($select->pluck('id'))->toContain($matching->id)
        ->and($api->pluck('id'))->toContain($matching->id);
});

test('nationalities dashboard and select/api search both match partial normalized names', function (): void {
    $matching = app(NationalityRepositoryInterface::class)->create([
        'en' => ['name' => 'Northernese'],
        'ar' => ['name' => 'شمالي'],
    ]);
    app(NationalityRepositoryInterface::class)->create([
        'en' => ['name' => 'Southernese'],
        'ar' => ['name' => 'جنوبي'],
    ]);

    $dashboard = app(NationalityRepositoryInterface::class)->paginate(
        Request::create('/', 'GET', ['search' => 'no'])
    );
    $select = app(NationalityRepositoryInterface::class)->listForSelect('no');
    $api = app(NationalityRepositoryInterface::class)->paginateForApi('no');

    expect($dashboard->pluck('id'))->toContain($matching->id)
        ->and($select->pluck('id'))->toContain($matching->id)
        ->and($api->pluck('id'))->toContain($matching->id);
});

test('marketplace categories dashboard and api/ajax search both match partial normalized titles', function (): void {
    $matching = Category::query()->create([
        'icon' => '🏠',
        'fees' => 5,
        'fees_type' => 'percentage',
        'translations' => [
            'en' => ['title' => 'Northern Cleaning', 'description' => 'desc'],
            'ar' => ['title' => 'تنظيف شمالي', 'description' => 'وصف'],
        ],
    ]);
    Category::query()->create([
        'icon' => '🧹',
        'fees' => 5,
        'fees_type' => 'percentage',
        'translations' => [
            'en' => ['title' => 'Southern Cleaning', 'description' => 'desc'],
            'ar' => ['title' => 'تنظيف جنوبي', 'description' => 'وصف'],
        ],
    ]);

    $repo = app(CategoryRepository::class);
    $dashboard = $repo->paginateForDashboard(Request::create('/', 'GET', ['search' => 'no']));
    $api = $repo->paginateForApi(Request::create('/', 'GET', ['search' => 'no']));
    $ajax = $repo->listForAjax('no');

    expect($dashboard->pluck('id'))->toContain($matching->id)
        ->and($api->pluck('id'))->toContain($matching->id)
        ->and($ajax->pluck('id'))->toContain($matching->id);
});

test('marketplace skills dashboard and api search both match partial normalized titles', function (): void {
    $category = Category::query()->create([
        'icon' => '🏠',
        'fees' => 5,
        'fees_type' => 'percentage',
        'translations' => [
            'en' => ['title' => 'Parent Cat', 'description' => 'd'],
            'ar' => ['title' => 'أصل', 'description' => 'د'],
        ],
    ]);
    $matching = Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Northern Skill'],
            'ar' => ['title' => 'مهارة شمالية'],
        ],
    ]);
    Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Southern Skill'],
            'ar' => ['title' => 'مهارة جنوبية'],
        ],
    ]);

    $repo = app(SkillRepository::class);
    $dashboard = $repo->paginateForDashboard(Request::create('/', 'GET', ['search' => 'no']));
    $api = $repo->paginateForApi(Request::create('/', 'GET', ['search' => 'no']));

    expect($dashboard->pluck('id'))->toContain($matching->id)
        ->and($api->pluck('id'))->toContain($matching->id);
});

test('property categories dashboard and select search both match partial normalized titles', function (): void {
    $matching = PropertyCategory::query()->create(['is_active' => true]);
    $matching->translations()->create(['locale' => 'en', 'title' => 'Northern Homes']);
    $other = PropertyCategory::query()->create(['is_active' => true]);
    $other->translations()->create(['locale' => 'en', 'title' => 'Southern Homes']);

    $dashboard = app(PropertyCategoryRepository::class)->paginateForDashboard(
        Request::create('/', 'GET', ['search' => 'no'])
    );
    $select = app(ListPropertyCategoriesForSelectAction::class)->handle('no');

    expect($dashboard->pluck('id'))->toContain($matching->id)
        ->and($select->pluck('id'))->toContain($matching->id);
});

test('electronic brands select search matches partial normalized names', function (): void {
    $matching = ElectronicBrand::query()->create(['is_active' => true]);
    $matching->translations()->create(['locale' => 'en', 'name' => 'NorthernTech']);
    $other = ElectronicBrand::query()->create(['is_active' => true]);
    $other->translations()->create(['locale' => 'en', 'name' => 'SouthernTech']);

    $select = app(ListElectronicBrandsForSelectAction::class)->handle('no');

    expect($select->pluck('id'))->toContain($matching->id)
        ->and($select->pluck('id'))->not->toContain($other->id);
});

test('property advisements dashboard search matches normalized title with Arabic-aware term', function (): void {
    $advisement = PropertyAdvisement::factory()->create([
        'title' => 'Northern Luxury Villa',
        'description' => 'A northern villa description',
    ]);

    expect($advisement->fresh()->normalized_title)->toBe(
        TranslationSearch::term('Northern Luxury Villa')
    );

    $results = app(PropertyAdvisementRepository::class)->paginateForDashboard(
        Request::create('/', 'GET', ['search' => 'no'])
    );

    expect($results->pluck('id'))->toContain($advisement->id);
});

test('eloquent creates populate normalized_* for geo and marketplace translations', function (): void {
    $region = Region::query()->create([
        'translations' => ['en' => ['title' => 'Probe Region']],
    ]);
    $nationality = Nationality::query()->create([
        'code' => 'PR',
        'icon' => '🏳️',
        'is_active' => true,
        'translations' => ['en' => ['name' => 'Probe Nation']],
    ]);
    $category = Category::query()->create([
        'icon' => 'x',
        'fees' => 1,
        'fees_type' => 'fixed',
        'translations' => ['en' => ['title' => 'Probe Category', 'description' => 'd']],
    ]);

    expect($region->translate('en')->normalized_title)->toBe(TranslationSearch::term('Probe Region'))
        ->and($nationality->translate('en')->normalized_name)->toBe(TranslationSearch::term('Probe Nation'))
        ->and($category->translate('en')->normalized_title)->toBe(TranslationSearch::term('Probe Category'));
});
