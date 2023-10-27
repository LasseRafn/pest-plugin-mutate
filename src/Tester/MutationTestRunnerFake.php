<?php

declare(strict_types=1);

namespace Pest\Mutate\Tester;

use Pest\Mutate\Contracts\MutationTestRunner as MutationTestRunnerContract;

class MutationTestRunnerFake implements MutationTestRunnerContract
{
    public function run(): void
    {
        // TODO: Implement run() method.
    }

    public function enable(string $profile): void
    {
        // TODO: Implement enable() method.
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function isCodeCoverageRequested(): bool
    {
        return false;
    }

    public function setOriginalArguments(array $arguments): void
    {
        // TODO: Implement setOriginalArguments() method.
    }
}
