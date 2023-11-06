<?php

declare(strict_types=1);

namespace Pest\Mutate\Boostrappers;

use Pest\Contracts\Bootstrapper;
use Pest\Mutate\Contracts\Subscriber;
use Pest\Mutate\Event\Facade;
use Pest\Mutate\Subscribers\MutationTest\MutationKilled;
use Pest\Mutate\Subscribers\MutationTest\MutationNotCovered;
use Pest\Mutate\Subscribers\MutationTest\MutationSurvived;
use Pest\Mutate\Subscribers\MutationTest\MutationTimedOut;
use Pest\Support\Container;

/**
 * @internal
 */
final class BootSubscribers implements Bootstrapper
{
    /**
     * The list of Subscribers.
     *
     * @var array<int, class-string<Subscriber>>
     */
    private const SUBSCRIBERS = [
        MutationKilled::class,
        MutationSurvived::class,
        MutationTimedOut::class,
        MutationNotCovered::class,
    ];

    /**
     * Creates a new instance of the Boot Subscribers.
     */
    public function __construct(
        private readonly Container $container,
    ) {
    }

    /**
     * Boots the list of Subscribers.
     */
    public function boot(): void
    {
        foreach (self::SUBSCRIBERS as $subscriber) {
            $instance = $this->container->get($subscriber);

            assert($instance instanceof Subscriber);

            Facade::instance()->registerSubscriber($instance);
        }
    }
}
