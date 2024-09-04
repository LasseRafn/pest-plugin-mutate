<?php

declare(strict_types=1);

namespace Pest\Mutate\Plugins;

use NunoMaduro\Collision\Adapters\Phpunit\Support\ResultReflection;
use Pest\Contracts\Bootstrapper;
use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Mutate\Boostrappers\BootPhpUnitSubscribers;
use Pest\Mutate\Boostrappers\BootSubscribers;
use Pest\Mutate\Cache\NullStore;
use Pest\Mutate\Contracts\MutationTestRunner;
use Pest\Mutate\Contracts\Printer;
use Pest\Mutate\Event\Events\Test\HookMethod\BeforeFirstTestExecuted;
use Pest\Mutate\Event\Events\Test\HookMethod\BeforeFirstTestExecutedSubscriber;
use Pest\Mutate\Event\Events\Test\Outcome\Caught;
use Pest\Mutate\Event\Events\Test\Outcome\CaughtSubscriber;
use Pest\Mutate\Event\Events\Test\Outcome\Escaped;
use Pest\Mutate\Event\Events\Test\Outcome\EscapedSubscriber;
use Pest\Mutate\Event\Events\Test\Outcome\NotCovered;
use Pest\Mutate\Event\Events\Test\Outcome\NotCoveredSubscriber;
use Pest\Mutate\Event\Events\Test\Outcome\Timeout;
use Pest\Mutate\Event\Events\Test\Outcome\TimeoutSubscriber;
use Pest\Mutate\Event\Events\TestSuite\FinishMutationGeneration;
use Pest\Mutate\Event\Events\TestSuite\FinishMutationGenerationSubscriber;
use Pest\Mutate\Event\Events\TestSuite\FinishMutationSuite;
use Pest\Mutate\Event\Events\TestSuite\FinishMutationSuiteSubscriber;
use Pest\Mutate\Event\Events\TestSuite\StartMutationGeneration;
use Pest\Mutate\Event\Events\TestSuite\StartMutationGenerationSubscriber;
use Pest\Mutate\Event\Events\TestSuite\StartMutationSuite;
use Pest\Mutate\Event\Events\TestSuite\StartMutationSuiteSubscriber;
use Pest\Mutate\Event\Facade;
use Pest\Mutate\Repositories\ConfigurationRepository;
use Pest\Mutate\Subscribers\PrinterSubscriber;
use Pest\Mutate\Support\Printers\DefaultPrinter;
use Pest\Mutate\Support\StreamWrapper;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Plugins\Parallel;
use Pest\Support\Container;
use Pest\Support\Coverage;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @final
 */
class Mutate implements AddsOutput, Bootable, HandlesArguments
{
    use HandleArguments;

    final public const ENV_MUTATION_TESTING = 'PEST_MUTATION_TESTING';

    final public const ENV_MUTATION_FILE = 'PEST_MUTATION_FILE';

    /**
     * The Kernel bootstrappers.
     *
     * @var array<int, class-string>
     */
    private const BOOTSTRAPPERS = [
        BootPhpUnitSubscribers::class,
        BootSubscribers::class,
    ];

    /**
     * Creates a new Plugin instance.
     */
    public function __construct(
        private readonly Container $container,
        private readonly OutputInterface $output,
    ) {
        //
    }

    public function boot(): void
    {
        if (getenv(self::ENV_MUTATION_TESTING) !== false) {
            StreamWrapper::start(getenv(self::ENV_MUTATION_TESTING), (string) getenv(self::ENV_MUTATION_FILE));
        }

        $this->container->add(MutationTestRunner::class, $runner = new \Pest\Mutate\Tester\MutationTestRunner);
        $this->container->add(Printer::class, $printer = new DefaultPrinter($this->output));

        if ($_SERVER['COLLISION_PRINTER_COMPACT'] ?? false) {
            $printer->compact();
        }

        foreach (self::BOOTSTRAPPERS as $bootstrapper) {
            $bootstrapper = Container::getInstance()->get($bootstrapper);
            assert($bootstrapper instanceof Bootstrapper);

            $bootstrapper->boot();
        }

        $this->container->add(CacheInterface::class, new NullStore);
    }

    /**
     * {@inheritdoc}
     */
    public function handleArguments(array $arguments): array
    {
        /** @var \Pest\Mutate\Tester\MutationTestRunner $mutationTestRunner */
        $mutationTestRunner = Container::getInstance()->get(MutationTestRunner::class);

        if ($this->hasArgument('mutate', $arguments)) {
            return $arguments;
        }

        $arguments = $this->popArgument('--mutate', $arguments);

        $mutationTestRunner->enable();
        $this->ensurePrinterIsRegistered();

        if (Coverage::isAvailable()) {
            $coverageRequired = array_filter($arguments, fn (string $argument): bool => str_starts_with($argument, '--coverage')) !== [];
            if ($coverageRequired) {
                $mutationTestRunner->doNotDisableCodeCoverage();
            } else {
                $arguments[] = '--coverage-php='.Coverage::getPath();
            }
        }

        $arguments = Container::getInstance()->get(ConfigurationRepository::class) // @phpstan-ignore-line
            ->cliConfiguration->fromArguments($arguments);

        $mutationTestRunner->setOriginalArguments($arguments);

        return $arguments;
    }

    public function addOutput(int $exitCode): int
    {
        /** @var MutationTestRunner $mutationTestRunner */
        $mutationTestRunner = Container::getInstance()->get(MutationTestRunner::class);

        if (Parallel::isWorker() || $exitCode !== 0 || ! $mutationTestRunner->isEnabled()) {
            return $exitCode;
        }

        if (! isset($_SERVER['PEST_PLUGIN_INTERNAL_TEST_SUITE']) || $_SERVER['PEST_PLUGIN_INTERNAL_TEST_SUITE'] === 1) {
            return $exitCode;
        }

        if (ResultReflection::numberOfTests(\PHPUnit\TestRunner\TestResult\Facade::result()) > 0) {
            return $mutationTestRunner->run();
        }

        return $exitCode;
    }

    private function ensurePrinterIsRegistered(): void
    {
        /** @var Printer $printer */
        $printer = Container::getInstance()->get(Printer::class);

        $subscribers = [
            // Test > Hook Methods
            new class($printer) extends PrinterSubscriber implements BeforeFirstTestExecutedSubscriber
            {
                public function notify(BeforeFirstTestExecuted $event): void
                {
                    $this->printer()->printFilename($event->testCollection);
                }
            },

            // Test > Outcome
            new class($printer) extends PrinterSubscriber implements CaughtSubscriber
            {
                public function notify(Caught $event): void
                {
                    $this->printer()->reportCaughtMutation($event->test);
                }
            },

            new class($printer) extends PrinterSubscriber implements EscapedSubscriber
            {
                public function notify(Escaped $event): void
                {
                    $this->printer()->reportEscapedMutation($event->test);
                }
            },

            new class($printer) extends PrinterSubscriber implements TimeoutSubscriber
            {
                public function notify(Timeout $event): void
                {
                    $this->printer()->reportTimedOutMutation($event->test);
                }
            },

            new class($printer) extends PrinterSubscriber implements NotCoveredSubscriber
            {
                public function notify(NotCovered $event): void
                {
                    $this->printer()->reportNotCoveredMutation($event->test);
                }
            },

            // MutationSuite
            new class($printer) extends PrinterSubscriber implements StartMutationGenerationSubscriber
            {
                public function notify(StartMutationGeneration $event): void
                {
                    $this->printer()->reportMutationGenerationStarted($event->mutationSuite);
                }
            },

            new class($printer) extends PrinterSubscriber implements FinishMutationGenerationSubscriber
            {
                public function notify(FinishMutationGeneration $event): void
                {
                    $this->printer()->reportMutationGenerationFinished($event->mutationSuite);
                }
            },

            new class($printer) extends PrinterSubscriber implements StartMutationSuiteSubscriber
            {
                public function notify(StartMutationSuite $event): void
                {
                    $this->printer()->reportMutationSuiteStarted($event->mutationSuite);
                }
            },

            new class($printer) extends PrinterSubscriber implements FinishMutationSuiteSubscriber
            {
                public function notify(FinishMutationSuite $event): void
                {
                    $this->printer()->reportMutationSuiteFinished($event->mutationSuite);
                }
            },
        ];

        Facade::instance()->registerSubscribers(...$subscribers);
    }
}
