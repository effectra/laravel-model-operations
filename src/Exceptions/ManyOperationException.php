<?php

namespace Effectra\LaravelModelOperations\Exceptions;

use Throwable;

/**
 * Class ManyOperationException
 *
 * Custom exception thrown when one of the operations in a batch fails.
 */
class ManyOperationException extends \Exception
{
    /**
     * @param  int|null     $index    Index of the failed item in the batch
     * @param  string       $message  Exception message
     * @param  int          $code     Exception code
     * @param  \Throwable|null $previous Previous throwable for exception chaining
     */
    public function __construct(
        private readonly ?int $index = null,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the index of the failed item in the batch.
     *
     * @return int|null
     */
    public function getIndex(): ?int
    {
        return $this->index;
    }
}