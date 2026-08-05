<?php

use App\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BladeServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Paytabscom\Laravel_paytabs\PaypageServiceProvider;

return [
    AdminServiceProvider::class,
    AppServiceProvider::class,
    BladeServiceProvider::class,
    HorizonServiceProvider::class,
    RepositoryServiceProvider::class,
    PaypageServiceProvider::class,
];
