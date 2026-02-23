<?php

namespace Koba\FilterBuilder\Core\Factories;

use Closure;
use Koba\FilterBuilder\Core\Configuration\Configuration;
use Koba\FilterBuilder\Core\Configuration\RelationConfigurationEntry;
use Koba\FilterBuilder\Core\Configuration\RuleConfigurationEntry;
use Koba\FilterBuilder\Core\Contracts\BoundFilterFactoryInterface;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;
use Koba\FilterBuilder\Core\Contracts\ConfigurationEntryInterface;
use Koba\FilterBuilder\Core\Contracts\StrategyInterface;
use Koba\FilterBuilder\Core\Enums\ConstraintType;
use Koba\FilterBuilder\Core\Enums\ErrorMessage;
use Koba\FilterBuilder\Core\Enums\ErrorFieldMessage;
use Koba\FilterBuilder\Core\Enums\GroupType;
use Koba\FilterBuilder\Core\Enums\Operation;
use Koba\FilterBuilder\Core\Exceptions\FilterBuilderException;
use ValueError;

/**
 * @template TBoundFilter of BoundFilterInterface
 * @template TStrategy of StrategyInterface<TBoundFilter>
 * @implements BoundFilterFactoryInterface<TBoundFilter,TStrategy>
 */
class BoundFilterFactory implements BoundFilterFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(private Configuration $configuration) {}

    /**
     * @inheritDoc
     */
    public function makeChildren(array $input, $fail): array
    {
        $children = [];
        foreach ($input as $childInput) {
            if (is_array($childInput)) {
                $boundFilter = null;
                if (
                    array_key_exists('type', $childInput)
                    && $childInput['type'] === 'group'
                ) {
                    $boundFilter = $this->makeGroup($childInput, $fail);
                } else if (
                    array_key_exists('name', $childInput)
                    && is_string($childInput['name'])
                ) {
                    try {
                        $entry = $this->configuration->get($childInput['name']);
                        $boundFilter = $this->makeForConfigurationEntry($entry, $childInput, $fail);
                    } catch (FilterBuilderException $e) {
                        $fail($e->getMessage());
                    }
                }

                if ($boundFilter !== null) {
                    $children[] = $boundFilter;
                }
            }
        }

        return $children;
    }

    /**
     * @inheritDoc
     */
    public function makeGroup(array $input, $fail)
    {
        $failError = fn(ErrorMessage $error) => $fail($this->configuration->getTranslation()->translateError($error));

        if (
            array_key_exists('type', $input)
            && array_key_exists('operation', $input)
            && array_key_exists('children', $input)
            && $input['type'] === "group"
            && is_array($input['children'])
            && is_string($input['operation'])
        ) {
            try {
                $type = GroupType::from($input['operation']);
            } catch (ValueError) {
                $failError(ErrorMessage::INVALID_GROUP);
                return null;
            }

            return $this->configuration->getStrategy()->makeGroupBoundFilter(
                $type,
                $this->makeChildren($input['children'], $fail)
            );
        }

        $failError(ErrorMessage::INVALID_GROUP);
        return null;
    }

    /**
     * @inheritDoc
     */
    public function makeForConfigurationEntry(
        ConfigurationEntryInterface $entry,
        array $input,
        $fail,
    ) {
        $failField = function (ErrorFieldMessage|string $error) use ($fail, $entry) {
            if ($error instanceof ErrorFieldMessage) {
                $error = $this->configuration
                    ->getTranslation()
                    ->translateErrorWithField($error, $entry->getName());
            }

            $fail($error);
        };

        if ($entry instanceof RuleConfigurationEntry) {
            return $this->makeRule($entry, $input, $failField);
        } else if ($entry instanceof RelationConfigurationEntry) {
            return $this->makeRelation($entry, $input, $failField);
        } else {
            return null;
        }
    }



    /**
     * @param RuleConfigurationEntry<TBoundFilter> $entry
     * @param array<mixed> $input
     * @param Closure(ErrorFieldMessage|string):void $fail
     * @return TBoundFilter|null
     */
    private function makeRule(RuleConfigurationEntry $entry, array $input, Closure $fail)
    {
        if (
            array_key_exists('operation', $input)
            && array_key_exists('value', $input)
            && is_string($input['operation'])
        ) {
            try {
                $operation = Operation::from($input['operation']);
            } catch (ValueError) {
                $fail(ErrorFieldMessage::INVALID_OPERATION);
                return null;
            }

            if (false === $entry->isOperationSupported($operation)) {
                $fail(ErrorFieldMessage::UNSUPPORTED_OPERATION);
            }

            $entry->performExtraValidation($input['value'], $fail);

            if (null === $input['value']) {
                $fail(ErrorFieldMessage::EMPTY_VALUE);
            }

            switch ($entry->getType()) {
                case ConstraintType::STRING:
                    if ($operation === Operation::ONE_OF) {
                        if ($this->isValueArray($input['value'])) {
                            return $entry->bind($operation, $input['value']);
                        } else {
                            $fail(ErrorFieldMessage::INVALID_VALUE);
                        }
                    } else {
                        if (is_string($input['value'])) {
                            return $entry->bind($operation, $input['value']);
                        } else {
                            $fail(ErrorFieldMessage::INVALID_VALUE);
                        }
                    }
                    break;
                case ConstraintType::NUMBER:
                    if ($operation === Operation::ONE_OF) {
                        if ($this->isValueArray($input['value'])) {
                            return $entry->bind($operation, $input['value']);
                        } else {
                            $fail(ErrorFieldMessage::INVALID_VALUE);
                        }
                    } else {
                        if (is_int($input['value']) || is_float($input['value'])) {
                            return $entry->bind($operation, $input['value']);
                        } else {
                            $fail(ErrorFieldMessage::INVALID_VALUE);
                        }
                    }
                    break;
                case ConstraintType::DROPDOWN:
                    if ($operation === Operation::ONE_OF) {
                        if ($this->isValueArray($input['value'])) {
                            if (count($input['value']) > 0) {
                                return $entry->bind($operation, $input['value']);
                            } else {
                                $fail(ErrorFieldMessage::EMPTY_ARRAY);
                            }
                        } else {
                            $fail(ErrorFieldMessage::INVALID_VALUE);
                        }
                    } else {
                        if (
                            is_int($input['value'])
                            || is_float($input['value'])
                            || is_string($input['value'])
                        ) {
                            return $entry->bind($operation, $input['value']);
                        } else {
                            $fail(ErrorFieldMessage::INVALID_VALUE);
                        }
                    }
                    break;
            }

            return null;
        }

        $fail(ErrorFieldMessage::INVALID_RULE);
        return null;
    }

    /**
     * @template TRelatedBoundFilter of BoundFilterInterface
     * @template TRelatedStrategy of StrategyInterface<TRelatedBoundFilter>
     * 
     * @param RelationConfigurationEntry<TBoundFilter,TRelatedBoundFilter,TRelatedStrategy> $entry
     * @param array<mixed> $input
     * @param Closure(ErrorFieldMessage|string):void $fail
     * @return TBoundFilter|null
     */
    private function makeRelation(RelationConfigurationEntry $entry, array $input, $fail)
    {
        if (
            array_key_exists('operation', $input)
            && array_key_exists('children', $input)
            && is_string($input['operation'])
            && is_array($input['children'])
        ) {
            try {
                $operation = GroupType::from($input['operation']);
            } catch (ValueError) {
                $fail(ErrorFieldMessage::INVALID_OPERATION);
                return null;
            }

            if (empty($input['children'])) {
                $fail(ErrorFieldMessage::EMPTY_RELATION);
                return null;
            }

            $children = $entry->getConfiguration()
                ->getBoundFilterFactory()
                ->makeChildren($input['children'], $fail);

            return $entry->bind($operation, $children);
        }

        $fail(ErrorFieldMessage::INVALID_RULE);
        return null;
    }

    /**
     * @phpstan-assert (int|string|float)[] $input
     */
    private function isValueArray(mixed $input): bool
    {
        if (false === is_array($input)) {
            return false;
        }

        foreach ($input as $value) {
            if (
                false === is_string($value)
                && false === is_int($value)
                && false === is_float($value)
            ) {
                return false;
            }
        }

        return true;
    }
}
