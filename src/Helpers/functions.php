<?php

use Effectra\LaravelModelOperations\Exceptions\ManyOperationException;

if (!function_exists("isSuccessfulResult")) {
    /**
     * Check if all operations were successful.
     *
     * @param  array<bool>  $results Array of boolean results
     * @param  bool         $throw   Whether to throw an exception on first failure
     * @return bool True if all results are true, false otherwise
     *
     * @throws \Effectra\LaravelModelOperations\Exceptions\ManyOperationException If $throw is true and a failure is found
     */
    function isSuccessfulResult(array $results, bool $throw = false): bool
    {
        foreach ($results as $index => $success) {
            if (!$success) {
                if ($throw) {
                    throw new ManyOperationException($index, "Error processing item at index {$index}");
                }
                return false;
            }
        }
        return true;
    }
}

