<?php

declare(strict_types=1);

namespace Scalp\Utils;

use Scalp\Exception\NoSuchElementException;

final class Success extends TryCatch
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function isFailure(): bool
    {
        return false;
    }

    public function isSuccess(): bool
    {
        return true;
    }

    public function getOrElse($default)
    {
        return $this->value;
    }

    public function orElse(TryCatch $default): TryCatch
    {
        return $this;
    }

    public function get()
    {
        return $this->value;
    }

    public function foreach(callable $f): void
    {
        $f($this->value);
    }

    public function flatMap(callable $f): TryCatch
    {
        restrictCallableReturnType($f, TryCatch::class);

        try {
            return $f($this->value);
        } catch (\Throwable $error) {
            return Failure($error);
        }
    }

    public function map(callable $f): TryCatch
    {
        return TryCatch(Delayed($f, $this->value));
    }

    public function filter(callable $p): TryCatch
    {
        try {
            return ($p($this->value)) ? $this : Failure(new NoSuchElementException("Predicate does not hold for {$this->value}"));
        } catch (\Throwable $error) {
            return Failure($error);
        }
    }

    public function __toString(): string
    {
        return sprintf(
            'Success[%s](%s)',
            type($this->value),
            (string) $this->value
        );
    }
}
