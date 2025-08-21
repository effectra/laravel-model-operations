<?php

namespace LaravelModelOperations\Traits;

use Illuminate\Http\Request;
use Closure;
use ManyOperationException;
use Throwable;
use Exception;

/**
 * Trait UseCreate
 *
 * Provides reusable methods for creating single or multiple Eloquent model instances.
 */
trait UseCreate
{
    /**
     * The last successfully created model instance.
     *
     * @var object|null
     */
    protected ?object $modelCreated = null;

    /**
     * The index of the failed model in batch creation (if any).
     *
     * @var int|null
     */
    protected ?int $modelFailedIndex = null;

    /**
     * The results of the last createMany operation.
     *
     * @var array<bool>|null
     */
    protected ?array $results = null;

    /**
     * Create a single model instance.
     *
     * @param  \Illuminate\Http\Request|array  $data   Validated request data or array of attributes
     * @param  array                           $default Additional default attributes
     * @param  \Closure|null                   $onFinish Callback executed after successful save
     * @return bool True if creation was successful, false otherwise
     */
    protected function create(array|Request $data, array $default = [], ?Closure $onFinish = null): bool
    {
        $attributes = $data instanceof Request
            ? $data->validated()
            : $data;

        $modelClass = $this->model;
        $model = new $modelClass(array_merge($attributes, $default));

        $saved = $model->save();

        if ($saved) {
            $this->modelCreated = $model;
            $onFinish?->call($this, $model);
        }

        return $saved;
    }

    /**
     * Get the last successfully created model.
     *
     * @return object|null
     */
    public function getModelCreated(): ?object
    {
        return $this->modelCreated;
    }

    /**
     * Create multiple model instances from request data.
     *
     * @param  \Illuminate\Http\Request  $request The request containing an array of items
     * @return bool True if all creations were successful, false otherwise
     * @throws ManyOperationException If any item fails to process
     */
    public function createMany(Request $request): bool
    {
        try {
            $data = $request->all();

            $this->results = array_map(
                fn(array $item, int $index) => $this->create($item),
                $data,
                array_keys($data)
            );

            return isSuccessfulResult($this->results);
        } catch (ManyOperationException $e) {
            $this->modelFailedIndex = $e->getIndex();
            return false;
        }
    }
}