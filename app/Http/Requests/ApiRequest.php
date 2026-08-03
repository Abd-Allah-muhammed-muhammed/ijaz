<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Inertia\Support\Header as InertiaHeader;
use MMAE\ApiResponse\Configurations\Response;
use MMAE\ApiResponse\Traits\HasApiResponse;

/**
 * Application FormRequest base for endpoints that may be JSON API and/or Inertia web.
 *
 * Do not extend MMAE\ApiResponse\Request\ApiRequest — package 1.0.0 imports
 * MMAE\Apiresponse\Traits\HasApiResponse (lowercase "r"), which fatals on
 * case-sensitive filesystems (Linux production) while working on Windows.
 *
 * Validation failures:
 * - Inertia / non-JSON web → Laravel default (redirect back with session errors)
 * - JSON API (Accept / expectsJson, no X-Inertia) → MMAE JSON envelope
 */
class ApiRequest extends FormRequest
{
    use HasApiResponse;

    public function failedValidation(Validator $validator): void
    {
        if ($this->wantsJsonApiValidationEnvelope()) {
            throw new HttpResponseException($this->failedResponse(
                $validator->errors()->toArray(),
                property_exists($this, 'message') ? (! is_null($this->message) ? $this->message : Response::$VALIDATION_FAILED_MESSAGE) : Response::$VALIDATION_FAILED_MESSAGE,
                property_exists($this, 'statusCode') ? ($this->statusCode ?? Response::$VALIDATION_FAILED_STATUS) : Response::$VALIDATION_FAILED_STATUS,
            ));
        }

        parent::failedValidation($validator);
    }

    /**
     * Inertia never accepts a raw 422 JSON body — it needs redirect-back-with-errors.
     * Dual-use FormRequests (API + Provider/Dashboard) must branch on the request, not the class.
     */
    protected function wantsJsonApiValidationEnvelope(): bool
    {
        if ($this->header(InertiaHeader::INERTIA)) {
            return false;
        }

        return $this->expectsJson();
    }
}
