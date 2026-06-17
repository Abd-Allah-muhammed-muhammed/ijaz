<?php

namespace Modules\Guarantor\DTOs\Dashboard;

final readonly class AdminReleaseInstallmentData
{
    public function __construct(
        public string $trigger = 'admin',
    ) {}
}
