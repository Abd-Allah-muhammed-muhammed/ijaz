<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Enums\SettingGroupEnum;
use Modules\Settings\Enums\SettingTypeEnum;
use Modules\Settings\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * PLACEHOLDER — gateway fee amount (SAR) applied by CalculateOrderFeesAction.
     * Not a real business rate: set the live value per driver in the Settings
     * dashboard (Payment tab) before launch. firstOrCreate preserves any value
     * already configured in an environment.
     */
    private const GATEWAY_FEE_PLACEHOLDER = '0';

    /**
     * Run the database seeds.
     *
     * is_public mirrors the former config/settings.php public_keys allowlist
     * (every seeded key was public).
     *
     * type defaults to text via the column default; only long-form notes set
     * textarea explicitly so updateOrCreate backfills existing rows.
     *
     * section is nullable — only General contact/social keys set it so the
     * dashboard can group without dropping uncategorized/future keys.
     *
     * Payment gateway fees: one `{driver}_fees` row per key in
     * config('payment.gateways') — same source PaymentService::resolveGateway()
     * uses — via firstOrCreate so re-seeds never clobber configured amounts.
     */
    public function run(): void
    {
        $settings = collect([
            [
                'key' => 'youtube',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'social',
                'is_public' => true,
            ],
            [
                'key' => 'facebook',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'social',
                'is_public' => true,
            ],
            [
                'key' => 'whatsapp',
                'content' => '966500000000',
                'group' => SettingGroupEnum::General,
                'section' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'x',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'social',
                'is_public' => true,
            ],
            [
                'key' => 'instagram',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'social',
                'is_public' => true,
            ],
            [
                'key' => 'tiktok',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'social',
                'is_public' => true,
            ],
            [
                'key' => 'snapchat',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'social',
                'is_public' => true,
            ],
            [
                'key' => 'telegram',
                'content' => '',
                'group' => SettingGroupEnum::General,
                'section' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'phone',
                'content' => '966500000000',
                'group' => SettingGroupEnum::General,
                'section' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'email',
                'content' => 'info@ijaz.sa',
                'group' => SettingGroupEnum::General,
                'section' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'offer_note',
                'content' => 'Special offer: Get a 10% discount on your first service booking! Use code FIRST10 at checkout.',
                'group' => SettingGroupEnum::General,
                'is_public' => true,
                'type' => SettingTypeEnum::Textarea,
            ],
            [
                'key' => 'guarantee_notes',
                'content' => 'The guarantee fee is a refundable amount held to ensure the successful completion of the service. It is returned to the user upon satisfactory completion of the service as per the agreed terms.',
                'group' => SettingGroupEnum::Guarantor,
                'is_public' => true,
                'type' => SettingTypeEnum::Textarea,
            ],
            [
                'key' => 'guarantee_fee_percent',
                'content' => '2.5',
                'group' => SettingGroupEnum::Guarantor,
                'is_public' => true,
            ],
            [
                'key' => 'chat_notes',
                'content' => 'Please be respectful and professional in your communication. Avoid sharing personal information and adhere to our community guidelines.',
                'group' => SettingGroupEnum::Chat,
                'is_public' => true,
                'type' => SettingTypeEnum::Textarea,
            ],
            [
                'key' => 'provider_registration_bonus_enabled',
                'content' => '1',
                'group' => SettingGroupEnum::Wallet,
                'is_public' => true,
            ],
            [
                'key' => 'provider_registration_bonus_amount',
                'content' => '50',
                'group' => SettingGroupEnum::Wallet,
                'is_public' => true,
            ],
            [
                'key' => 'min_withdraw_amount',
                'content' => '200',
                'group' => SettingGroupEnum::Wallet,
                'is_public' => true,
            ],
            [
                'key' => 'order_dispute_window_hours',
                'content' => '48',
                'group' => SettingGroupEnum::Wallet,
                'is_public' => true,
            ],
            [
                'key' => 'guarantor_first_installment_max_days',
                'content' => '5',
                'group' => SettingGroupEnum::Guarantor,
                'is_public' => false,
            ],
        ]);

        $settings->each(function (array $setting): void {
            $attributes = [
                'content' => $setting['content'],
                'group' => $setting['group'],
                'is_public' => $setting['is_public'],
            ];

            if (array_key_exists('type', $setting)) {
                $attributes['type'] = $setting['type'];
            }

            if (array_key_exists('section', $setting)) {
                $attributes['section'] = $setting['section'];
            }

            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $attributes,
            );
        });

        foreach (array_keys(config('payment.gateways', [])) as $driver) {
            Setting::query()->firstOrCreate(
                ['key' => "{$driver}_fees"],
                [
                    // PLACEHOLDER — replace with the live gateway fee (SAR) before launch.
                    'content' => self::GATEWAY_FEE_PLACEHOLDER,
                    'group' => SettingGroupEnum::Payment,
                    'is_public' => false,
                ],
            );
        }

        cache()->forget('settings');
        app()->forgetInstance('settings');
    }
}
