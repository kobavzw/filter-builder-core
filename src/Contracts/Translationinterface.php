<?php

namespace Koba\FilterBuilder\Core\Contracts;

use Koba\FilterBuilder\Core\Enums\ErrorMessage;
use Koba\FilterBuilder\Core\Enums\ErrorFieldMessage;

interface Translationinterface
{
    /**
     * Provide an error message.
     */
    public function translateError(ErrorMessage $error): string;

    /**
     * Provide an error message for an error that contains a specific fieldname.
     */
    public function translateErrorWithField(ErrorFieldMessage $error, string $field): string;
}
