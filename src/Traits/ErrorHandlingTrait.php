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

            if (is_object($result)) {
                $this->instance = $result;
            }
            $this->result = $result;
        } catch (\Throwable $e) {
            $result = $handler($e);

            if (is_object($result)) {
                $this->instance = $result;
            }
            $this->result = $result;
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
    public function retry(int $times, callable $callback, int $delayMs = 0): self
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $times; $attempt++) {
            try {
                $result = $callback($this->instance);

                if (is_object($result)) {
                    $this->instance = $result;
                }
                $this->result = $result;

                return $this;
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < $times && $delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        return $this;
    }

    /**
     * Execute callback with timeout protection.
     *
     * @param int $seconds Timeout in seconds
     * @param callable $callback Callback to execute
     */
    public function timeout(int $seconds, callable $callback): self
    {
        $start = time();
        $result = $callback($this->instance);

        if (time() - $start > $seconds) {
            throw new ChainTimeoutException("Operation timed out after {$seconds} seconds");
        }

        if (is_object($result)) {
            $this->instance = $result;
        }
        $this->result = $result;

        return $this;
    }
}
