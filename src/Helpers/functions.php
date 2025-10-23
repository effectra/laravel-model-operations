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

if (!function_exists('config')) {
    /**
     * Get or set configuration values.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key = null, $default = null)
    {
        static $config = [];

        // Load config files once
        if (empty($config)) {
            $configPath = __DIR__ . '/../../config/';
            if (is_dir($configPath)) {
                foreach (glob($configPath . '*.php') as $file) {
                    $name = basename($file, '.php');
                    $config[$name] = require $file;
                }
            }
        }

        if ($key === null) {
            return $config;
        }

        // Dot notation support: config('app.name')
        $segments = explode('.', $key);
        $value = $config;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }
}