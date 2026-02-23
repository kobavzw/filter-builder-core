<?php

namespace Koba\FilterBuilder\Core\Contracts;

/**
 * @template TBoundFilter of BoundFilterInterface
 */
interface ConfigurationEntryInterface
{
    /**
     * De naam die die configuratie identificeert.
     */
    public function getName(): string;
}
