<?php

namespace Koba\FilterBuilder\Core\Translation;

use Koba\FilterBuilder\Core\Contracts\Translationinterface;
use Koba\FilterBuilder\Core\Enums\ErrorMessage;
use Koba\FilterBuilder\Core\Enums\ErrorFieldMessage;

class Dutch implements Translationinterface
{
    public function translateError(ErrorMessage $error): string
    {
        return match ($error) {
            ErrorMessage::INVALID_GROUP => 'Ongeldige groep',
            ErrorMessage::INVALID_GROUP_OPERATION => 'Ongeldige operatie voor een groep',
            ErrorMessage::INVALID_CONFIGURATION => 'Ongeldige configuratie',
            ErrorMessage::INVALID_RULE => 'Ongeldige regel',
        };
    }

    public function translateErrorWithField(ErrorFieldMessage $error, string $field): string
    {
        return match ($error) {
            ErrorFieldMessage::EMPTY_ARRAY => "Het veld '{$field}' moet waarden bevatten",
            ErrorFieldMessage::EMPTY_VALUE => "De waarde voor '{$field}' mag niet leeg zijn",
            ErrorFieldMessage::EMPTY_RELATION => 'Een relatie moet regels bevatten',
            ErrorFieldMessage::INVALID_RULE => "Ongeldige regel voor '{$field}'",
            ErrorFieldMessage::INVALID_VALUE => "Ongeldige waarde voor '{$field}'",
            ErrorFieldMessage::MISSING_CONFIGURATION_ENTRY => "De configuratie bevat geen regel voor '{$field}'",
            ErrorFieldMessage::UNSUPPORTED_OPERATION => "De operatie voor '{$field}' wordt niet ondersteund",
            ErrorFieldMessage::INVALID_OPERATION => "Ongeldige operatie voor '{$field}'",
        };
    }
}
