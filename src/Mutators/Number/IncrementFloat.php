<?php

declare(strict_types=1);

namespace Pest\Mutate\Mutators\Number;

use Pest\Mutate\Mutators\Abstract\AbstractMutator;
use PhpParser\Node;
use PhpParser\Node\Scalar\DNumber;

class IncrementFloat extends AbstractMutator
{
    public const SET = 'Number';

    public const DESCRIPTION = 'Increments a float number by 1.';

    public const DIFF = <<<'DIFF'
        $a = 1.2;  // [tl! remove]
        $a = 2.2;  // [tl! add]
        DIFF;

    public static function nodesToHandle(): array
    {
        return [DNumber::class];
    }

    public static function mutate(Node $node): Node
    {
        /** @var Node\Scalar\LNumber $node */
        $node->value++;

        return $node;
    }
}
