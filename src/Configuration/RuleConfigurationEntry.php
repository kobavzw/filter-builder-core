<?php

namespace Koba\FilterBuilder\Core\Configuration;

use Closure;
use Exception;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;
use Koba\FilterBuilder\Core\Contracts\ConfigurationEntryInterface;
use Koba\FilterBuilder\Core\Enums\ConstraintType;
use Koba\FilterBuilder\Core\Enums\Operation;

/**
 * @template TBoundFilter of BoundFilterInterface
 * @implements ConfigurationEntryInterface<TBoundFilter>
 */
class RuleConfigurationEntry implements ConfigurationEntryInterface
{
    /**
     * @param Operation[] $supportedOperations
     * @param null|Closure(mixed,callable(string):void):void $extraValidation
     * @param Closure(Operation,int|string|float|(int|string|float)[]): TBoundFilter $boundFilterFn
     */
    public function __construct(
        protected string $name,
        protected ConstraintType $type,
        protected array $supportedOperations,
        protected $boundFilterFn,
        protected ?Closure $extraValidation = null
    ) {
        $typeOperations = $type->getOperations();
        foreach ($supportedOperations as $operation) {
            if (false === in_array($operation, $typeOperations)) {
                throw new Exception('Operatie niet toegelaten voor type');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the operation is supported by this configuration entry.
     */
    public function isOperationSupported(Operation $operation): bool
    {
        return in_array($operation, $this->supportedOperations);
    }

    /**
     * Perform extra validation, if any was set, on the value.
     * 
     * @param Closure(string):void $fail
     */
    public function performExtraValidation(mixed $value, $fail): void
    {
        if ($this->extraValidation !== null) {
            ($this->extraValidation)($value, $fail);
        }
    }

    /**
     * Retrieve the constraint type.
     */
    public function getType(): ConstraintType
    {
        return $this->type;
    }

    /**
     * Bind the given operation and value to the filter.
     * 
     * @param int|string|float|(int|string|float)[] $value
     * @return TBoundFilter
     */
    public function bind(Operation $operation, $value)
    {
        return ($this->boundFilterFn)($operation, $value);
    }
}
