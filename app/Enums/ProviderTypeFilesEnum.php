<?php

namespace App\Enums;

use App\Enums\Utilities\Collectable;

enum ProviderTypeFilesEnum: string
{
    use Collectable;
    case ID_IMAGE = 'id_image';
    case COMMERCIAL_RECORD = 'commercial_record';
    case FREELANCER_CERTIFICATION = 'freelancer_certification';
    case IBAN_CERTIFICATION = 'iban_certification';
    case LICENSE_TO_PRACTICE_LAW = 'license_to_practice_law';
}
