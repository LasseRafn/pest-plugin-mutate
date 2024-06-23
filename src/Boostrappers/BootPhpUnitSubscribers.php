<?php

declare(strict_types=1);

namespace Pest\Mutate\Boostrappers;

use Pest\Contracts\Bootstrapper;
use Pest\Mutate\Subscribers\DisablePhpCodeCoverageIfNotRequired;
use Pest\Mutate\Subscribers\DisplayInitialTestRunMessage;
use Pest\Mutate\Subscribers\EnsureInitialTestRunWasSuccessful;
use Pest\Mutate\Subscribers\EnsurePrinterIsRegistered;
use Pest\Mutate\Subscribers\EnsureToRunMutationTestingIfRequired;
use Pest\Mutate\Subscribers\PrepareForInitialTestRun;
use Pest\Subscribers;
use Pest\Support\Container;
use PHPUnit\Event\Facade;
use PHPUnit\Event\Subscriber;

/**
 * @internal
 */
final class BootPhpUnitSubscribers implements Bootstrapper
{
    /**
     * The list of Subscribers.
     *
     * @var array<int, class-string<Subscriber>>
     */
    private const SUBSCRIBERS = [
        DisablePhpCodeCoverageIfNotRequired::class,
        DisplayInitialTestRunMessage::class,
        PrepareForInitialTestRun::class,
        EnsureInitialTestRunWasSuccessful::class,
        EnsureToRunMutationTestingIfRequired::class,
        EnsurePrinterIsRegistered::class,
    ];

    /**
     * Creates a new instance of the Boot Subscribers.
     */
    public function __construct(
        private readonly Container $container,
    ) {}

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
