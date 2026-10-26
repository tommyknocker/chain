<?php

declare(strict_types=1);

namespace tommyknocker\chain\Traits;

use tommyknocker\chain\Exception\ChainTimeoutException;

/**
 * Trait providing error handling and resilience functionality.
 */
trait ErrorHandlingTrait
{
    /**
     * Execute callback with exception handling.
     *
     * @param callable $callback Callback to execute (receives current instance)
     * @param callable $handler Exception handler that receives the exception and returns a fallback value
     */
    public function rescue(callable $callback, callable $handler): self
    {
        try {
            $result = $callback($this->instance);

            $this->setResult($result);
        } catch (\Throwable $e) {
            $result = $handler($e);

            $this->setResult($result);
        }

        return $this;
    }

    /**
     * Catch specific exception types.
     *
     * @param string $exceptionClass Exception class name
     * @param callable $callback Callback to execute
     * @param callable $handler Exception handler
     */
    public function catch(string $exceptionClass, callable $callback, callable $handler): self
    {
        return $this->rescue($callback, function (\Throwable $e) use ($exceptionClass, $handler) {
            if ($e instanceof $exceptionClass) {
                return $handler($e);
            }

            throw $e;
        });
    }

    /**
     * Execute a callback with retry logic.
     *
     * @param int $times Number of retry attempts
     * @param callable $callback Callback to execute with retry
     * @param int $delayMs Delay between retries in milliseconds
     */
    public function retry(?int $times, callable $callback, ?int $delayMs = null): self
    {
        $config = $this->config;
        $times ??= $config->maxRetryAttempts;
        $delayMs ??= $config->defaultRetryDelay;

        if ($times < 1) {
            throw new \InvalidArgumentException('retry() requires at least one attempt.');
        }
        if ($delayMs < 0) {
            throw new \InvalidArgumentException('retry() delay must be zero or greater.');
        }

        $lastException = new \LogicException('retry() did not run.');

        for ($attempt = 1; $attempt <= $times; $attempt++) {
            try {
                $result = $callback($this->instance);

                $this->setResult($result);

                return $this;
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < $times && $delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Execute callback with timeout protection.
     *
     * @param int $seconds Timeout in seconds
     * @param callable $callback Callback to execute
     */
    public function timeout(?int $seconds, callable $callback): self
    {
        $seconds ??= $this->config->defaultTimeout;
        if ($seconds < 1) {
            throw new \InvalidArgumentException('timeout() requires at least one second.');
        }
        if (!extension_loaded('pcntl')) {
            throw new \LogicException('timeout() requires the PCNTL extension.');
        }
        if (self::$timeoutActive) {
            throw new \LogicException('Nested timeout() calls are not supported.');
        }

        self::$timeoutActive = true;
        pcntl_async_signals(true);
        $previousHandler = pcntl_signal_get_handler(SIGALRM);
        pcntl_signal(SIGALRM, static function () use ($seconds): void {
            throw new ChainTimeoutException("Operation timed out after {$seconds} seconds");
        });
        pcntl_alarm($seconds);

        try {
            $result = $callback($this->instance);
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, $previousHandler);
            self::$timeoutActive = false;
        }

        $this->setResult($result);

        return $this;
    }
}
