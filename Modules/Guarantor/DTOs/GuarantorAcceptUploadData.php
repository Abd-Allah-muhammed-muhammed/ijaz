<?php

namespace Modules\Guarantor\DTOs;

use Illuminate\Http\UploadedFile;
use Modules\Guarantor\Http\Requests\AcceptGuarantorRequest;

final readonly class GuarantorAcceptUploadData
{
    public function __construct(
        public UploadedFile $signature,
        public ?UploadedFile $ibanCertificate = null,
        public ?UploadedFile $crFile = null,
        public ?UploadedFile $articlesOfAssociation = null,
        public ?UploadedFile $nationalAddressFile = null,
    ) {}

    public static function fromRequest(AcceptGuarantorRequest $request): self
    {
        return new self(
            signature: $request->file('signature'),
            ibanCertificate: $request->file('iban_certificate'),
            crFile: $request->file('cr_file'),
            articlesOfAssociation: $request->file('articles_of_association'),
            nationalAddressFile: $request->file('national_address_file'),
        );
    }
}
