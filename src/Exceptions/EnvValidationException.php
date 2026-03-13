<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator\Exceptions;

use PhilipRehberger\EnvValidator\ValidationResult;
use RuntimeException;

class EnvValidationException extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly ValidationResult $result,
    ) {
        parent::__construct($message);
    }

    public static function fromResult(ValidationResult $result): self
    {
        $messages = [];

        if (! empty($result->missing)) {
            $messages[] = 'Missing: '.implode(', ', $result->missing);
        }

        if (! empty($result->invalid)) {
            $messages[] = 'Invalid: '.implode('; ', $result->invalid);
        }

        return new self('Environment validation failed. '.implode('. ', $messages), $result);
    }
}
