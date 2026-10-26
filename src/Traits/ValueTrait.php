<?php

declare(strict_types=1);

namespace tommyknocker\chain\Traits;

use tommyknocker\chain\Exception\ChainInvalidOperationException;

trait ValueTrait
{
    /** Execute a side effect with the current value without changing state. */
    public function inspect(callable $callback): self
    {
        $callback($this->currentValue());

        return $this;
    }

    /** Filter items from the current iterable value while preserving their keys. */
    public function filter(callable $predicate): self
    {
        $value = $this->currentValue();
        if (!is_iterable($value)) {
            throw new ChainInvalidOperationException('filter() requires an iterable current value.');
        }

        $filtered = [];
        foreach ($value as $key => $item) {
            if ($predicate($item, $key)) {
                $filtered[$key] = $item;
            }
        }

        $this->setResult($filtered);

        return $this;
    }

    /** Map every item to an iterable and flatten the resulting items into an array. */
    public function flatMap(callable $mapper): self
    {
        $value = $this->currentValue();
        if (!is_iterable($value)) {
            throw new ChainInvalidOperationException('flatMap() requires an iterable current value.');
        }

        $flattened = [];
        foreach ($value as $key => $item) {
            $mapped = $mapper($item, $key);
            if (!is_iterable($mapped)) {
                throw new ChainInvalidOperationException('flatMap() mapper must return an iterable.');
            }
            foreach ($mapped as $mappedItem) {
                $flattened[] = $mappedItem;
            }
        }

        $this->setResult($flattened);

        return $this;
    }

    /** Execute a value callback and replace a thrown error with the handler result. */
    public function recover(callable $callback, callable $handler): self
    {
        try {
            $result = $callback($this->currentValue());
        } catch (\Throwable $error) {
            $result = $handler($error);
        }

        $this->setResult($result);

        return $this;
    }
}
