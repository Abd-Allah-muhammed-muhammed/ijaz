<?php

/**
 * Admin login UI copy is client-rendered via i18next `t()`, so we assert:
 * 1) Login.tsx wires the working keys (not hardcoded English JSX), and
 * 2) those keys resolve to real translations for every supported locale.
 *
 * @return array{locale: string, sign_in: string, email: string, password: string}
 */
dataset('admin_login_page_label_locales', [
    'ar' => ['ar', 'تسجيل الدخول', 'البريد الإلكتروني', 'كلمة المرور'],
    'hi' => ['hi', 'साइन इन', 'ईमेल', 'पासवर्ड'],
    'ur' => ['ur', 'سائن اِن', 'ای میل', 'پاس ورڈ'],
    'en' => ['en', 'Sign In', 'Email', 'Password'],
]);

test('admin login page renders header, Email label, Password label, and Email placeholder in the correct translated language for ar/hi/ur/en routes, not hardcoded English', function (string $locale, string $signIn, string $email, string $password): void {
    $source = file_get_contents(resource_path('js/apps/admin/pages/Auth/components/Login.tsx'));

    expect($source)->not->toBeFalse();

    // Must follow the same t() pattern as the working password placeholder — not plain English JSX.
    expect($source)
        ->toContain("t('sign_in')")
        ->toContain("t('email')")
        ->toContain("t('password')")
        ->not->toContain('>Sign In<')
        ->not->toContain('>Email</label>')
        ->not->toContain('>Password</label>')
        ->not->toContain("placeholder='Email'")
        ->not->toContain('placeholder="Email"');

    expect(__('sign_in', [], $locale))->toBe($signIn)
        ->and(__('email', [], $locale))->toBe($email)
        ->and(__('password', [], $locale))->toBe($password);

    if ($locale !== 'en') {
        expect(__('sign_in', [], $locale))->not->toBe('Sign In')
            ->and(__('email', [], $locale))->not->toBe('Email')
            ->and(__('password', [], $locale))->not->toBe('Password');
    }
})->with('admin_login_page_label_locales');
