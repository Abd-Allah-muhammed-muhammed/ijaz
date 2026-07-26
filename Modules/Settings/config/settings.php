<?php

/**
 * Public API allowlist for Modules\Settings Api\V1\SettingController::settings().
 *
 * The settings singleton caches every key→content pair. The unauthenticated
 * catalog/settings endpoint must NOT dump the full bag — only keys listed here
 * are returned. Today's list matches the historical full public exposure
 * (all SettingsSeeder keys) so this change is non-breaking. Adding a new
 * sensitive setting (fees, bonuses, etc.) stays private until deliberately
 * appended here.
 *
 * @return array{
 *     public_keys: list<string>,
 *     groups: list<string>
 * }
 */
return [
    'public_keys' => [
        'youtube',
        'facebook',
        'whatsapp',
        'x',
        'instagram',
        'tiktok',
        'snapchat',
        'telegram',
        'phone',
        'email',
        'offer_note',
        'guarantee_notes',
        'guarantee_fee',
        'chat_notes',
        'provider_registration_bonus_enabled',
        'provider_registration_bonus_amount',
        'min_withdraw_amount',
    ],

    'groups' => [
        'general',
        'wallet',
        'payment',
        'guarantor',
        'chat',
    ],
];
