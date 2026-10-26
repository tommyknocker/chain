<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use ReflectionClass;
use ReflectionProperty;
use SplObjectStorage;
use tommyknocker\chain\Exception\ChainInvalidOperationException;

/** @internal */
final class DeepCloner
{
    public static function clone(object $value): object
    {
        return self::cloneValue($value, new SplObjectStorage());
    }

    /** @param SplObjectStorage<object, object> $clones */
    private static function cloneValue(mixed $value, SplObjectStorage $clones): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::cloneValue($item, $clones);
            }

            return $value;
        }

        if (!is_object($value)) {
            return $value;
        }

        if ($clones->offsetExists($value)) {
            return $clones[$value];
        }

        $reflection = new ReflectionClass($value);
        if (!$reflection->isCloneable()) {
            throw new ChainInvalidOperationException(sprintf('Cannot deep-clone %s.', $reflection->getName()));
        }

        $clone = clone $value;
        $clones[$value] = $clone;

        foreach (get_object_vars($value) as $name => $propertyValue) {
            if (!$reflection->hasProperty($name)) {
                $clone->{$name} = self::cloneValue($propertyValue, $clones);
            }
        }

        do {
            foreach ($reflection->getProperties() as $property) {
                if ($property->isStatic() || !$property->isInitialized($value)) {
                    continue;
                }

                self::cloneProperty($property, $value, $clone, $clones);
            }
        } while ($reflection = $reflection->getParentClass());

        return $clone;
    }

    /** @param SplObjectStorage<object, object> $clones */
    private static function cloneProperty(
        ReflectionProperty $property,
        object $source,
        object $target,
        SplObjectStorage $clones
    ): void {
        $originalValue = $property->getValue($source);
        $clonedValue = self::cloneValue($originalValue, $clones);

        if ($originalValue === $clonedValue) {
            return;
        }
        if ($property->isReadOnly()) {
            throw new ChainInvalidOperationException(sprintf(
                'Cannot deep-clone readonly property %s::$%s.',
                $property->getDeclaringClass()->getName(),
                $property->getName()
            ));
        }

        $property->setValue($target, $clonedValue);
    }
}
