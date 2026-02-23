<?php

namespace Koba\FilterBuilder\Core\ObjectStrategy;

use Closure;
use Koba\FilterBuilder\Core\Contracts\StrategyInterface;
use Koba\FilterBuilder\Core\Enums\GroupType;
use Koba\FilterBuilder\Core\Enums\Operation;

/**
 * @template TObject of object
 * @implements StrategyInterface<ObjectBoundFilter<TObject>>
 */
class ObjectStrategy implements StrategyInterface
{
    /**
     * @param class-string<TObject> $type
     */
    public function __construct(private $type) {}

    public function makeGroupBoundFilter(GroupType $type, array $children)
    {
        return new ObjectBoundFilter(
            $this->type,
            function ($object) use ($type, $children) {
                return $this->childrenAdhere($children, $object, $type === GroupType::OR);
            }
        );
    }

    /**
     * @template TCheckObject of object
     * @param ObjectBoundFilter<TCheckObject>[] $filters
     * @param TCheckObject $object
     */
    private function childrenAdhere(array $filters, $object, bool $any): bool
    {
        foreach ($filters as $filter) {
            if ($any === $filter->adheres($object)) {
                return $any;
            }
        }

        return !$any;
    }

    /**
     * @param Closure(TObject):(int|string|float|(int|string|float)[]) $getValue
     * @return Closure(Operation,int|string|float|(int|string|float)[]):ObjectBoundFilter<TObject>
     */
    public function makeRule($getValue)
    {
        return fn(Operation $operation, $value) => new ObjectBoundFilter(
            $this->type,
            function ($object) use ($operation, $value, $getValue) {
                $constraintValue = $getValue($object);

                return match ($operation) {
                    Operation::EQUALS => $constraintValue === $value,
                    Operation::ONE_OF => in_array($constraintValue, $value, true),
                    Operation::STARTS_WITH => is_string($constraintValue) && str_starts_with($constraintValue, $value),
                    Operation::LESS_THAN => $constraintValue < $value,
                    Operation::GREATER_THAN => $constraintValue > $value,
                };
            }
        );
    }

    /**
     * @template TRelated of object
     * @param class-string<TRelated> $type
     * @param Closure(TObject):TRelated[] $getRelated
     * @return Closure(GroupType,ObjectBoundFilter<TRelated>[]):ObjectBoundFilter<TObject>
     */
    public function makeRelation(string $type, $getRelated)
    {
        return fn(GroupType $type, array $children) => new ObjectBoundFilter(
            $this->type,
            function ($object) use ($type, $children, $getRelated) {
                $relatedObjects = $getRelated($object);
                foreach ($relatedObjects as $relatedObject) {
                    if($this->childrenAdhere($children, $relatedObject, $type === GroupType::OR)) {
                        return true;
                    }
                }

                return false;
            }
        );
    }
}
