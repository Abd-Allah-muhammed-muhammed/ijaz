<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use MMAE\ApiResponse\Configurations\Response;
use MMAE\ApiResponse\Traits\HasApiResponse;

/**
 * Application FormRequest base that returns the MMAE JSON validation envelope.
 *
 * Do not extend MMAE\ApiResponse\Request\ApiRequest — package 1.0.0 imports
 * MMAE\Apiresponse\Traits\HasApiResponse (lowercase "r"), which fatals on
 * case-sensitive filesystems (Linux production) while working on Windows.
 */
class ApiRequest extends FormRequest
{
    use HasApiResponse;

    public function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException($this->failedResponse(
            $validator->errors()->toArray(),
            property_exists($this, 'message') ? (! is_null($this->message) ? $this->message : Response::$VALIDATION_FAILED_MESSAGE) : Response::$VALIDATION_FAILED_MESSAGE,
            property_exists($this, 'statusCode') ? ($this->statusCode ?? Response::$VALIDATION_FAILED_STATUS) : Response::$VALIDATION_FAILED_STATUS,
        ));
    }
}
