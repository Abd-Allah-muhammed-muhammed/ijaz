<?php

namespace Modules\Opportunity\Exceptions;

use App\Exceptions\ApiException;

class OpportunityException extends ApiException
{
    /**
     * @param  array<string, mixed>|list<mixed>  $errors
     */
    public function __construct(
        string $translationKey,
        int $httpStatusCode = 422,
        array $errors = [],
    ) {
        parent::__construct($translationKey, $httpStatusCode, errors: $errors);
    }

    public function getTranslationKey(): string
    {
        return $this->getMessage();
    }
}
