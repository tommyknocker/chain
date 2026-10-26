<?php

declare(strict_types=1);

namespace tommyknocker\chain;

use Psr\Container\ContainerInterface;

final class Flow
{
    /** @var list<mixed> */
    private array $steps;
    /** @var array<string, mixed> */
    private array $contextAttributes = [];
    private ?ContainerInterface $resolver = null;
    /** @var list<callable(FlowEvent): void> */
    private array $observers = [];

    private function __construct(mixed ...$steps)
    {
        $this->steps = array_values($steps);
    }

    public static function start(): self
    {
        return new self();
    }

    public static function of(mixed ...$steps): self
    {
        return new self(...$steps);
    }

    public static function stop(mixed $value): FlowStop
    {
        return new FlowStop($value);
    }

    public function then(mixed $step): self
    {
        $clone = clone $this;
        $clone->steps[] = $step;

        return $clone;
    }

    public function when(callable $predicate, self $flow): self
    {
        return $this->then(static function (mixed $payload, FlowContext $context) use ($predicate, $flow): mixed {
            if (!$predicate($payload, $context)) {
                return $payload;
            }

            $execution = $flow->executeNested($payload, $context);
            $failure = $execution->failure();

            if ($failure !== null) {
                throw $failure;
            }

            return $execution->stopped() ? self::stop($execution->result()) : $execution->result();
        });
    }

    public function withResolver(?ContainerInterface $resolver): self
    {
        $clone = clone $this;
        $clone->resolver = $resolver;

        return $clone;
    }

    /** @param array<string, mixed> $attributes */
    public function withContext(array $attributes): self
    {
        $clone = clone $this;
        $clone->contextAttributes = [...$clone->contextAttributes, ...$attributes];

        return $clone;
    }

    /** @param callable(FlowEvent): void $observer */
    public function onEvent(callable $observer): self
    {
        $clone = clone $this;
        $clone->observers[] = $observer;

        return $clone;
    }

    public function run(mixed $payload): mixed
    {
        return $this->execute($payload)->unwrap();
    }

    public function execute(mixed $payload): FlowExecution
    {
        return $this->executeNested($payload);
    }

    private function executeNested(mixed $payload, ?FlowContext $parentContext = null): FlowExecution
    {
        $context = new FlowContext(
            [...($parentContext?->attributes() ?? []), ...$this->contextAttributes],
            $this->resolver ?? $parentContext?->resolver(),
        );
        $trace = [];

        foreach ($this->steps as $index => $definition) {
            $name = $this->stepName($definition);
            $startedAt = microtime(true);
            $this->emit(new FlowEvent(FlowEventType::Started, $index, $name, 0));

            try {
                $result = $this->invoke($definition, $payload, $context);
            } catch (\Throwable $error) {
                $duration = microtime(true) - $startedAt;
                $failure = $error instanceof FlowFailedException ? $error : new FlowFailedException($index, $name, $error);
                $this->emit(new FlowEvent(FlowEventType::Failed, $index, $name, $duration, $failure));

                return new FlowExecution(null, $failure, false, $trace);
            }

            $duration = microtime(true) - $startedAt;
            $trace[] = new FlowStepResult($index, $name, $duration);
            if ($result instanceof FlowStop) {
                $this->emit(new FlowEvent(FlowEventType::Stopped, $index, $name, $duration));

                return new FlowExecution($result->value, null, true, $trace);
            }

            $payload = $result;
            $this->emit(new FlowEvent(FlowEventType::Completed, $index, $name, $duration));
        }

        return new FlowExecution($payload, null, false, $trace);
    }

    private function invoke(mixed $definition, mixed $payload, FlowContext $context): mixed
    {
        if ($definition instanceof self) {
            $execution = $definition->executeNested($payload, $context);
            $failure = $execution->failure();

            if ($failure !== null) {
                throw $failure;
            }

            return $execution->stopped() ? self::stop($execution->result()) : $execution->result();
        }
        if (is_string($definition) && class_exists($definition)) {
            $definition = $this->resolve($definition, $context->resolver());
        }
        if ($definition instanceof StepInterface) {
            return $definition->handle($payload, $context);
        }
        if (is_callable($definition)) {
            return $definition($payload, $context);
        }

        throw new FlowResolutionException(sprintf('Flow step %s is not a StepInterface, class-string, callable, or Flow.', get_debug_type($definition)));
    }

    private function resolve(string $class, ?ContainerInterface $resolver): object
    {
        if ($resolver !== null && $resolver->has($class)) {
            return $resolver->get($class);
        }

        try {
            return new $class();
        } catch (\Throwable $error) {
            throw new FlowResolutionException(sprintf('Could not resolve flow step %s.', $class), 0, $error);
        }
    }

    private function stepName(mixed $definition): string
    {
        return match (true) {
            is_string($definition) => $definition,
            $definition instanceof self => self::class,
            is_object($definition) => $definition::class,
            default => 'callable',
        };
    }

    private function emit(FlowEvent $event): void
    {
        foreach ($this->observers as $observer) {
            try {
                $observer($event);
            } catch (\Throwable) {
            }
        }
    }
}
