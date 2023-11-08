<?php

declare(strict_types=1);

namespace Pest\Mutate\Mutators\String;

use Pest\Mutate\Mutators\Abstract\AbstractMutator;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

class NotEmptyStringToEmpty extends AbstractMutator
{
    public const SET = 'String';

    public const DESCRIPTION = 'Changes a non-empty string to an empty string.';

    public const DIFF = <<<'DIFF'
        $a = 'Hello World';  // [tl! remove]
        $a = '';  // [tl! add]
        DIFF;

    public static function nodesToHandle(): array
    {
        return [String_::class];
    }

    public static function can(Node $node): bool
    {
        return $node instanceof String_ &&
            $node->value !== '';
    }

    public static function mutate(Node $node): Node
    {
        /** @var Node\Scalar\String_ $node */
        $node->value = '';

        return $node;
    }
}
