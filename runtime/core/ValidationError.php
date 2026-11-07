<?php
declare(strict_types=1);

namespace SiteRuntime;

/** Invalid input. $errors maps field names (or paths like "items.0.title") to messages. Safe to show. */
class ValidationError extends \RuntimeException
{
    public function __construct(string $message, public array $errors = [], public array $extra = [])
    {
        parent::__construct($message);
    }
}
