<?php

namespace App;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use LogicException;

final class Collection implements  Iterator, ArrayAccess, Countable, JsonSerializable
{

    private array $items;
    private array $operations = [];

    private int $position = 0;
    private ?array $evaluated = null;

    public function __construct(iterable $items)
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items);
    }

    public function current(): mixed
    {
        return $this->evaluate()[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->evaluate());
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->evaluate()[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->evaluate()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Collection is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Collection is immutable');
    }

    public function count(): int
    {
        return count($this->evaluate());
    }

    public function jsonSerialize(): mixed
    {
        return $this->evaluate();
    }

    private function evaluate(): array
    {
        if ($this->evaluated !== null) {
            return $this->evaluated;
        }

        $result = $this->items;

        foreach ($this->operations as $operation) {
            $result = $operation($result);
        }

        return $this->evaluated = array_values($result);
    }

    public function filter(callable $callback): self
    {
        $clone = clone $this;

        $clone->operations[] = function (array $items) use ($callback) {
            return array_filter($items, $callback);
        };

        return $clone;
    }

    public function map(callable $callback): self
    {
        $clone = clone $this;

        $clone->operations[] = function (array $items) use ($callback) {
            return array_map($callback, $items);
        };

        return $clone;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce(
            $this->evaluate(),
            $callback,
            $initial
        );
    }

    public function where(string $field, string $operator, mixed $value): self
    {
        $clone = clone $this;

        $clone->operations[] = function (array $items) use ($field, $operator, $value) {
            return array_filter($items, function ($item) use ($field, $operator, $value) {
                $current = $this->getFieldValue($item, $field);

                return match ($operator) {
                    '=', '==' => $current == $value,
                    '!='      => $current != $value,
                    '>'       => $current > $value,
                    '>='      => $current >= $value,
                    '<'       => $current < $value,
                    '<='      => $current <= $value,
                    'in'      => in_array($current, (array)$value, true),
                    default   => throw new InvalidArgumentException("Unknown operator {$operator}")
                };
            });
        };

        return $clone;
    }

    private function getFieldValue(mixed $item, string $field): mixed
    {
        $parts = explode('.', $field);
        $value = $item;

        foreach ($parts as $part) {
            if (is_array($value)) {
                $value = $value[$part] ?? null;
            } elseif (is_object($value)) {
                $value = $value->$part ?? null;
            } else {
                return null;
            }
        }

        return $value;
    }

    public function toArray(): array
    {
        return $this->evaluate();
    }
}