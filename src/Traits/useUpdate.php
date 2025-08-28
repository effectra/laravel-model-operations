<?php

namespace Effectra\LaravelModelOperations\Traits;

use Illuminate\Http\Request;
use Closure;
use Effectra\LaravelModelOperations\Exceptions\ManyOperationException;
use Illuminate\Support\Facades\Cache;

/**
 * Trait UseUpdate
 *
 * Provides reusable methods for updating single or multiple Eloquent model instances.
 * @property class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Model $model The model class name or instance used for operations.
 */
trait UseUpdate
{

    /**
     * The last successfully updated model instance.
     *
     * @var object|null
     */
    protected ?object $modelUpdated = null;


    /**
     * Get the last successfully updated model.
     *
     * @return object|null
     */
    public function getModelUpdated(): ?object
    {
        return $this->modelUpdated;
    }

    /**
     * Update a single model instance.
     * @param int|string $id
     * @param   \Illuminate\Http\Request|array $data Validated request data or array of attributes
     * @param  \Closure|null                   $onFinish Callback executed after successful save
     * @return bool True if updating was successful, false otherwise
     */
    public function update(int|string $id, array|Request $data, ?Closure $onFinish = null): bool
    {
        if( $data instanceof Request && method_exists($data, 'validated') === false){
            throw new \InvalidArgumentException('The Request object must have a validated() method. Ensure it is a Form Request.');
        }
        $attributes = $data instanceof Request
            ? $data->all()
            : $data;
 
        $model = $this->model::findOrFail($id);

        if (config('model-operations.save_old_data_when_update', true)) {
            $this->saveOldDataModelInCache($model);
        }

        $saved = $model->update($attributes);

        if ($saved) {
            $this->modelUpdated = $model;

            $onFinish?->call($this, $model);
            $this->emptyCacheRead();
        }

        return $saved;
    }

    /**
     * Updates multiple model instances from request data.
     *
     * @param  \Illuminate\Http\Request $request The request containing an array of items
     * @param  \Closure|null            $onFinish Callback executed after each successful save
     * @return bool True if all updating were successful, false otherwise
     * @throws \Effectra\LaravelModelOperations\Exceptions\ManyOperationException If any item fails to process
     */
    public function updateMany(Request $request, ?Closure $onFinish = null): bool
    {
        try {
            $data = $request->all();

            $this->results = array_map(
                fn(array $item) => $this->update($item['id'], $item, $onFinish),
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
     * Updates all model instances from request data.
     *
     * @param  \Illuminate\Http\Request $request The request containing an array of items
     * @param  \Closure|null            $onFinish Callback executed after each successful save
     * @return bool True if all updating were successful, false otherwise
     * @throws \Effectra\LaravelModelOperations\Exceptions\ManyOperationException If any item fails to process
     */
    public function updateAll(Request $request, ?Closure $onFinish = null): bool
    {
        try {
            $data = $request->all();

            $models = $this->model::all();

            foreach ($models as $model) {
                $this->results[] = $this->update($model->id, $data, $onFinish);
            }
            return isSuccessfulResult($this->results);
        } catch (ManyOperationException $e) {
            $this->modelFailedIndex = $e->getIndex();
            return false;
        }
    }

    /**
     * Generate a unique cache key for a updated model.
     * @param int|string $id
     * @return string
     */
    public function oldModelCacheKey(int|string $id): string
    {
        return sprintf('old_updated_model_%s_%s', $this->resolveModelName(), $id);
        ;
    }

    /**
     * Store old model data in cache before update.
     * @param object $model
     * @return bool
     */
    public function saveOldDataModelInCache(object $model): bool
    {
        return Cache::put($this->oldModelCacheKey($model->id), $model->getAttributes(), config('model-operations.trash_ttl', 3600));
    }
}