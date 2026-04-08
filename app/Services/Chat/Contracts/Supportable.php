<?php

namespace App\Services\Chat\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Supportable extends HasConversation
{
    public function supportTicket(): MorphMany;

    public function supportChat(): MorphOne;
}
