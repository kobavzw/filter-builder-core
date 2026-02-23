<?php

namespace Koba\FilterBuilder\Core\Contracts;

use Closure;
use Koba\FilterBuilder\Core\Configuration\Configuration;

/**
 * @template TBoundFilter of BoundFilterInterface
 * @template TStrategy of StrategyInterface<TBoundFilter>
 */
interface BoundFilterFactoryInterface
{
    /**
     * @param Configuration<TBoundFilter,TStrategy> $configuration
     */
    public function __construct(Configuration $configuration);

    /**
     * @param array<mixed> $input 
     * @param Closure(string):void $fail
     * @return TBoundFilter[]
     */
    public function makeChildren(array $input, $fail): array;

    /**
     * @param array<mixed> $input
     * @param Closure(string):void $fail
     * @return null|TBoundFilter
     */
    public function makeGroup(array $input, $fail);

    /**
     * @param array<mixed> $input
     * @param ConfigurationEntryInterface<TBoundFilter> $entry
     * @param Closure(string):void $fail
     * @return TBoundFilter|null
     */
    public function makeForConfigurationEntry(
        ConfigurationEntryInterface $entry,
        array $input,
        $fail,
    );
}
