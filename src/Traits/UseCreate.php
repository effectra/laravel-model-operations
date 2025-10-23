<?php

namespace Effectra\LaravelModelOperations\Traits;

use Illuminate\Http\Request;
use Closure;
use Effectra\LaravelModelOperations\Exceptions\ManyOperationException;
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
     * Get the last successfully created model.
     *
     * @return object|null
     */
    public function getModelCreated(): ?object
    {
        return $this->modelCreated;
    }


    /**
     * Create a single model instance.
     *
     * @param  \Illuminate\Http\Request|array  $data   Validated request data or array of attributes
     * @param  array                           $default Additional default attributes
     * @param  \Closure|null                   $onFinish Callback executed after successful save
     * @return bool True if creation was successful, false otherwise
     */
    public function create(array|Request $data, array $default = [], ?Closure $onFinish = null): bool
    {
         if( $data instanceof Request && method_exists($data, 'validated') === false){
            throw new \InvalidArgumentException('The Request object must have a validated() method. Ensure it is a Form Request.');
        }
        $attributes = $data instanceof Request
            ? $data->validated()
            : $data;

        $modelClass = $this->model;
        $model = new $modelClass(array_merge($attributes, $default));

        $saved = $model->save();

        if ($saved) {
            $this->modelCreated = $model;
            $onFinish?->call($this, $model);
            $this->emptyCacheRead();
        }

        return $saved;
    }

    /**
     * Create multiple model instances from request data.
     *
     * @param  \Illuminate\Http\Request $request The request containing an array of items
     * @param  array                    $default Additional default attributes
     * @param  \Closure|null            $onFinish Callback executed after each successful save
     * @return bool True if all creations were successful, false otherwise
     * @throws ManyOperationException If any item fails to process
     */
    public function createMany(Request $request, array $default = [], ?Closure $onFinish = null): bool
    {
        try {
            $data = $request->all();

            $this->results = array_map(
                fn(array $item) => $this->create($item, $default, $onFinish),
                $data,
                array_keys($data)
            );

            return isSuccessfulResult($this->results);
        } catch (ManyOperationException $e) {
            $this->modelFailedIndex = $e->getIndex();
            return false;
        }
    }

    /**
     * Replicate a model instance by its ID.
     *
     * @param  int|string  $id The ID of the model to replicate
     * @param  int  $times Number of times to replicate the model (default is 1)
     * @return bool True if replication was successful, false otherwise
     */
    public function replicateOne(int|string $id, int $times = 1): bool
    {
        $model = $this->model::find($id);

        if (!$model) {
            throw new Exception("Model with ID {$id} not found.");
        }

        $replicatedModels = [];
        for ($i = 0; $i < $times; $i++) {
            $replicatedModel = $model->replicate();
            if ($replicatedModel->save()) {
                $replicatedModels[] = $replicatedModel;
            } else {
                return false; // If any replication fails, return false
            }
        }

        $this->modelCreated = end($replicatedModels);
        $this->emptyCacheRead();
        return true;
    }

    /**
     * Replicate multiple model instances by their IDs.
     *
     * @param  array<int|string>  $ids An array of model IDs to replicate
     * @return bool True if all replications were successful, false otherwise
     */
    public function replicateMany(array $ids, int $times = 1): bool
    {
        $this->results = [];
        foreach ($ids as $id) {
            try {
                $this->results[] = $this->replicateOne($id, $times);
            } catch (Exception $e) {
                throw new ManyOperationException(
                    index: array_search($id, $ids, true),
                    message: "Failed to replicate model with ID {$id}: " . $e->getMessage(),
                    previous: $e
                );
            }
        }

        return isSuccessfulResult($this->results);
    }

}