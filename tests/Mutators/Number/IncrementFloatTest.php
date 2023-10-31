<?php

declare(strict_types=1);
use Pest\Mutate\Mutators\Number\IncrementFloat;

it('increments all floats by one', function (): void {
    expect(mutateCode(IncrementFloat::class, <<<'CODE'
        <?php

        $a = 1.0;
        CODE))->toBe(<<<'CODE'
        <?php

        $a = 2.0;
        CODE);
});
