<?php

namespace App\Support\Api\Facades;

use App\Support\Api\ApiVersionService;
use Illuminate\Support\Facades\Facade;

/**
 * Static-style access to informational API version helpers.
 *
 * INFORMATIONAL ONLY — does not control routing or controller dispatch.
 * Laravel matches routes by URL prefix (api/v1/...); this facade only reports
 * which version key the resolver chain inferred for awareness purposes.
 *
 * @method static string current(\Illuminate\Http\Request|null $request = null)
 * @method static bool isDeprecated(string|null $version = null)
 * @method static \App\Support\Api\ApiVersionRegistry registry()
 * @method static \App\Support\Api\ApiVersionResolverChain chain()
 *
 * @see ApiVersionService
 */
class ApiVersion extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiVersionService::class;
    }
}
