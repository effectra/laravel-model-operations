<?php

namespace Effectra\LaravelModelOperations\Traits;

use Effectra\LaravelModelOperations\Exceptions\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Trait UseModelDefinition
 *
 * Provides methods for defining and interacting with Eloquent models.
 */
trait UseModelDefinition
{
    /**
     *  The model class name or instance used for operations.
     * @var class-string<Model>|Model
     */
    protected $model;

    protected string $modelNameSpace = 'App\\Models\\';

    /**
     * The cache TTL map for different methods.
     *
     * @var array<string, int>
     */

    protected array $ttlMap = [];

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
     * Get the index of the failed model in batch creation (if any).
     *
     * @return  int|null
     */
    public function getModelFailedIndex()
    {
        return $this->modelFailedIndex;
    }

    /**
     * get correct model name based on class source
     * @param string $source
     * @throws \Effectra\LaravelModelOperations\Exceptions\ModelNotFoundException
     * @return string
     */
    public function resolveModelName(string $source = 'Controller'): string
    {
        // Get the full class name of the current controller instance
        $target = get_class($this);

        // Remove 'Controller' suffix from the class name
        $name = str_replace($source, '', $target);

        // Get the base class name without the namespace
        $model = class_basename($name);

        // Split the class name by namespace separators
        $namespaceArr = explode('\\', $name);

        // Get the part of the namespace before the last element (the controller name)
        $beforeEnd = count($namespaceArr) - 2;

        // Rebuild the namespace to point to the corresponding model
        $name = $this->modelNameSpace . $namespaceArr[$beforeEnd] . '\\' . $model;

        // Ensure the constructed class name is valid and exists
        if (class_exists($name)) {
            return $name;
        }

        // Handle cases where the model might be directly under 'App\Models' without sub-folders
        $alternativeName = $this->modelNameSpace . $model;
        if (class_exists($alternativeName)) {
            return $alternativeName;
        }

        // Throw an exception if no valid model class was found
        throw new ModelNotFoundException("Model class $name or $alternativeName not found.");
    }

    /**
     * Set the model class or instance
     */
    protected function setModel(string $model)
    {
        $this->model = $model;
    }

    public function getModelName()
    {
        return class_basename($this->model);
    }

    /**
     * Generate a cache key for a given method and optional identifier.
     *
     * @param string $method
     * @param int|string $id
     * @return string
     */
    public function cacheKey(string $method, int|string $id = ''): string
    {
        $key = "{$this->resolveModelName()}_{$method}";
        return $id !== '' ? "{$key}_{$id}" : $key;
    }

    /**
     * Get the cache TTL for a specific key.
     *
     * @param string $key The key for which to get the TTL.
     * @param int|null $default The default TTL value if the key is not found.
     * @return int|null The TTL value in seconds, or null if not set.
     */
    public function getCacheTtl(string $key, ?int $default = null): ?int
    {
        return $this->ttlMap[$key] ?? $default;
    }

    /**
     * Find a model by its primary key.
     *
     * @param int|string $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findOneBy(int|string $id)
    {
        return $this->model::findOrFail($id);
    }

    /**
     * Clear the cache for read operations.
     *
     * @return bool
     */
    public function emptyCacheRead(): bool
    {
        return Cache::forget($this->cacheKey('read'));
    }

    /**
     * Clear the cache for readOne operation.
     *
     * @param int|string $id
     * @return bool
     */
    public function emptyCacheReadOne(int|string $id): bool
    {
        return Cache::forget($this->cacheKey('read_one', $id));
    }

    /**
     * Clear the cache for readMany operation.
     * @param array $ids
     * @return bool
     */
    public function emptyCacheReadMany(array $ids): bool
    {
        return Cache::forget($this->cacheKey('read_many', implode('_', $ids)));
    }

    /**
     * Get the value of modelNameSpace
     */ 
    public function getModelNameSpace():string
    {
        return $this->modelNameSpace;
    }

    /**
     * Set the value of modelNameSpace
     *
     * @return  self
     */ 
    public function setModelNameSpace(string $modelNameSpace)
    {
        $this->modelNameSpace = $modelNameSpace;

        return $this;
    }
    
}