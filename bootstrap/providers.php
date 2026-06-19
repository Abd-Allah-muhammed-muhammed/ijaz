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
    PaypageServiceProvider::class,
];
