<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Tests\Fixtures\Traits\SizeHelperTrait;

class SizeHelper
{
    use SizeHelperTrait;

    public static function isBig(int $size): bool
    {
        return $size >= 100;
    }
}
