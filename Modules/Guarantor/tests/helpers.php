<?php

use Modules\Catalog\Models\Bank;

function activeGuarantorTestBank(?array $translations = null): Bank
{
    return Bank::factory()->create([
        'translations' => $translations ?? geoNameTranslations('Test Bank'),
    ]);
}

function defaultGuarantorTestBankId(): int
{
    return activeGuarantorTestBank()->id;
}
