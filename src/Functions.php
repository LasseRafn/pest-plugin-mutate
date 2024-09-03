<?php

declare(strict_types=1);
use Pest\Mutate\Contracts\MutationTestRunner;
use Pest\Mutate\Repositories\ConfigurationRepository;
use Pest\Mutate\Support\Configuration\GlobalConfiguration;
use Pest\PendingCalls\BeforeEachCall;
use Pest\Support\Backtrace;
use Pest\Support\Container;
use Pest\TestSuite;

// @codeCoverageIgnoreStart
if (! function_exists('mutate')) {
    // @codeCoverageIgnoreEnd

    /**
     * Runs the test suite or a single test mutating the given class(es).
     *
     * @param array<int, string>|string $classOrPath
     */
    function mutate(array|string $classOrPath): GlobalConfiguration
    {
        try {
            if (! str_ends_with(Backtrace::testFile(), 'Pest.php')) {
                Container::getInstance()->get(MutationTestRunner::class)->enable(); // @phpstan-ignore-line

                (new BeforeEachCall(TestSuite::getInstance(), Backtrace::testFile(), fn (): null => null))->only();
            }
        } catch (Throwable) { // @phpstan-ignore-line
            // @ignoreException
        }

        $classesOrPaths = is_array($classOrPath) ? $classOrPath : [$classOrPath];
        $classes = [];
        $paths = [];

        foreach ($classesOrPaths as $classOrPath) {
            is_file($classOrPath) ? $paths[] = $classOrPath : $classes[] = $classOrPath;
        }

        $configuration = Container::getInstance()->get(ConfigurationRepository::class)->globalConfiguration('default');

        if (count($classes) > 0) {
            $configuration->class($classes);
        }

        if (count($paths) > 0) {
            $configuration->path($paths);
        }

        return $configuration;
    }
}
