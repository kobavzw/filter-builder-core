<?php

namespace Koba\FilterBuilder\Core\Enums;

enum ConstraintType
{
    case STRING;
    case NUMBER;
    case DROPDOWN;

    /**
     * @return Operation[]
     */
    public function getOperations(): array
    {
        return match ($this) {
            self::STRING => [
                Operation::EQUALS,
                Operation::STARTS_WITH,
                Operation::ONE_OF,
            ],
            self::NUMBER => [
                Operation::EQUALS,
                Operation::GREATER_THAN,
                Operation::LESS_THAN,
                Operation::ONE_OF,
            ],
            self::DROPDOWN => [
                Operation::EQUALS,
                Operation::ONE_OF,
            ],
        };
    }
}
