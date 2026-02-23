# Filter Builder Core

A flexible, type-safe PHP library for creating and applying filters to objects. Filter Builder Core allows you to define filterable fields, parse filter criteria from arrays (typically JSON/API requests), validate them, and execute complex filtering logic against your domain objects.

## Features

- **Type-Safe Filtering**: Built with PHP generics
- **Complex Logic Groups**: Support for nested AND/OR operations
- **Multiple Data Types**: Strings, numbers, and dropdown values
- **Relation Support**: Filter based on related objects with nested configurations
- **Comprehensive Validation**: Type checking, operation validation, and custom validation hooks
- **Internationalization**: Built-in English and Dutch translations with pluggable translation system
- **Extensible Architecture**: Strategy pattern allows custom implementations

## Installation

Install via Composer:

```bash
composer require koba/filter-builder-core
```

## Quick Start

```php
<?php

use Koba\FilterBuilder\Core\Configuration\Configuration;
use Koba\FilterBuilder\Core\Enums\ConstraintType;
use Koba\FilterBuilder\Core\Enums\Operation;
use Koba\FilterBuilder\Core\ObjectStrategy\ObjectStrategy;

// Define your class
class Product
{
    public function __construct(
        public string $name,
        public float $price,
        public string $category,
    ) {}
}

// Create a product instance
$product = new Product("Laptop", 999.99, "Electronics");

// Define filter criteria (typically from API request)
$filterArray = [
    'type' => 'group',
    'operation' => 'and',
    'children' => [
        ['name' => 'name', 'operation' => 'starts_with', 'value' => 'Lap'],
        ['name' => 'price', 'operation' => 'less_than', 'value' => 1000],
    ]
];

// Configure filterable fields
$strategy = new ObjectStrategy(Product::class);
$configuration = (new Configuration($strategy))
    ->addRuleEntry(
        'name',
        ConstraintType::STRING,
        [Operation::EQUALS, Operation::STARTS_WITH],
        fn($factory) => $factory->makeRule(fn($obj) => $obj->name)
    )
    ->addRuleEntry(
        'price',
        ConstraintType::NUMBER,
        [Operation::LESS_THAN, Operation::GREATER_THAN, Operation::EQUALS],
        fn($factory) => $factory->makeRule(fn($obj) => $obj->price)
    )
    ->addRuleEntry(
        'category',
        ConstraintType::DROPDOWN,
        [Operation::EQUALS, Operation::ONE_OF],
        fn($factory) => $factory->makeRule(fn($obj) => $obj->category)
    );

// Get the filter and check if object adheres
$filter = $configuration->getFilter($filterArray);

if ($filter->adheres($product)) {
    echo "Product matches the filter!";
}
```

## Core Concepts

### Configuration

The `Configuration` class is the central point for defining what can be filtered. It manages rules and relations.

```php
$configuration = new Configuration($strategy);
```

You can optionally provide a translation object and custom bound filter factory:

```php
use Koba\FilterBuilder\Core\Translation\Dutch;

$configuration = new Configuration(
    $strategy,
    new Dutch(), // Translation
    null // Custom BoundFilterFactory (optional)
);
```

### Constraint Types

Filter Builder Core supports three constraint types:

- **`ConstraintType::STRING`**: For string values
  - Supported operations: `EQUALS`, `STARTS_WITH`, `ONE_OF`

- **`ConstraintType::NUMBER`**: For numeric values (int/float)
  - Supported operations: `EQUALS`, `GREATER_THAN`, `LESS_THAN`, `ONE_OF`

- **`ConstraintType::DROPDOWN`**: For predefined values
  - Supported operations: `EQUALS`, `ONE_OF`

### Operations

Available filter operations:

- **`Operation::EQUALS`**: Exact match
- **`Operation::STARTS_WITH`**: String starts with value (case-sensitive)
- **`Operation::LESS_THAN`**: Numeric less than comparison
- **`Operation::GREATER_THAN`**: Numeric greater than comparison
- **`Operation::ONE_OF`**: Value is in array of options

### Adding Rule Entries

Define filterable fields using `addRuleEntry()`:

```php
$configuration->addRuleEntry(
    'fieldName',                    // Field identifier
    ConstraintType::STRING,         // Data type
    [Operation::EQUALS],            // Supported operations
    fn($factory) => $factory->makeRule(
        fn($obj) => $obj->fieldName // Property accessor
    )
);
```

With custom validation:

```php
$configuration->addRuleEntry(
    'status',
    ConstraintType::DROPDOWN,
    [Operation::EQUALS, Operation::ONE_OF],
    fn($factory) => $factory->makeRule(fn($obj) => $obj->status),
    extraValidation: function($value, $addError) {
        $validStatuses = ['active', 'inactive', 'pending'];
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $v) {
            if (!in_array($v, $validStatuses)) {
                $addError("Invalid status: $v");
            }
        }
    }
);
```

### Adding Relation Entries

Filter based on related objects:

```php
class Order
{
    public function __construct(
        public int $id,
        public Customer $customer,
    ) {}
}

class Customer
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

// Configure customer filters
$customerStrategy = new ObjectStrategy(Customer::class);
$customerConfig = (new Configuration($customerStrategy))
    ->addRuleEntry(
        'name',
        ConstraintType::STRING,
        [Operation::EQUALS, Operation::STARTS_WITH],
        fn($factory) => $factory->makeRule(fn($customer) => $customer->name)
    );

// Configure order filters with customer relation
$orderStrategy = new ObjectStrategy(Order::class);
$orderConfig = (new Configuration($orderStrategy))
    ->addRuleEntry(
        'id',
        ConstraintType::NUMBER,
        [Operation::EQUALS],
        fn($factory) => $factory->makeRule(fn($order) => $order->id)
    )
    ->addRelationEntry(
        'customer',
        fn($factory) => $factory->makeRelation(
            fn($order) => $order->customer
        ),
        $customerConfig
    );

// Filter by customer name
$filterArray = [
    'type' => 'group',
    'operation' => 'and',
    'children' => [
        [
            'name' => 'customer',
            'operation' => 'and',
            'children' => [
                ['name' => 'name', 'operation' => 'equals', 'value' => 'John Doe']
            ]
        ]
    ]
];

$filter = $orderConfig->getFilter($filterArray);
```

## Filter Array Format

Filters are defined as arrays with a specific structure:

### Group Filter

```php
[
    'type' => 'group',
    'operation' => 'and', // or 'or'
    'children' => [
        // Child filters (rules or nested groups)
    ]
]
```

### Rule Filter

```php
[
    'name' => 'fieldName',
    'operation' => 'equals', // or other operations
    'value' => 'someValue'
]
```

For `ONE_OF` operations, provide an array:

```php
[
    'name' => 'category',
    'operation' => 'one_of',
    'value' => ['Electronics', 'Books', 'Clothing']
]
```

### Relation Filter

```php
[
    'name' => 'relationName',
    'operation' => 'and', // or 'or'
    'children' => [
        // Filters for related object
    ]
]
```

### Complex Example

```php
$filterArray = [
    'type' => 'group',
    'operation' => 'and',
    'children' => [
        [
            'type' => 'group',
            'operation' => 'or',
            'children' => [
                ['name' => 'category', 'operation' => 'equals', 'value' => 'Electronics'],
                ['name' => 'category', 'operation' => 'equals', 'value' => 'Books'],
            ]
        ],
        ['name' => 'price', 'operation' => 'less_than', 'value' => 100],
        ['name' => 'inStock', 'operation' => 'equals', 'value' => true],
    ]
];
```

This translates to: `(category = 'Electronics' OR category = 'Books') AND price < 100 AND inStock = true`

## Validation and Error Handling

The library provides comprehensive validation with detailed error messages.

### Validation Exception

When filter validation fails, a `ValidationException` is thrown:

```php
use Koba\FilterBuilder\Core\Exceptions\ValidationException;

try {
    $filter = $configuration->getFilter($invalidFilterArray);
} catch (ValidationException $e) {
    // Get all error messages
    $errors = $e->getMessages();

    foreach ($errors as $error) {
        echo $error . "\n";
    }
}
```

### Common Validation Errors

- Missing or invalid field names
- Unsupported operations for a field type
- Invalid value types
- Custom validation failures
- Invalid filter structure

## Internationalization

The library supports multiple languages for error messages.

### Using Built-in Translations

```php
use Koba\FilterBuilder\Core\Translation\English;
use Koba\FilterBuilder\Core\Translation\Dutch;

// English (default)
$configuration = new Configuration($strategy, new English());

// Dutch
$configuration = new Configuration($strategy, new Dutch());
```

### Creating Custom Translations

Implement the `Translationinterface`:

```php
use Koba\FilterBuilder\Core\Contracts\Translationinterface;
use Koba\FilterBuilder\Core\Enums\ErrorMessage;
use Koba\FilterBuilder\Core\Enums\ErrorFieldMessage;

class Spanish implements Translationinterface
{
    public function translateError(ErrorMessage $message): string
    {
        return match($message) {
            ErrorMessage::INVALID_CONFIGURATION => 'Configuración inválida',
            // ... other translations
        };
    }

    public function translateErrorWithField(
        ErrorFieldMessage $message,
        string $field
    ): string {
        return match($message) {
            ErrorFieldMessage::MISSING_CONFIGURATION_ENTRY =>
                "Falta la entrada de configuración: $field",
            // ... other translations
        };
    }
}

$configuration = new Configuration($strategy, new Spanish());
```

## Advanced Usage

### Working with Collections

Filter multiple objects:

```php
$products = [
    new Product("Laptop", 999.99, "Electronics"),
    new Product("Book", 19.99, "Books"),
    new Product("Phone", 599.99, "Electronics"),
];

$filter = $configuration->getFilter($filterArray);

$filtered = array_filter($products, fn($product) => $filter->adheres($product));
```

### Custom Bound Filter Factory

For advanced use cases, you can create a custom bound filter factory:

```php
use Koba\FilterBuilder\Core\Contracts\BoundFilterFactoryInterface;
use Koba\FilterBuilder\Core\Factories\BoundFilterFactory;

class CustomBoundFilterFactory extends BoundFilterFactory
{
    // Override methods as needed
}

$configuration = new Configuration(
    $strategy,
    null,
    new CustomBoundFilterFactory($configuration)
);
```

### Strategy Pattern

The library uses the strategy pattern for extensibility. Currently, `ObjectStrategy` is provided for filtering objects, but you can implement custom strategies:

```php
use Koba\FilterBuilder\Core\Contracts\StrategyInterface;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;

class DatabaseQueryStrategy implements StrategyInterface
{
    // Implement strategy for building database queries
}
```

## Development

### Static Analysis

The library uses PHPStan for static analysis:

```bash
vendor/bin/phpstan analyse
```

Configuration is in [phpstan.neon](phpstan.neon).

## Support

For issues, questions, or suggestions, please open an issue on the GitHub repository.
