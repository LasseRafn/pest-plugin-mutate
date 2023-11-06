<?php

namespace Pest\Mutate\Event\Events\Test\Outcome;

use Pest\Mutate\Contracts\Event;
use Pest\Mutate\MutationTest;

class Survived implements Event
{
    public function __construct(
        public readonly MutationTest $test,
    )
    {
    }
}