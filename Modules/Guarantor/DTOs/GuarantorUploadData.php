<?php

namespace Modules\Guarantor\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class GuarantorUploadData
{
    /**
     * @param  list<UploadedFile>  $contracts
     * @param  list<UploadedFile>  $companyDocuments
     * @param  list<UploadedFile>  $files
     */
    public function __construct(
        public ?UploadedFile $signature = null,
        public ?UploadedFile $authorizedId = null,
        public array $contracts = [],
        public ?UploadedFile $ibanCertificate = null,
        public array $companyDocuments = [],
        public array $files = [],
    ) {}

    public static function fromIndividualRequest(Request $request): self
    {
        return new self(
            signature: $request->file('signature'),
        );
    }

    public static function fromCompanyRequest(Request $request): self
    {
        /** @var list<UploadedFile> $contracts */
        $contracts = array_values(array_filter($request->file('contracts', []) ?: []));
        /** @var list<UploadedFile> $companyDocuments */
        $companyDocuments = array_values(array_filter($request->file('company_documents', []) ?: []));

        return new self(
            signature: $request->file('signature'),
            authorizedId: $request->file('authorized_id'),
            contracts: $contracts,
            ibanCertificate: $request->file('iban_certificate'),
            companyDocuments: $companyDocuments,
        );
    }

    public static function fromUpdateRequest(Request $request): self
    {
        /** @var list<UploadedFile> $files */
        $files = array_values(array_filter($request->file('files', []) ?: []));

        return new self(
            files: $files,
        );
    }
}
