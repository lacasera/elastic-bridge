<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Rector;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class OrderImportsAlphabetically extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Namespace_::class];
    }

    #[Override]
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Order import use statements alphabetically', [
            new CodeSample(
                <<<'CODE'
<?php
namespace App;
use Zzz\Bar;
use App\Foo;
CODE,
                <<<'CODE'
<?php
namespace App;
use App\Foo;
use Zzz\Bar;
CODE
            ),
        ]);
    }

    #[Override]
    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Namespace_) {
            return null;
        }

        $stmts = $node->stmts;

        // sort inner use elements first
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Use_) {
                $this->sortUseUseList($stmt->uses);
            } elseif ($stmt instanceof GroupUse) {
                $this->sortUseUseList($stmt->uses);
            }
        }

        // find contiguous use block at top
        $i = 0;
        $count = count($stmts);
        while ($i < $count && ($stmts[$i] instanceof Use_ || $stmts[$i] instanceof GroupUse)) {
            $i++;
        }

        if ($i <= 1) {
            return null;
        }

        $useBlock = array_slice($stmts, 0, $i);
        usort($useBlock, function (Stmt $a, Stmt $b): int {
            $wa = $this->useTypeWeight($a);
            $wb = $this->useTypeWeight($b);
            if ($wa !== $wb) {
                return $wa <=> $wb;
            }

            $ka = strtolower($this->firstUseKey($a));
            $kb = strtolower($this->firstUseKey($b));

            return $ka <=> $kb;
        });

        $node->stmts = array_merge($useBlock, array_slice($stmts, $i));

        return $node;
    }

    /**
     * @param  array<int, UseItem>  $uses
     */
    private function sortUseUseList(array &$uses): void
    {
        usort($uses, function (UseItem $a, UseItem $b): int {
            $ka = $this->useUseKey($a);
            $kb = $this->useUseKey($b);

            return strcasecmp($ka, $kb);
        });
    }

    private function useUseKey(UseItem $useItem): string
    {
        if ($useItem->alias instanceof Identifier) {
            return $useItem->alias->toString();
        }

        return (string) $this->nodeNameResolver->getName($useItem->name);
    }

    private function useTypeWeight(Stmt $stmt): int
    {
        $type = $stmt instanceof Use_ || $stmt instanceof GroupUse ? $stmt->type : Use_::TYPE_NORMAL;

        return match ($type) {
            Use_::TYPE_FUNCTION => 2,
            Use_::TYPE_CONSTANT => 3,
            default => 1,
        };
    }

    private function firstUseKey(Stmt $stmt): string
    {
        if ($stmt instanceof Use_ || $stmt instanceof GroupUse) {
            $first = $stmt->uses[0] ?? null;
            if ($first instanceof UseItem) {
                return $this->useUseKey($first);
            }
        }

        return '';
    }
}
