<?php

if (! function_exists('array_pop_key')) {
    function array_pop_key(array &$array, string $key): mixed
    {
        if (array_key_exists($key, $array)) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return null;
    }
}
