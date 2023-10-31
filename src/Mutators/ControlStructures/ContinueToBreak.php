<?php

declare(strict_types=1);

namespace Pest\Mutate\Mutators\ControlStructures;

use Pest\Mutate\Contracts\Mutator;
use Pest\Mutate\Mutators\Concerns\HasName;
use PhpParser\Node;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;

class ContinueToBreak implements Mutator
{
    use HasName;

    public static function can(Node $node): bool
    {
        return $node instanceof Continue_;
    }

    public static function mutate(Node $node): Node
    {
        /** @var Node\Stmt\Continue_ $node */

        return new Break_($node->num, $node->getAttributes());
    }
}