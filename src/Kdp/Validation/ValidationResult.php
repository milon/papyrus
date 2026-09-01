<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp\Validation;

final class ValidationResult
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $errors = [],
        public readonly array $warnings = [],
    ) {}

    public function message(): string
    {
        return implode('; ', [...$this->errors, ...$this->warnings]);
    }
}
