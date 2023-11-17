<?php

declare(strict_types=1);

use Pest\Mutate\Mutators\String\ConcatSwitchSides;

it('mutates a coalesce operation by switching the parameters', function (): void {
    expect(mutateCode(ConcatSwitchSides::class, <<<'CODE'
        <?php

        $a = $b . $c;
        CODE))->toBe(<<<'CODE'
        <?php
        
        $a = $c . $b;
        CODE);
});

it('does not mutate other operators', function (): void {
    mutateCode(ConcatSwitchSides::class, <<<'CODE'
        <?php

        return $a + $b;
        CODE);
})->expectExceptionMessage('No mutation performed');
