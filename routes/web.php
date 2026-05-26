<?php

use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\GeneralController;
use App\Http\Controllers\General\AjaxController;
use App\Http\Controllers\General\MediaController;
use App\Http\Controllers\General\ReactSelectController;
use App\Http\Controllers\Payments\PayTabsController;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\General\CatalogSelectController;

Route::get('testing', static function () {

    //  return \Illuminate\Support\Facades\Http::post('https://translate.argosopentech.com/translate',[
    //    'q' => 'Hello World',
    //    'source' => 'en',
    //    'target' => 'ar',
    //  ])->json();
})
    ->name('testing');

Route::group(['prefix' => 'media', 'as' => 'media.'], static function () {
    Route::controller(MediaController::class)->middleware('auth:admin,provider')->group(function () {
        Route::get('file/{media}', 'file')->name('file-path');
        Route::get('/{media}', 'media')->name('media');
        Route::get('/chat/{media}', 'chatMedia')->name('chat.media');
    });
});

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ], static function () {
        Route::controller(GeneralController::class)->group(function () {
            Route::get('/lang/{locale}', 'switchLang')->name('switchLang');
            Route::get('/', 'index')->name('home');
            Route::get('/help', 'help')->name('help');
            Route::get('/about-us', 'aboutUs')->name('about-us');
            Route::get('/our-services', 'ourServices')->name('our-services');
            Route::get('/our-services/{service}', 'ourService')->name('our-services.show');
            Route::get('/customer-reviews', 'customerReviews')->name('customer-reviews');
            Route::get('/privacy-and-policies', 'privacyAndPolicies')->name('privacy-and-policies');
            Route::get('/privacy-policy', 'privacyPolicy')->name('privacy-policy');
            Route::get('/service-provider-authorization-terms-and-conditions', 'serviceProviderAuthorizationTermsAndConditions')->name('service-provider-authorization-terms-and-conditions');
            Route::get('/how-to-use-agency', 'howToUseAgency')->name('how-to-use-agency');
            Route::get('/real-estate-marketplace-terms-of-use', 'realEstateMarketplaceTermsOfUse')->name('real-estate-marketplace-terms-of-use');
            Route::get('payment/test/{payment}/callback', 'paymentTestCallback')->name('payment.test.callback');
            Route::get('payment/test/callback/{payment}/success', 'paymentTestSuccess')->name('payment.test.success');
            Route::get('payment/test/callback//{payment}/failed', 'paymentTestFailed')->name('payment.test.failed');
            Route::get('payment/test/{payment}', 'paymentTest')->name('payment.test');
            Route::post('payment/test/{payment}', 'paymentTestSubmit')->name('payment.test.submit');
            //    Route::get('/contact', 'contact')->name('contact');
            //    Route::post('/contact', 'sendContactMessage')->name('contact.send');
            //    Route::get('/privacy-policy', 'privacyPolicy')->name('privacy.policy');
            //    Route::get('/terms-of-service', 'termsOfService')->name('terms.service');
        });
        Route::controller(AuthController::class)->as('auth.')->group(function () {
            Route::get('/register', 'create')->name('register');
            Route::post('/register', 'store')->name('register.submit');
            Route::post('/otp/register', 'otp')->name('register.otp');
            Route::post('/otp/register/verify', 'verifyOtp')->name('register.otp.verify');
        });
        Route::controller(ReactSelectController::class)->prefix('general')->as('general.')->group(function () {
            Route::get('/skills', 'skills')->name('skills');
            Route::get('/categories', 'categories')->name('categories');
            Route::get('/regions', 'regions')->name('regions');
            Route::get('/cities', 'cities')->name('cities');
            Route::get('/nationalities', 'nationalities')->name('nationalities');
        });
        Route::controller(CatalogSelectController::class)->prefix('general')->as('general.')->group(function () {
            Route::get('/property-types', 'propertyTypes')->name('propertyTypes');
            Route::get('/property-categories', 'propertyCategories')->name('propertyCategories');
            Route::get('/car-categories', 'carCategories')->name('carCategories');
            Route::get('/car-types', 'carTypes')->name('carTypes');
            Route::get('/car-brands', 'carBrands')->name('carBrands');
            Route::get('/device-categories', 'deviceCategories')->name('deviceCategories');
            Route::get('/electronic-brands', 'electronicBrands')->name('electronicBrands');
            Route::get('/specializations', 'specializations')->name('specializations');
        });
        Route::controller(AjaxController::class)->prefix('ajax')->as('ajax.')->group(function () {
            Route::get('/categories', 'categories')->name('categories.index');
            Route::get('/categories/{category}', 'category')->name('categories.show');
        });
    });

Route::group(['prefix' => 'payments', 'as' => 'payment.'], static function () {
    Route::controller(PayTabsController::class)->as('paytabs.')->group(function () {
        //    Route::prefix('paytabs')->group(function () {
        //      Route::match(['get', 'post'], '/{payment}/redirect', 'redirect')->name('redirect');
        //      Route::match(['get', 'post'], '/{payment}/callback', 'callback')->name('callback');
        //    });
        Route::get('/{payment}/callback/success', 'success')->name('success');
        Route::get('/{payment}/callback/failed', 'failed')->name('failed');
    });
});
