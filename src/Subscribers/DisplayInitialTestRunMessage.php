<?php

declare(strict_types=1);

namespace Pest\Mutate\Subscribers;

use Pest\Mutate\Contracts\MutationTestRunner;
use Pest\Support\Container;
use PHPUnit\Event\TestSuite\Loaded;
use PHPUnit\Event\TestSuite\LoadedSubscriber;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;
use function Termwind\renderUsing;

/**
 * @internal
 */
final class DisplayInitialTestRunMessage implements LoadedSubscriber
{
    /**
     * Runs the subscriber.
     */
    public function notify(Loaded $event): void
    {
        // ...
    }
}
