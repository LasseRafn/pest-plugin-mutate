<?php

declare(strict_types=1);

namespace Pest\Mutate\Support\Configuration;

use Pest\Mutate\Options\ClassOption;
use Pest\Mutate\Options\CoveredOnlyOption;
use Pest\Mutate\Options\MinMsiOption;
use Pest\Mutate\Options\MutateOption;
use Pest\Mutate\Options\MutatorsOption;
use Pest\Mutate\Options\ParallelOption;
use Pest\Mutate\Options\PathsOption;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;

class CliConfiguration extends AbstractConfiguration
{
    private const OPTIONS = [
        MutateOption::class,
        ClassOption::class,
        CoveredOnlyOption::class,
        MinMsiOption::class,
        MutatorsOption::class,
        PathsOption::class,
        ParallelOption::class,
    ];

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function fromArguments(array $arguments): array
    {
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

        $input = new ArgvInput($filteredArguments, new InputDefinition($inputOptions));

        if ($input->hasOption(CoveredOnlyOption::ARGUMENT)) {
            $this->coveredOnly($input->getOption(CoveredOnlyOption::ARGUMENT) !== 'false');
        }

        if ($input->hasOption(PathsOption::ARGUMENT)) {
            $this->path(explode(',', (string) $input->getOption(PathsOption::ARGUMENT))); // @phpstan-ignore-line
        }

        if ($input->hasOption(MutatorsOption::ARGUMENT)) {
            $this->mutator(explode(',', (string) $input->getOption(MutatorsOption::ARGUMENT))); // @phpstan-ignore-line
        }

        if ($input->hasOption(MinMsiOption::ARGUMENT)) {
            $this->min((float) $input->getOption(MinMsiOption::ARGUMENT)); // @phpstan-ignore-line
        }

        if ($input->hasOption(CoveredOnlyOption::ARGUMENT)) {
            $this->coveredOnly($input->getOption(CoveredOnlyOption::ARGUMENT) !== 'false');
        }

        if ($input->hasOption(ParallelOption::ARGUMENT)) {
            $this->parallel();

            if (($index = array_search('--parallel', $_SERVER['argv'], true)) !== false) {
                unset($_SERVER['argv'][$index]);
            }
        }

        if ($input->hasOption(ClassOption::ARGUMENT)) {
            $this->class(explode(',', (string) $input->getOption(ClassOption::ARGUMENT))); // @phpstan-ignore-line
        }

        return $arguments;
    }
}