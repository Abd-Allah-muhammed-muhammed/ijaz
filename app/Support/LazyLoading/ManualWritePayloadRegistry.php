<?php

namespace App\Support\LazyLoading;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Maintained map of write-route payloads for routes the FormRequest introspector
 * cannot satisfy (no FormRequest, cross-field rules, domain objects, etc.).
 *
 * Keys are "METHOD /uri-with-{params}" (route URI template, not resolved).
 *
 * @phpstan-type PayloadFactory callable(array<string, int|string>): array<string, mixed>
 */
final class ManualWritePayloadRegistry
{
    private const SAUDI_IBAN = 'SA0380000000608010167519';

    /**
     * @param  array<string, int|string>  $parameterMap
     * @return array{payload: array<string, mixed>}|null
     */
    public function lookup(Route $route, string $method, array $parameterMap): ?array
    {
        $key = strtoupper($method).' /'.ltrim($route->uri(), '/');
        $map = $this->payloads();

        if (! array_key_exists($key, $map)) {
            return null;
        }

        $definition = $map[$key];
        $payload = is_callable($definition)
            ? $definition($parameterMap)
            : $definition;

        return ['payload' => $payload];
    }

    private function fakeImage(): UploadedFile
    {
        return UploadedFile::fake()->image('sweep.jpg');
    }

    private function fakePdf(): UploadedFile
    {
        return UploadedFile::fake()->create('sweep.pdf', 100, 'application/pdf');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function translations(string $field = 'name'): array
    {
        return [
            'en' => [$field => 'Sweep EN '.uniqid()],
            'ar' => [$field => 'كنس AR '.uniqid()],
            'ur' => [$field => 'Sweep UR '.uniqid()],
            'hi' => [$field => 'Sweep HI '.uniqid()],
        ];
    }

    /**
     * @return array<string, array<string, mixed>|PayloadFactory>
     */
    private function payloads(): array
    {
        $empty = [];
        $chatContent = ['content' => 'Sweep chat message'];
        $iban = self::SAUDI_IBAN;

        return [
            // --- No FormRequest: empty / minimal bodies ---
            'POST /api/v1/guarantor/{guarantorRequest}/pay' => $empty,
            'POST /api/v1/guarantor/{guarantorRequest}/end' => $empty,
            'POST /api/v1/guarantor/{guarantorRequest}/end/approve' => $empty,
            'POST /dashboard/guarantor/{guarantorRequest}/conversation-typing' => $empty,
            'POST /dashboard/logout' => $empty,
            'POST /provider/logout' => $empty,
            'POST /api/v1/user/auth/logout' => $empty,
            'POST /api/v1/user/auth/logout-all' => $empty,
            'POST /dashboard/notifications/mark-all-as-read' => $empty,
            'POST /provider/notifications/mark-all-as-read' => $empty,
            'POST /dashboard/notifications/{notification}/mark-as-read' => $empty,
            'POST /provider/notifications/{notification}/mark-as-read' => $empty,
            'POST /api/v1/opportunities/{opportunity}/resubmit' => $empty,
            'POST /dashboard/orders/{order}/conversation-typing' => $empty,
            'POST /provider/dashboard/orders/{order}/end' => $empty,
            'POST /dashboard/banks/{bank}/restore' => $empty,
            'PATCH /dashboard/banks/{bank}/toggle-active' => $empty,
            'POST /dashboard/support/tickets/{ticket}/typing' => $empty,
            'POST /provider/dashboard/chat/orders/typing/{conversation}' => $empty,
            'POST /otp/register' => $empty,
            'POST /api/broadcasting/auth' => $empty,
            'POST /paymentIPN' => $empty,
            'POST /dashboard/pan-analytics/export' => $empty,

            'PUT /dashboard/property-types/{propertyType}/update-status' => ['is_active' => true],
            'PUT /dashboard/car-brands/{carBrand}/update-status' => ['is_active' => true],
            'PUT /dashboard/car-types/{carType}/update-status' => ['is_active' => true],
            'PUT /dashboard/electronic-brands/{electronic_brand}/update-status' => ['is_active' => true],

            'PUT /dashboard/property-advisements/{property_advisement}' => ['status' => 'published'],
            'PATCH /dashboard/property-advisements/{property_advisement}' => ['status' => 'published'],
            'PUT /dashboard/car-advisements/{car_advisement}' => ['status' => 'published'],
            'PATCH /dashboard/car-advisements/{car_advisement}' => ['status' => 'published'],
            'PUT /dashboard/electronic-advisements/{electronic_advisement}' => ['status' => 'published'],
            'PATCH /dashboard/electronic-advisements/{electronic_advisement}' => ['status' => 'published'],
            'PUT /dashboard/institute-advisements/{institute_advisement}' => ['status' => 'published'],
            'PATCH /dashboard/institute-advisements/{institute_advisement}' => ['status' => 'published'],

            'POST /dashboard/guarantor/{guarantorRequest}/installments/{installment}/release' => $empty,
            'POST /api/v1/opportunities/{opportunity}/offers/{offer}/accept' => $empty,
            'POST /api/v1/opportunities/{opportunity}/offers/{offer}/reject' => $empty,
            'POST /api/v1/user/orders/{order}/{offer}/pay' => $empty,
            'POST /api/v1/user/orders/{order}/{offer}/update-status' => ['status' => 'accepted'],

            'POST /dashboard/categories' => fn (): array => [
                'fees_type' => 'inherited',
                'translations' => $this->translations('title'),
                'icon' => $this->fakeImage(),
            ],
            'PUT /dashboard/categories/{category}' => fn (): array => [
                'fees_type' => 'inherited',
                'translations' => $this->translations('title'),
            ],
            'PATCH /dashboard/categories/{category}' => fn (): array => [
                'fees_type' => 'inherited',
                'translations' => $this->translations('title'),
            ],

            'POST /dashboard/profile' => [
                'name' => 'Sweep Admin Profile',
                'phone' => '0501234567',
                'email' => 'sweep-admin-profile@example.test',
                'address' => 'Riyadh',
                'job' => 'Admin',
            ],

            'POST /api/v1/user/auth/register' => fn (array $p): array => [
                'f_name' => 'Sweep',
                'l_name' => 'User',
                'email' => 'sweep-reg-'.uniqid().'@example.test',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'nationality_id' => $p['nationality'] ?? null,
                'image' => $this->fakeImage(),
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ],

            'POST /payments/testing/{payment}/checkout' => ['status' => 'success'],
            'POST /dashboard/support/tickets/{ticket}' => $chatContent,
            'PUT /dashboard/support/tickets/{ticket}/status' => ['status' => 'open'],

            // --- Complex FormRequest / synthetic failures ---
            'POST /api/v1/chats/guarantor' => fn (array $p): array => [
                'guarantor_request_id' => $p['guarantorRequest'] ?? null,
            ],
            'POST /dashboard/guarantor/{guarantorRequest}/conversation-messages' => $chatContent,
            'POST /dashboard/orders/{order}/conversation-messages' => $chatContent,
            'POST /api/v1/tickets/{ticket}/conversation' => $chatContent,
            'POST /dashboard/support/tickets/{ticket}/send' => $chatContent,
            'POST /api/v1/chats/guarantor/{conversation}/send' => $chatContent,
            'POST /api/v1/chats/opportunities/{conversation}/send' => $chatContent,
            'POST /api/v1/chats/orders/send/{conversation}' => $chatContent,
            'POST /api/v1/chats/send/{conversation}' => $chatContent,
            'POST /api/v1/chats/tickets/send/{conversation}' => $chatContent,
            'POST /provider/dashboard/chat/orders/send/{conversation}' => $chatContent,
            'POST /provider/dashboard/chat/{conversation}/send' => $chatContent,

            'POST /dashboard/roles' => fn (array $p): array => [
                'name' => 'sweep-role-'.uniqid(),
                'permissions' => array_values(array_filter([(int) ($p['permission'] ?? 0)])),
            ],
            'PUT /dashboard/roles/{role}' => fn (array $p): array => [
                'name' => 'sweep-role-upd-'.uniqid(),
                'permissions' => array_values(array_filter([(int) ($p['permission'] ?? 0)])),
            ],
            'PATCH /dashboard/roles/{role}' => fn (array $p): array => [
                'name' => 'sweep-role-upd-'.uniqid(),
                'permissions' => array_values(array_filter([(int) ($p['permission'] ?? 0)])),
            ],

            'POST /dashboard/admins' => fn (array $p): array => [
                'name' => 'Sweep Admin '.uniqid(),
                'email' => 'sweep-admin-'.uniqid().'@example.test',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'image' => $this->fakeImage(),
                'roles' => [(int) ($p['role'] ?? 0)],
                'language' => 'en',
            ],
            'PUT /dashboard/admins/{admin}' => fn (array $p): array => [
                'name' => 'Sweep Admin Upd',
                'email' => 'sweep-admin-upd-'.uniqid().'@example.test',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'roles' => [(int) ($p['role'] ?? 0)],
                'language' => 'en',
            ],
            'PATCH /dashboard/admins/{admin}' => fn (array $p): array => [
                'name' => 'Sweep Admin Upd',
                'email' => 'sweep-admin-upd-'.uniqid().'@example.test',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'roles' => [(int) ($p['role'] ?? 0)],
                'language' => 'en',
            ],

            'POST /dashboard/providers' => fn (array $p): array => [
                'name' => 'Sweep Provider '.uniqid(),
                'provider_type_id' => $p['provider_type'] ?? $p['providerType'] ?? null,
                'region_id' => $p['region'] ?? null,
                'city_id' => $p['city'] ?? null,
                'address' => 'Riyadh',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => 'sweep-prov-'.uniqid().'@example.test',
                'iban' => $iban,
                'about' => 'Sweep about',
                'logo' => $this->fakeImage(),
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'categories' => [[
                    'id' => $p['category'] ?? null,
                    'skills' => array_values(array_filter([(int) ($p['skill'] ?? 0)])),
                ]],
                'id_image' => $this->fakePdf(),
                'commercial_record' => $this->fakePdf(),
            ],
            'PUT /dashboard/providers/{provider}' => fn (array $p): array => [
                'name' => 'Sweep Provider Upd',
                'provider_type_id' => $p['provider_type'] ?? $p['providerType'] ?? null,
                'region_id' => $p['region'] ?? null,
                'city_id' => $p['city'] ?? null,
                'address' => 'Riyadh',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => 'sweep-prov-upd-'.uniqid().'@example.test',
                'iban' => $iban,
                'about' => 'Sweep about',
                'categories' => [[
                    'id' => $p['category'] ?? null,
                    'skills' => array_values(array_filter([(int) ($p['skill'] ?? 0)])),
                ]],
            ],
            'PATCH /dashboard/providers/{provider}' => fn (array $p): array => [
                'name' => 'Sweep Provider Upd',
                'provider_type_id' => $p['provider_type'] ?? $p['providerType'] ?? null,
                'region_id' => $p['region'] ?? null,
                'city_id' => $p['city'] ?? null,
                'address' => 'Riyadh',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => 'sweep-prov-upd-'.uniqid().'@example.test',
                'iban' => $iban,
                'about' => 'Sweep about',
                'categories' => [[
                    'id' => $p['category'] ?? null,
                    'skills' => array_values(array_filter([(int) ($p['skill'] ?? 0)])),
                ]],
            ],

            'POST /dashboard/users' => fn (array $p): array => [
                'name' => 'Sweep User '.uniqid(),
                'email' => 'sweep-user-'.uniqid().'@example.test',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'image' => $this->fakeImage(),
                'nationality_id' => $p['nationality'] ?? null,
                'region_id' => $p['region'] ?? null,
                'city_id' => $p['city'] ?? null,
            ],

            'POST /provider/profile' => fn (array $p): array => [
                'name' => 'Sweep Provider Profile',
                'iban' => $iban,
                'logo' => $this->fakeImage(),
                'about' => 'About',
                'address' => 'Riyadh',
                'region_id' => $p['region'] ?? null,
                'city_id' => $p['city'] ?? null,
                'categories' => [[
                    'id' => $p['category'] ?? null,
                    'skills' => array_values(array_filter([(int) ($p['skill'] ?? 0)])),
                ]],
            ],
            'POST /provider/deactivate' => ['confirmed' => true],

            'POST /api/v1/otp/verify' => fn (array $p): array => [
                'verification_id' => $p['verification'] ?? (string) Str::uuid(),
                'code' => '1234',
            ],
            'POST /api/v1/otp/resend' => fn (array $p): array => [
                'verification_id' => $p['verification'] ?? (string) Str::uuid(),
            ],

            'POST /api/v1/user/auth/profile/update' => [
                'name' => 'Sweep User Profile',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ],

            'POST /dashboard/property-types' => fn (): array => [
                'translations' => $this->translations('name'),
                'is_active' => true,
            ],
            'POST /dashboard/electronic-brands' => fn (): array => [
                'translations' => $this->translations('name'),
                'image' => $this->fakeImage(),
                'is_active' => true,
            ],
            'POST /dashboard/banners' => fn (): array => [
                'link' => 'https://example.com/sweep',
                'image' => $this->fakeImage(),
            ],
            'POST /dashboard/provider-types' => fn (array $p): array => [
                'image' => $this->fakeImage(),
                'translations' => $this->translations('name'),
                'categories' => [(int) ($p['category'] ?? 0)],
                'files' => [
                    'id_image' => true,
                    'commercial_record' => true,
                    'freelancer_certification' => false,
                    'iban_certification' => false,
                    'license_to_practice_law' => false,
                ],
            ],
            'PUT /dashboard/provider-types/{provider_type}' => fn (array $p): array => [
                'translations' => $this->translations('name'),
                'categories' => [(int) ($p['category'] ?? 0)],
                'files' => [
                    'id_image' => true,
                    'commercial_record' => true,
                    'freelancer_certification' => false,
                    'iban_certification' => false,
                    'license_to_practice_law' => false,
                ],
            ],
            'PATCH /dashboard/provider-types/{provider_type}' => fn (array $p): array => [
                'translations' => $this->translations('name'),
                'categories' => [(int) ($p['category'] ?? 0)],
                'files' => [
                    'id_image' => true,
                    'commercial_record' => true,
                    'freelancer_certification' => false,
                    'iban_certification' => false,
                    'license_to_practice_law' => false,
                ],
            ],
            'PUT /dashboard/settings' => [
                'group' => 'wallet',
                'values' => ['min_withdraw_amount' => '200'],
            ],
            'POST /pan/events' => [
                'events' => [['name' => 'lazy-sweep', 'type' => 'click']],
            ],
            'POST /provider/dashboard/chat' => fn (array $p): array => [
                'socket_id' => 'user-'.($p['user'] ?? 1),
                'provider_id' => $p['provider'] ?? null,
            ],
            'POST /api/v1/chats' => fn (array $p): array => [
                'socket_id' => 'user-'.($p['user'] ?? 1),
                'provider_id' => $p['provider'] ?? null,
            ],

            'POST /api/v1/jobs' => fn (array $p): array => [
                'title' => 'Sweep Job '.uniqid(),
                'description' => 'Sweep job description',
                'expected_salary' => 3000,
                'expired_at' => now()->addDays(10)->toDateString(),
                'contact_number' => '0501234567',
                'city_id' => $p['city'] ?? null,
                'region_id' => $p['region'] ?? null,
                'nationality_id' => $p['nationality'] ?? null,
                'type' => 2,
            ],
            'PUT /api/v1/jobs/{job}' => fn (array $p): array => [
                'title' => 'Sweep Job Upd',
                'description' => 'Sweep job description',
                'expected_salary' => 3000,
                'expired_at' => now()->addDays(10)->toDateString(),
                'contact_number' => '0501234567',
                'city_id' => $p['city'] ?? null,
                'region_id' => $p['region'] ?? null,
                'nationality_id' => $p['nationality'] ?? null,
                'type' => 2,
            ],
            'PATCH /api/v1/jobs/{job}' => fn (array $p): array => [
                'title' => 'Sweep Job Upd',
                'description' => 'Sweep job description',
                'expected_salary' => 3000,
                'expired_at' => now()->addDays(10)->toDateString(),
                'contact_number' => '0501234567',
                'city_id' => $p['city'] ?? null,
                'region_id' => $p['region'] ?? null,
                'nationality_id' => $p['nationality'] ?? null,
                'type' => 2,
            ],

            'POST /api/v1/classifieds/cars' => fn (array $p): array => [
                'title' => 'Sweep Car '.uniqid(),
                'description' => 'A car',
                'price' => 10000,
                'show_price' => true,
                'phone' => '0501234567',
                'year' => (int) date('Y'),
                'car_brand_id' => $p['car_brand'] ?? $p['carBrand'] ?? null,
                'car_type_id' => $p['car_type'] ?? $p['carType'] ?? null,
                'car_category_id' => $p['car_category'] ?? $p['carCategory'] ?? null,
                'city_id' => $p['city'] ?? null,
                'region_id' => $p['region'] ?? null,
                'image' => $this->fakeImage(),
            ],
            'PUT /api/v1/classifieds/cars/{car_advisement}' => fn (array $p): array => [
                'title' => 'Sweep Car Upd',
                'description' => 'A car',
                'price' => 10000,
                'show_price' => true,
                'phone' => '0501234567',
                'year' => (int) date('Y'),
                'car_brand_id' => $p['car_brand'] ?? $p['carBrand'] ?? null,
                'car_type_id' => $p['car_type'] ?? $p['carType'] ?? null,
                'car_category_id' => $p['car_category'] ?? $p['carCategory'] ?? null,
                'city_id' => $p['city'] ?? null,
                'region_id' => $p['region'] ?? null,
            ],
            'PATCH /api/v1/classifieds/cars/{car_advisement}' => fn (array $p): array => [
                'title' => 'Sweep Car Upd',
                'description' => 'A car',
                'price' => 10000,
                'show_price' => true,
                'phone' => '0501234567',
                'year' => (int) date('Y'),
                'car_brand_id' => $p['car_brand'] ?? $p['carBrand'] ?? null,
                'car_type_id' => $p['car_type'] ?? $p['carType'] ?? null,
                'car_category_id' => $p['car_category'] ?? $p['carCategory'] ?? null,
                'city_id' => $p['city'] ?? null,
                'region_id' => $p['region'] ?? null,
            ],

            'POST /provider/dashboard/withdraw-requests' => ['amount' => 200],
            'POST /api/v1/wallet/withdraw' => ['amount' => 200],

            'POST /register' => fn (array $p): array => [
                'name' => 'Sweep Reg Provider',
                'phone' => '5'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => 'sweep-reg-'.uniqid().'@example.test',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'iban' => $iban,
                'provider_type_id' => $p['provider_type'] ?? $p['providerType'] ?? null,
                'region_id' => $p['region'] ?? null,
                'city_id' => $p['city'] ?? null,
                'address' => 'Riyadh',
                'about' => 'About',
                'logo' => $this->fakeImage(),
                'otp' => '1234',
                'id_image' => $this->fakePdf(),
                'commercial_record' => $this->fakePdf(),
            ],

            'POST /provider/register/uploads/{token}' => fn (): array => [
                'field' => 'id_image',
                'file' => $this->fakeImage(),
            ],

            'POST /api/v1/guarantor/individual' => fn (array $p): array => [
                'user_id' => $p['provider'] ?? null,
                'user_type' => 'provider',
                'amount' => 1000,
                'notes' => 'Sweep individual guarantor',
            ],
            'POST /api/v1/guarantor/company' => fn (array $p): array => [
                'user_id' => $p['provider'] ?? null,
                'user_type' => 'provider',
                'amount' => 5000,
                'requester_iban' => $iban,
                'requester_bank_id' => $p['bank'] ?? null,
                'installments' => [
                    ['amount' => 2500, 'due_date' => now()->addMonth()->toDateString()],
                    ['amount' => 2500, 'due_date' => now()->addMonths(2)->toDateString()],
                ],
                'contracts' => [$this->fakePdf()],
                'notes' => 'Sweep company guarantor',
            ],
        ];
    }
}
