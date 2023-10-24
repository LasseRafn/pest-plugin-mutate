<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class AgeHelper
{
    public static function isAdult(int $age): bool
    {
        return $age >= 18;
    }
}
