<?php

declare(strict_types=1);

namespace Pest\Mutate\Subscribers;

use Pest\Mutate\Contracts\MutationTestRunner;
use Pest\Support\Container;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;

/**
 * @internal
 */
final class EnsureToRunMutationTestingIfRequired implements FinishedSubscriber
{
    /**
     * Runs the subscriber.
     */
    public function notify(Finished $event): void
    {
        /** @var MutationTestRunner $mutationTestRunner */
        $mutationTestRunner = Container::getInstance()->get(MutationTestRunner::class);

        if ($mutationTestRunner->isEnabled()) {
            $mutationTestRunner->run();
        }
    }
}
