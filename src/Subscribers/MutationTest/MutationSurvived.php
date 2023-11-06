<?php

declare(strict_types=1);

namespace Pest\Mutate\Subscribers\MutationTest;

use Pest\Mutate\Contracts\Printer;
use Pest\Mutate\Event\Events\Test\Outcome\Survived;
use Pest\Mutate\Event\Events\Test\Outcome\SurvivedSubscriber;
use Pest\Support\Container;

class MutationSurvived implements SurvivedSubscriber
{
    public function notify(Survived $event): void
    {
        Container::getInstance()->get(Printer::class)->reportSurvivedMutation($event->test); // @phpstan-ignore-line
    }
}
