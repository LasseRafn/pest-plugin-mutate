<?php

declare(strict_types=1);

namespace Tests\Fixtures\Traits;

trait SizeHelperTrait
{
    public static function isSmall(int $size): bool
    {
        return $size < 100;
    }
}
