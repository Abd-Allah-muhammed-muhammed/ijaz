<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

trait HandlesTransactionalFileUpload
{
    /**
     * Store an optional upload, run DB work inside a transaction, delete the new
     * file on any failure, and delete a previous path only after a successful commit.
     *
     * When $disk is null, uses config('filesystems.default'). Explicit disk arguments
     * (e.g. disk: 'public') are unchanged.
     *
     * @template TReturn
     *
     * @param  callable(?string $storedPath): TReturn  $dbWork
     * @return TReturn
     *
     * @throws Throwable
     */
    protected function storeFileWithCleanup(
        ?UploadedFile $file,
        string $directory,
        callable $dbWork,
        ?string $disk = null,
        ?string $previousPath = null,
    ): mixed {
        $storedPath = null;
        $resolvedDisk = $disk ?? (string) config('filesystems.default');

        try {
            DB::beginTransaction();

            if ($file !== null) {
                $storedPath = $file->store($directory, $resolvedDisk);
            }

            $result = $dbWork($storedPath);

            DB::commit();

            if ($previousPath !== null && $storedPath !== null && $previousPath !== $storedPath) {
                Storage::disk($resolvedDisk)->delete($previousPath);
            }

            return $result;
        } catch (Throwable $throwable) {
            DB::rollBack();

            if ($storedPath !== null) {
                Storage::disk($resolvedDisk)->delete($storedPath);
            }

            report($throwable);

            throw $throwable;
        }
    }
}
