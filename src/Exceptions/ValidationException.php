<?php

namespace Koba\FilterBuilder\Core\Exceptions;

class ValidationException extends FilterBuilderException
{
    /**
     * @var string[] $messages
     */
    private array $messages;

    private final function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * @param string|string[] $messages
     */
    public static function make(string|array $messages): static
    {
        $messages = is_array($messages) ? $messages : [$messages];
        $message = implode('. ', $messages) . '.';

        $exception = new static($message);
        $exception->messages = $messages;
        return $exception;
    }

    /**
     * Get the validation errors.
     * 
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
