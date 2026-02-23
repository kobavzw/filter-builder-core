<?php

namespace Koba\FilterBuilder\Core\Configuration;

use Closure;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;
use Koba\FilterBuilder\Core\Contracts\ConfigurationEntryInterface;
use Koba\FilterBuilder\Core\Contracts\StrategyInterface;
use Koba\FilterBuilder\Core\Enums\GroupType;

/**
 * @template TBoundFilter of BoundFilterInterface
 * @template TRelatedBoundFilter of BoundFilterInterface
 * @template TRelatedStrategy of StrategyInterface<TRelatedBoundFilter>
 * @implements ConfigurationEntryInterface<TBoundFilter>
 */
class RelationConfigurationEntry implements ConfigurationEntryInterface
{
    /**
     * @param Closure(GroupType,TRelatedBoundFilter[]):TBoundFilter $boundFilterFn
     * @param Configuration<TRelatedBoundFilter,TRelatedStrategy> $configuration
     */
    public function __construct(
        protected string $name,
        protected $boundFilterFn,
        protected Configuration $configuration
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param TRelatedBoundFilter[] $children
     * @return TBoundFilter
     */
    public function bind(GroupType $type, $children)
    {
        return ($this->boundFilterFn)($type, $children);
    }

    /**
     * @return Configuration<TRelatedBoundFilter,TRelatedStrategy>
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
