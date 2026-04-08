<?php

namespace App\Enums\Utilities;

trait HasOperations
{
    /**
     * Check if the current enum is not the same as the provided enum.
     *
     * @param  HasOperations  $num
     */
    public function isNot(self $num): bool
    {
        return ! $this->is($num);
    }

    /**
     * Check if the current enum is the same as the provided enum.
     *
     * @param  HasOperations  $enum
     */
    public function is(self $enum): bool
    {
        return $enum === $this;
    }

    /**
     * Check if the current enum is not in the provided array of enums.
     *
     * @param  array<self>  $enums
     */
    public function isNotIn(array $enums): bool
    {
        return ! $this->isIn($enums);
    }

    /**
     * Check if the current enum is in the provided array of enums.
     *
     * @param  array<self>  $enums
     */
    public function isIn(array $enums): bool
    {
        return in_array($this, $enums, true);
    }
}
