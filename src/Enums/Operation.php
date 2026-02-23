<?php

namespace Koba\FilterBuilder\Core\Enums;

enum Operation: string
{
    case ONE_OF = 'one_of';
    case EQUALS = 'equals';
    case STARTS_WITH = 'starts_with';
    case LESS_THAN = 'less_than';
    case GREATER_THAN = 'greater_than';
}
