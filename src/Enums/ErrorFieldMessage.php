<?php

namespace Koba\FilterBuilder\Core\Enums;

enum ErrorFieldMessage
{
    case EMPTY_ARRAY;
    case EMPTY_RELATION;
    case EMPTY_VALUE;
    case INVALID_OPERATION;
    case INVALID_RULE;
    case INVALID_VALUE;
    case MISSING_CONFIGURATION_ENTRY;
    case UNSUPPORTED_OPERATION;
}
