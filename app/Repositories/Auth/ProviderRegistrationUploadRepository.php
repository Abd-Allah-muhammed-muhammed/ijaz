<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use App\Models\ProviderRegistrationUpload;
use Illuminate\Support\Carbon;

class ProviderRegistrationUploadRepository implements ProviderRegistrationUploadRepositoryInterface
{
    public function create(array $attributes): ProviderRegistrationUpload
    {
        return ProviderRegistrationUpload::query()->create($attributes);
    }

    public function findByIdAndToken(int $id, string $token): ?ProviderRegistrationUpload
    {
        return ProviderRegistrationUpload::query()
            ->whereKey($id)
            ->where('token', $token)
            ->first();
    }

    public function findByIdTokenAndField(int $id, string $token, string $field): ?ProviderRegistrationUpload
    {
        return ProviderRegistrationUpload::query()
            ->whereKey($id)
            ->where('token', $token)
            ->where('field', $field)
            ->first();
    }

    public function delete(ProviderRegistrationUpload $upload): void
    {
        $upload->delete();
    }

    public function countForToken(string $token): int
    {
        return ProviderRegistrationUpload::query()
            ->where('token', $token)
            ->count();
    }

    public function sumBytesForToken(string $token): int
    {
        return (int) ProviderRegistrationUpload::query()
            ->where('token', $token)
            ->sum('size');
    }

    public function chunkOlderThan(Carbon $cutoff, int $chunkSize, callable $callback): void
    {
        ProviderRegistrationUpload::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById($chunkSize, $callback);
    }
}
