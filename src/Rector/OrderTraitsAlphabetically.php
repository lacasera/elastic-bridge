<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class OrderTraitsAlphabetically extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Order trait use statements alphabetically', [
            new CodeSample(
                <<<'CODE'
<?php
class Example {
    use ZzzTrait, FooTrait;
}
CODE,
                <<<'CODE'
<?php
class Example {
    use FooTrait, ZzzTrait;
}
CODE
            ),
        ]);
    }

    /**
     * @param  Class_  $node
     */
    public function refactor(Node $node): ?Node
    {
        $traits = $this->collectTraits($node);

        if (count($traits) <= 1) {
            return null;
        }

        $traitNames = array_map(
            fn (TraitUse $traitUse): string => $this->nodeNameResolver->getShortName($traitUse->traits[0]),
            $traits
        );

        if ($this->areTraitsOrdered($traitNames)) {
            return null;
        }

        $sortedTraits = $traits;
        usort($sortedTraits, function (TraitUse $a, TraitUse $b): int {
            $traitNameA = $this->nodeNameResolver->getShortName($a->traits[0]);
            $traitNameB = $this->nodeNameResolver->getShortName($b->traits[0]);

            return strcmp($traitNameA, $traitNameB);
        });

        $node->stmts = array_merge(
            $sortedTraits,
            array_values(array_filter($node->stmts, fn ($stmt): bool => ! $stmt instanceof TraitUse))
        );

        return $node;
    }

    /**
     * @return array<int, TraitUse>
     */
    private function collectTraits(Class_ $class): array
    {
        return array_filter($class->stmts, fn ($stmt): bool => $stmt instanceof TraitUse);
    }

    /**
     * @param  array<int, string>  $traitNames
     */
    private function areTraitsOrdered(array $traitNames): bool
    {
        $sorted = $traitNames;
        sort($sorted);

        return $traitNames === $sorted;
    }
}
