<?php

use App\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BladeServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Paytabscom\Laravel_paytabs\PaypageServiceProvider;

return [
    AdminServiceProvider::class,
    AppServiceProvider::class,
    BladeServiceProvider::class,
    RepositoryServiceProvider::class,
    // TelescopeServiceProvider is registered conditionally in AppServiceProvider
    // (laravel/telescope is a --dev dependency).
    PaypageServiceProvider::class,
];
