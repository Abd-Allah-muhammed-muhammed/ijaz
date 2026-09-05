<?php

namespace App\Contracts\Auth;

use App\Models\ProviderRegistrationUpload;
use Illuminate\Support\Carbon;

interface ProviderRegistrationUploadRepositoryInterface
{
    public function create(array $attributes): ProviderRegistrationUpload;

    public function findByIdAndToken(int $id, string $token): ?ProviderRegistrationUpload;

    public function findByIdTokenAndField(int $id, string $token, string $field): ?ProviderRegistrationUpload;

    public function delete(ProviderRegistrationUpload $upload): void;

    public function countForToken(string $token): int;

    public function sumBytesForToken(string $token): int;

    public function chunkOlderThan(Carbon $cutoff, int $chunkSize, callable $callback): void;
}
