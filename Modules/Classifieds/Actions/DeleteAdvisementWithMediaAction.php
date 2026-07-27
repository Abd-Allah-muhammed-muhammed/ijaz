<?php

namespace Modules\Classifieds\Actions;

use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;

final class DeleteAdvisementWithMediaAction
{
    public function handle(HasMedia $advisement): void
    {
        if (Schema::hasTable('media')) {
            $advisement->clearMediaCollection();
        }

        $advisement->delete();
    }
}
