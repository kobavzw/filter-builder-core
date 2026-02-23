<?php

namespace Koba\FilterBuilder\Core\Contracts;

use Koba\FilterBuilder\Core\Enums\GroupType;

/**
 * @template TBoundFilter of BoundFilterInterface
 */
interface StrategyInterface
{
    /**
     * @param TBoundFilter[] $children
     * @return TBoundFilter
     */
    public function makeGroupBoundFilter(GroupType $type, array $children);
}
