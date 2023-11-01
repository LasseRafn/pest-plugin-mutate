<?php

declare(strict_types=1);

namespace Pest\Mutate\Mutators\String;

use Pest\Mutate\Contracts\Mutator;
use Pest\Mutate\Mutators\Concerns\HasName;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

class EmptyStringToNotEmpty implements Mutator
{
    use HasName;

    public static function can(Node $node): bool
    {
        return $node instanceof String_ &&
            $node->value === '';
    }

    public static function mutate(Node $node): Node
    {
        /** @var Node\Scalar\String_ $node */
        $node->value = 'PEST Mutator was here!';

        return $node;
    }
}