<?php

namespace Effectra\LaravelModelOperations\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Trait UseFetch
 * 
 * Provides reusable methods for fetching single or multiple Eloquent model instances.
 * 
 * @property class-string<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Eloquent\Model  $model
 * @method findOneBy($id)
 */
trait UseFetch
{
    /**
     * The relations to load with the model.
     *
     * @var array
     */
    protected array $relationsArray = [];

    /**
     * Fetch all records with caching.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function fetch(): Collection
    {
        $ttl = $this->getCacheTtl('fetch', config('model-operations.fetch_ttl.fetch'));

        return Cache::remember($this->cacheKey('fetch'), $ttl, function () {
            $query = $this->model::query();

            if (!empty($this->relationsArray)) {
                $query->with($this->relationsArray);
            }

            $data = $query->get();
            return $data;
        });
    }

    /**
     * Fetch a single record by its ID with caching.
     * @param int|string $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function fetchOne(int|string $id): ?Model
    {
        $ttl = $this->getCacheTtl('fetch_one', config('model-operations.fetch_ttl.fetch_one'));

        return Cache::remember($this->cacheKey('fetch_one', $id), $ttl, function () use ($id) {
            $query = $this->model::query();

            if (!empty($this->relationsArray)) {
                $query->with($this->relationsArray);
            }

            return $query->findOrFail($id);
        });
    }

    /**
     * Fetch multiple records by their IDs with caching.
     * @param array<int|string> $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function fetchMany(array $ids): Collection
    {
        $ttl = $this->getCacheTtl('fetch_many', config('model-operations.fetch_ttl.fetch_many'));

        return Cache::remember($this->cacheKey('fetch_many', implode('_', $ids)), $ttl, function () use ($ids) {
            $query = $this->model::query();

            if (!empty($this->relationsArray)) {
                $query->with($this->relationsArray);
            }

            return $query->whereIn('id', $ids)->get();
        });
    }

    /**
     * set the relations to load with the model.
     * @param array $relationsArray
     * @return void
     */
    public function setRelations(array $relationsArray)
    {
        $this->relationsArray = $relationsArray;
    }

    /**
     * Get the relations to load with the model.
     */
    public function getRelations(): array
    {
        return $this->relationsArray;
    }

    /**
     * Clear the relations array.
     */
    public function clearRelations(): void
    {
        $this->relationsArray = [];
    }

}
