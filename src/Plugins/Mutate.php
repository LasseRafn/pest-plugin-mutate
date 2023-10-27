<?php

declare(strict_types=1);

namespace Pest\Mutate\Plugins;

use Infection\StreamWrapper\IncludeInterceptor;
use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Mutate\Contracts\MutationTestRunner;
use Pest\Mutate\Factories\ProfileFactory;
use Pest\Mutate\Options\CoveredOnlyOption;
use Pest\Mutate\Options\MinMsiOption;
use Pest\Mutate\Options\MutateOption;
use Pest\Mutate\Options\MutatorsOption;
use Pest\Mutate\Options\ParallelOption;
use Pest\Mutate\Options\PathsOption;
use Pest\Mutate\Subscribers\DisablePhpCodeCoverageIfNotRequired;
use Pest\Mutate\Subscribers\EnsureToRunMutationTestingIfRequired;
use Pest\Mutate\Subscribers\PrepareForInitialTestRun;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Plugins\Parallel;
use Pest\Support\Container;
use Pest\Support\Coverage;
use PHPUnit\Event\Facade;
use PHPUnit\Event\Subscriber;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @final
 */
class Mutate implements Bootable, HandlesArguments
{
    use HandleArguments;

    private const OPTIONS = [
        MutateOption::class,
        PathsOption::class,
        MutatorsOption::class,
        MinMsiOption::class,
        CoveredOnlyOption::class,
        ParallelOption::class,
    ];

    /**
     * The list of Subscribers.
     *
     * @var array<int, class-string<Subscriber>>
     */
    private const SUBSCRIBERS = [
        DisablePhpCodeCoverageIfNotRequired::class,
        PrepareForInitialTestRun::class,
        EnsureToRunMutationTestingIfRequired::class,
    ];

    /**
     * Creates a new Plugin instance.
     */
    public function __construct(
        private readonly Container $container,
        private readonly OutputInterface $output
    ) {
        //
    }

    public function boot(): void
    {
        $this->container->add(MutationTestRunner::class, new \Pest\Mutate\Tester\MutationTestRunner($this->output));

        foreach (self::SUBSCRIBERS as $subscriber) {
            $instance = $this->container->get($subscriber);

            assert($instance instanceof Subscriber);

            Facade::instance()->registerSubscriber($instance);
        }

        if (getenv('MUTATION_TESTING') !== false) {
            IncludeInterceptor::intercept(getenv('MUTATION_TESTING'), getenv('MUTATION_FILE'));
            IncludeInterceptor::enable();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleArguments(array $arguments): array
    {
        /** @var \Pest\Mutate\Tester\MutationTestRunner $mutationTestRunner */
        $mutationTestRunner = Container::getInstance()->get(MutationTestRunner::class);

        $filteredArguments = ['vendor/bin/pest'];
        $inputOptions = [];
        foreach ($arguments as $key => $argument) {
            foreach (self::OPTIONS as $option) {
                if ($option::match($argument)) {
                    $filteredArguments[] = $argument;
                    $inputOptions[] = $option::inputOption();

                    if ($option::remove()) {
                        unset($arguments[$key]);
                    }
                }
            }
        }

        $mutationTestRunner->setOriginalArguments($arguments);

        $inputDefinition = new InputDefinition($inputOptions);

        $input = new ArgvInput($filteredArguments, $inputDefinition);

        // always enable php coverage report, but it will be disabled if not required
        if (Coverage::isAvailable()) {
            $coverageRequired = array_filter($arguments, fn (string $argument): bool => str_starts_with($argument, '--coverage')) !== [];
            if ($coverageRequired) {
                $mutationTestRunner->doNotDisableCodeCoverage();
            }
            $arguments[] = '--coverage-php='.Coverage::getPath();
        }

        if (! $input->hasOption(MutateOption::ARGUMENT)) {
            return $arguments;
        }

        $profileName = $input->getOption(MutateOption::ARGUMENT) ?? 'default';
        $profileFactory = new ProfileFactory($profileName); // @phpstan-ignore-line

        $mutationTestRunner->enable($profileName); // @phpstan-ignore-line

        if ($input->hasOption(PathsOption::ARGUMENT)) {
            $profileFactory->paths(explode(',', (string) $input->getOption(PathsOption::ARGUMENT))); // @phpstan-ignore-line
        }

        if ($input->hasOption(MutatorsOption::ARGUMENT)) {
            $profileFactory->mutators(explode(',', (string) $input->getOption(MutatorsOption::ARGUMENT))); // @phpstan-ignore-line
        }

        if ($input->hasOption(MinMsiOption::ARGUMENT)) {
            $profileFactory->min((float) $input->getOption(MinMsiOption::ARGUMENT)); // @phpstan-ignore-line
        }

        if ($input->hasOption(CoveredOnlyOption::ARGUMENT)) {
            $profileFactory->coveredOnly($input->getOption(CoveredOnlyOption::ARGUMENT) !== 'false');
        }

        if ($input->hasOption(ParallelOption::ARGUMENT)) {
            unset($arguments[array_search('--'.ParallelOption::ARGUMENT, $arguments, true)]);
            $profileFactory->parallel();
            Parallel::disable();
        }

        return $arguments;
    }
}
