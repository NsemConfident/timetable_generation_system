<?php

declare(strict_types=1);

namespace Utils;

/**
 * Input validation utility.
 */
class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? null;
            if ($value === null || $value === '') {
                $this->errors[$field][] = "Field {$field} is required.";
            }
        }
        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Field {$field} must be a valid email.";
        }
        return $this;
    }

    public function minLength(string $field, int $min): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && strlen((string) $value) < $min) {
            $this->errors[$field][] = "Field {$field} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && strlen((string) $value) > $max) {
            $this->errors[$field][] = "Field {$field} must not exceed {$max} characters.";
        }
        return $this;
    }

    public function integer(string $field, ?int $min = null, ?int $max = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '') {
            return $this;
        }
        if (!is_numeric($value) || (int) $value != $value) {
            $this->errors[$field][] = "Field {$field} must be an integer.";
            return $this;
        }
        $intVal = (int) $value;
        if ($min !== null && $intVal < $min) {
            $this->errors[$field][] = "Field {$field} must be at least {$min}.";
        }
        if ($max !== null && $intVal > $max) {
            $this->errors[$field][] = "Field {$field} must not exceed {$max}.";
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "Field {$field} must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    public function unique(string $field, callable $checkExists): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && $checkExists($value)) {
            $this->errors[$field][] = "Field {$field} already exists.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFlatErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $field => $messages) {
            foreach ($messages as $msg) {
                $flat[] = $msg;
            }
        }
        return $flat;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
