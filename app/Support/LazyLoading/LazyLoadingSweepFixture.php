<?php

namespace App\Support\LazyLoading;

use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Bank;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\Models\Specialization;
use Modules\Chat\Models\Conversation;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Cms\Models\Banner;
use Modules\Cms\Models\Page;
use Modules\Cms\Models\Question;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Jobs\Enums\JobTypeEnum;
use Modules\Jobs\Models\JobOffer;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\Models\Order;
use Modules\Payment\Models\Payment;
use Modules\Payout\Models\PayoutRequest;
use Modules\Reviews\Models\Review;
use Modules\Support\Models\TicketSupport;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds a realistic graph of related models so route-parameter substitution
 * exercises nested Resources instead of empty list endpoints.
 *
 * @return array{
 *     admin: Admin,
 *     provider: Provider,
 *     user: User,
 *     parameters: array<string, int|string>
 * }
 */
final class LazyLoadingSweepFixture
{
    public static function seed(): array
    {
        $admin = Admin::query()->create([
            'name' => 'Lazy Sweep Admin',
            'phone' => fake()->unique()->numerify('05########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'language' => 'en',
            'root' => true,
        ]);

        $providerType = ProviderType::query()->create([
            'image' => 'media/sweep-type.png',
            'files' => [
                'id_image' => true,
                'commercial_record' => true,
                'freelancer_certification' => false,
                'iban_certification' => false,
                'license_to_practice_law' => false,
            ],
            'translations' => [
                'en' => ['name' => 'Sweep Provider Type', 'description' => 'Sweep'],
                'ar' => ['name' => 'نوع مزود', 'description' => 'وصف'],
                'ur' => ['name' => 'Sweep PT UR', 'description' => 'Desc UR'],
                'hi' => ['name' => 'Sweep PT HI', 'description' => 'Desc HI'],
            ],
        ]);

        $region = Region::factory()->create();
        $city = City::factory()->create(['region_id' => $region->id]);

        $nationality = Nationality::query()->create([
            'is_active' => true,
            'translations' => [
                'en' => ['name' => 'Saudi Sweep'],
                'ar' => ['name' => 'سعودي'],
                'ur' => ['name' => 'Saudi UR'],
                'hi' => ['name' => 'Saudi HI'],
            ],
        ]);

        $category = Category::factory()->create();
        $skill = Skill::query()->create([
            'category_id' => $category->id,
            'translations' => [
                'en' => ['title' => 'Sweep Skill EN'],
                'ar' => ['title' => 'Sweep Skill AR'],
                'ur' => ['title' => 'Sweep Skill UR'],
                'hi' => ['title' => 'Sweep Skill HI'],
            ],
        ]);

        $provider = Provider::query()->create([
            'name' => 'Sweep Provider Co',
            'iban' => fake()->unique()->iban('SA'),
            'logo' => 'media/sweep-logo.png',
            'provider_type_id' => $providerType->id,
            'region_id' => $region->id,
            'city_id' => $city->id,
            'password' => Hash::make('password'),
            'status' => ProviderStatusEnum::Approved,
            'language' => 'en',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('05########'),
            'about' => 'Sweep fixture provider',
            'address' => 'Riyadh',
        ]);

        $user = User::factory()->create([
            'nationality_id' => $nationality->id,
        ]);

        $propertyCategory = PropertyCategory::factory()->create();
        $propertyType = PropertyType::factory()->create();
        $carCategory = CarCategory::factory()->create();
        $carBrand = CarBrand::factory()->create();
        $carType = CarType::factory()->create(['car_brand_id' => $carBrand->id]);

        $deviceCategory = DeviceCategory::query()->create(['icon' => 'icons/sweep.png']);
        $deviceCategory->translateOrNew('en')->title = 'Sweep Devices';
        $deviceCategory->save();

        $electronicBrand = ElectronicBrand::query()->create([
            'image' => 'brands/sweep.png',
            'is_active' => true,
        ]);
        $electronicBrand->translateOrNew('en')->name = 'Sweep Brand';
        $electronicBrand->save();

        $specialization = Specialization::factory()->create();
        $bank = Bank::factory()->create();

        $page = Page::query()->create([
            'slug' => 'lazy-sweep-about-'.uniqid('', true),
            'translations' => [
                'en' => ['title' => 'About Sweep', 'content' => 'Content EN'],
                'ar' => ['title' => 'عنا', 'content' => 'محتوى'],
                'ur' => ['title' => 'About UR', 'content' => 'Content UR'],
                'hi' => ['title' => 'About HI', 'content' => 'Content HI'],
            ],
        ]);

        $question = Question::query()->create([
            'translations' => [
                'en' => ['title' => 'Sweep Q?', 'answer' => 'Sweep A'],
                'ar' => ['title' => 'سؤال؟', 'answer' => 'جواب'],
                'ur' => ['title' => 'Q UR', 'answer' => 'A UR'],
                'hi' => ['title' => 'Q HI', 'answer' => 'A HI'],
            ],
        ]);

        $banner = Banner::query()->create([
            'link' => 'https://example.com/sweep',
            'image' => 'banners/sweep.png',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
        ]);

        $orderOffer = OrderOfferFactory::new()
            ->forOrder($order)
            ->forProvider($provider)
            ->create();

        Review::query()->create([
            'reviewer_type' => User::class,
            'reviewer_id' => $user->id,
            'reviewee_type' => Provider::class,
            'reviewee_id' => $provider->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
            'rating' => 5,
            'comment' => 'Sweep review',
        ]);

        // Second review so multi-row hydrate stamps preventsLazyLoading without the collector.
        Review::query()->create([
            'reviewer_type' => User::class,
            'reviewer_id' => $user->id,
            'reviewee_type' => Provider::class,
            'reviewee_id' => $provider->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
            'rating' => 4,
            'comment' => 'Sweep review 2',
        ]);

        $opportunity = Opportunity::factory()->create([
            'author_type' => User::class,
            'author_id' => $user->id,
        ]);

        $guarantorRequest = GuarantorRequest::factory()->create([
            'requester_type' => User::class,
            'requester_id' => $user->id,
            'counterparty_type' => Provider::class,
            'counterparty_id' => $provider->id,
        ]);

        $carAdvisement = CarAdvisement::factory()->create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'car_brand_id' => $carBrand->id,
            'car_type_id' => $carType->id,
            'car_category_id' => $carCategory->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'status' => AdvisementStatusEnum::PUBLISHED,
        ]);

        $propertyAdvisement = PropertyAdvisement::factory()->create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'category_id' => $propertyCategory->id,
            'property_type_id' => $propertyType->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'status' => AdvisementStatusEnum::PUBLISHED,
        ]);

        $electronicAdvisement = ElectronicAdvisement::query()->create([
            'title' => 'Sweep Electronic',
            'normalized_title' => 'sweep-electronic',
            'description' => 'A device',
            'normalized_description' => 'a-device',
            'image' => 'media/sweep-electronic.png',
            'status' => AdvisementStatusEnum::PUBLISHED,
            'condition' => ElectronicConditionEnum::NEW,
            'price' => 100,
            'show_price' => true,
            'phone' => '966501234567',
            'user_type' => User::class,
            'user_id' => $user->id,
            'device_category_id' => $deviceCategory->id,
            'electronic_brand_id' => $electronicBrand->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'options' => [],
        ]);

        $instituteAdvisement = InstituteAdvisement::query()->create([
            'title' => 'Sweep Institute',
            'normalized_title' => 'sweep-institute',
            'description' => 'A course',
            'normalized_description' => 'a-course',
            'image' => 'media/sweep-institute.png',
            'status' => AdvisementStatusEnum::PUBLISHED,
            'price' => 200,
            'type' => InstituteTypeEnum::INSTITUTE,
            'study_type' => StudyTypeEnum::ONSITE,
            'study_level' => StudyLevelEnum::CERTIFICATE,
            'phone' => '966501234567',
            'user_type' => User::class,
            'user_id' => $user->id,
            'specialization_id' => $specialization->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'options' => [],
        ]);

        $ticketSupport = TicketSupport::factory()->create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
        ]);

        $withdrawRequest = WithdrawRequest::factory()->for($user, 'user')->create();

        $payment = Payment::factory()->forProduct($order, $user)->create();

        $role = Role::findOrCreate('lazy-sweep-admin-role', 'admin');
        $permission = Permission::findOrCreate('lazy-sweep-permission', 'admin');
        $role->givePermissionTo($permission);

        $installment = GuarantorInstallment::factory()
            ->for($guarantorRequest, 'guarantorRequest')
            ->create();

        $conversation = Conversation::query()->firstOrCreate([
            'operation_type' => Order::class,
            'operation_id' => $order->getKey(),
        ], [
            'user1_type' => $user::class,
            'user1_id' => $user->getKey(),
            'user2_type' => $provider::class,
            'user2_id' => $provider->getKey(),
        ]);

        $jobOffer = JobOffer::query()->create([
            'user_id' => $user->id,
            'user_type' => User::class,
            'title' => 'Sweep Job',
            'description' => 'Sweep job description',
            'expired_at' => now()->addDays(10),
            'contact_number' => '0501234567',
            'city_id' => $city->id,
            'region_id' => $region->id,
            'nationality_id' => $nationality->id,
            'type' => JobTypeEnum::Private,
            'expected_salary' => 3000,
        ]);

        $opportunityOffer = OpportunityOffer::factory()->create([
            'opportunity_id' => $opportunity->id,
            'author_type' => User::class,
            'author_id' => $user->id,
        ]);

        $payoutRequest = PayoutRequest::factory()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\LazySweepNotification',
            'notifiable_type' => $admin->getMorphClass(),
            'notifiable_id' => $admin->getKey(),
            'data' => [
                'title_translated_key' => 'notifications.sweep.title',
                'body_translated_key' => 'notifications.sweep.body',
                'translated_attributes' => [],
            ],
            'read_at' => null,
        ]);

        app(WalletService::class)->credit($user, 1000.0, $user, 'Lazy sweep fund');
        app(WalletService::class)->credit($provider, 1000.0, $provider, 'Lazy sweep fund');

        $parameters = [
            'locale' => 'en',
            'region' => $region->id,
            'city' => $city->id,
            'nationality' => $nationality->id,
            'category' => $category->id,
            'skill' => $skill->id,
            'provider' => $provider->id,
            'providerType' => $providerType->id,
            'provider_type' => $providerType->id,
            'user' => $user->id,
            'admin' => $admin->id,
            'propertyCategory' => $propertyCategory->id,
            'property_category' => $propertyCategory->id,
            'propertyType' => $propertyType->id,
            'property_type' => $propertyType->id,
            'carCategory' => $carCategory->id,
            'car_category' => $carCategory->id,
            'carBrand' => $carBrand->id,
            'car_brand' => $carBrand->id,
            'carType' => $carType->id,
            'car_type' => $carType->id,
            'deviceCategory' => $deviceCategory->id,
            'device_category' => $deviceCategory->id,
            'electronicBrand' => $electronicBrand->id,
            'electronic_brand' => $electronicBrand->id,
            'specialization' => $specialization->id,
            'bank' => $bank->id,
            'page' => $page->id,
            'question' => $question->id,
            'banner' => $banner->id,
            'order' => $order->id,
            'orderOffer' => $orderOffer->id,
            'offer' => $orderOffer->id,
            'opportunity' => $opportunity->id,
            'opportunityOffer' => $opportunityOffer->id,
            'guarantorRequest' => $guarantorRequest->id,
            'installment' => $installment->id,
            'conversation' => $conversation->id,
            'job' => $jobOffer->id,
            'payoutRequest' => $payoutRequest->id,
            'notification' => $notification->id,
            'token' => (string) Str::uuid(),
            'verification' => (string) Str::uuid(),
            'permission' => $permission->id,
            'carAdvisement' => $carAdvisement->id,
            'car_advisement' => $carAdvisement->id,
            'propertyAdvisement' => $propertyAdvisement->id,
            'property_advisement' => $propertyAdvisement->id,
            'electronicAdvisement' => $electronicAdvisement->id,
            'electronic_advisement' => $electronicAdvisement->id,
            'instituteAdvisement' => $instituteAdvisement->id,
            'institute_advisement' => $instituteAdvisement->id,
            'ticketSupport' => $ticketSupport->id,
            'ticket' => $ticketSupport->id,
            'withdrawRequest' => $withdrawRequest->id,
            'withdraw_request' => $withdrawRequest->id,
            'payment' => $payment->id,
            'role' => $role->id,
            'id' => $order->id,
        ];

        return compact('admin', 'provider', 'user', 'parameters');
    }
}
