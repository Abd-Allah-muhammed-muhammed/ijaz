<?php

/**
 * Admin profile dropdown UI copy is client-rendered via i18next `t()`, so we assert:
 * 1) HeaderUserMenu.tsx wires the same t() keys as the working Language label, and
 * 2) those keys resolve to real translations for every supported locale.
 *
 * @return array{locale: string, profile: string, logout: string}
 */
dataset('admin_profile_dropdown_label_locales', [
    'ar' => ['ar', 'الملف الشخصي', 'تسجيل الخروج'],
    'hi' => ['hi', 'प्रोफ़ाइल', 'लॉग आउट'],
    'ur' => ['ur', 'پروفائل', 'لاگ آؤٹ'],
    'en' => ['en', 'Profile', 'Logout'],
]);

test('admin profile dropdown renders "My Profile" and "Sign Out" in the correct translated language for ar/hi/ur/en locales, not hardcoded English', function (string $locale, string $profile, string $logout): void {
    $source = file_get_contents(resource_path('js/vendor/metronic/partials/layout/header-menus/HeaderUserMenu.tsx'));

    expect($source)->not->toBeFalse();

    // Must follow the same t() pattern as Languages.tsx (Language label) — not plain English JSX.
    expect($source)
        ->toContain("t('profile')")
        ->toContain("t('logout')")
        ->toContain('useTranslation')
        ->not->toContain('My Profile')
        ->not->toContain('Sign Out');

    expect(__('profile', [], $locale))->toBe($profile)
        ->and(__('logout', [], $locale))->toBe($logout);

    if ($locale !== 'en') {
        expect(__('profile', [], $locale))->not->toBe('My Profile')
            ->and(__('logout', [], $locale))->not->toBe('Sign Out')
            ->and(__('profile', [], $locale))->not->toBe('Profile')
            ->and(__('logout', [], $locale))->not->toBe('Logout');
    }
})->with('admin_profile_dropdown_label_locales');
