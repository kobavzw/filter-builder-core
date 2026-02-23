<?php

namespace Koba\FilterBuilder\Core\Configuration;

use Closure;
use Koba\FilterBuilder\Core\Contracts\BoundFilterFactoryInterface;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;
use Koba\FilterBuilder\Core\Contracts\ConfigurationEntryInterface;
use Koba\FilterBuilder\Core\Contracts\StrategyInterface;
use Koba\FilterBuilder\Core\Contracts\Translationinterface;
use Koba\FilterBuilder\Core\Enums\ConstraintType;
use Koba\FilterBuilder\Core\Enums\ErrorFieldMessage;
use Koba\FilterBuilder\Core\Enums\ErrorMessage;
use Koba\FilterBuilder\Core\Enums\GroupType;
use Koba\FilterBuilder\Core\Enums\Operation;
use Koba\FilterBuilder\Core\Exceptions\FilterBuilderException;
use Koba\FilterBuilder\Core\Exceptions\ValidationException;
use Koba\FilterBuilder\Core\Factories\BoundFilterFactory;
use Koba\FilterBuilder\Core\Translation\English;

/**
 * @template TBoundFilter of BoundFilterInterface
 * @template TStrategy of StrategyInterface<TBoundFilter>
 */
class Configuration
{
    /**
     * The configuration entries (rules).
     * 
     * @var array<string,ConfigurationEntryInterface<TBoundFilter>> $entries
     */
    private array $entries;

    /**
     * @var BoundFilterFactoryInterface<TBoundFilter,TStrategy> $boundFilterFactory
     */
    private BoundFilterFactoryInterface $boundFilterFactory;

    private Translationinterface $translation;

    /**
     * @param TStrategy $strategy
     * @param null|BoundFilterFactoryInterface<TBoundFilter,TStrategy> $boundFilterFactory
     */
    public function __construct(
        private $strategy,
        ?Translationinterface $translation = null,
        ?BoundFilterFactoryInterface $boundFilterFactory = null,
    ) {
        $this->boundFilterFactory = $boundFilterFactory ?? new BoundFilterFactory($this);
        $this->translation = $translation ?? new English;
    }

    /**
     * Controleer of de configuratie een entry bevat voor de opgegeven naam.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->entries);
    }

    /**
     * Geeft het configuratie-entry object terug voor de opgegeven naam.
     * 
     * @return ConfigurationEntryInterface<TBoundFilter>
     * @throws FilterBuilderException
     */
    public function get(string $name): ConfigurationEntryInterface
    {
        if ($this->has($name)) {
            return $this->entries[$name];
        }

        throw new FilterBuilderException(
            $this->getTranslation()->translateErrorWithField(
                ErrorFieldMessage::MISSING_CONFIGURATION_ENTRY,
                $name,
            )
        );
    }

    /** 
     * Voeg een nieuw entry toe.
     * 
     * @param Operation[] $supportedOperations
     * @param Closure(TStrategy):(Closure(Operation,int|string|float|(int|string|float)[]):TBoundFilter) $boundFilterFn
     * @param null|Closure(mixed,callable(string):void):void $extraValidation
     * @return $this
     */
    public function addRuleEntry(
        string $name,
        ConstraintType $type,
        array $supportedOperations,
        $boundFilterFn,
        ?Closure $extraValidation = null
    ): self {
        $this->entries[$name] = new RuleConfigurationEntry(
            $name,
            $type,
            $supportedOperations,
            $boundFilterFn($this->strategy),
            $extraValidation,
        );

        return $this;
    }

    /**
     * @template TRelatedBoundFilter of BoundFilterInterface
     * @template TRelatedStrategy of StrategyInterface<TRelatedBoundFilter>
     * 
     * @param Closure(TStrategy):(Closure(GroupType,TRelatedBoundFilter[]):TBoundFilter) $boundFilterFn
     * @param Configuration<TRelatedBoundFilter,TRelatedStrategy> $configuration
     * @return $this
     */
    public function addRelationEntry(
        string $name,
        Closure $boundFilterFn,
        Configuration $configuration,
    ): self {
        $this->entries[$name] = new RelationConfigurationEntry(
            $name,
            $boundFilterFn($this->strategy),
            $configuration,
        );

        return $this;
    }

    /**
     * @return BoundFilterFactoryInterface<TBoundFilter,TStrategy>
     */
    public function getBoundFilterFactory()
    {
        return $this->boundFilterFactory;
    }

    /**
     * @return TStrategy
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * Retrieves the translation object.
     */
    public function getTranslation(): Translationinterface
    {
        return $this->translation;
    }

    /**
     * @param array<mixed> $input
     * @return TBoundFilter
     */
    public function getFilter(array $input)
    {
        $errors = [];
        $filter = $this->getBoundFilterFactory()->makeGroup(
            $input,
            function ($error) use (&$errors) {
                $errors[] = $error;
            }
        );

        if (count($errors) > 0) {
            throw ValidationException::make($errors);
        }

        if ($filter === null) {
            throw ValidationException::make(
                $this->getTranslation()->translateError(ErrorMessage::INVALID_CONFIGURATION)
            );
        }

        return $filter;
    }
}
