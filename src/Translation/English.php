<?php

namespace Koba\FilterBuilder\Core\Translation;

use Koba\FilterBuilder\Core\Contracts\Translationinterface;
use Koba\FilterBuilder\Core\Enums\ErrorMessage;
use Koba\FilterBuilder\Core\Enums\ErrorFieldMessage;

class English implements Translationinterface
{
    public function translateError(ErrorMessage $error): string
    {
        return match ($error) {
            ErrorMessage::INVALID_GROUP => 'Invalid group',
            ErrorMessage::INVALID_GROUP_OPERATION => 'Invalid operation for a group',
            ErrorMessage::INVALID_CONFIGURATION => 'Invalid configuration',
            ErrorMessage::INVALID_RULE => 'Invalid rule',
        };
    }

    public function translateErrorWithField(ErrorFieldMessage $error, string $field): string
    {
        return match ($error) {
            ErrorFieldMessage::EMPTY_ARRAY => "Field '{$field}' must contain values",
            ErrorFieldMessage::EMPTY_VALUE => "Value for '{$field}' cannot be empty",
            ErrorFieldMessage::EMPTY_RELATION => 'A relation must contain rules',
            ErrorFieldMessage::INVALID_RULE => "Invalid rule for field '{$field}'",
            ErrorFieldMessage::INVALID_VALUE => "Invalid value for '{$field}'",
            ErrorFieldMessage::MISSING_CONFIGURATION_ENTRY => "Configuration doesn't contain an entry with name '{$field}'",
            ErrorFieldMessage::UNSUPPORTED_OPERATION => "Operation is not supported for '{$field}'",
            ErrorFieldMessage::INVALID_OPERATION => "Invalid operation for '{$field}'",
        };
    }
}
