<?php

namespace Koba\FilterBuilder\Core\ObjectStrategy;

use Closure;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;

/**
 * @template TObject of object
 */
class ObjectBoundFilter implements BoundFilterInterface
{
    /**
     * @param class-string<TObject> $type
     * @param Closure(TObject):bool $adheresFn
     */
    public function __construct(protected $type, private $adheresFn) {}

    /**
     * @param TObject $object
     */
    public function adheres($object): bool
    {
        return ($this->adheresFn)($object);
    }
}
