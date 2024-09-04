<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

test('visual snapshot of mutation tests when a mutant escaped', function (): void {
    $testsPath = dirname(__DIR__);

    $process = (new Process(
        ['php', 'vendor/bin/pest', '--mutate', '--group=escaped'],
        dirname($testsPath),
        ['PEST_PLUGIN_INTERNAL_TEST_SUITE' => 0],
    ));

    $process->run();

    $output = preg_replace([
        '#\\x1b[[][^A-Za-z]*[A-Za-z]#',
        '/(Tests\\\PHPUnit\\\CustomAffixes\\\InvalidTestName)([A-Za-z0-9]*)/',
    ], [
        '',
        '$1',
    ], $process->getOutput());

    expect($output)
        ->toMatchSnapshot();
});
