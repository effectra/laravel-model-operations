<?php

namespace Effectra\LaravelModelOperations\Traits;

use Effectra\LaravelModelOperations\Exceptions\ManyOperationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Closure;
use Exception;

/**
 * Trait UseDelete
 *
 * Provides reusable methods for deleting single or multiple Eloquent model instances.
 * 
 * @property class-string<Model>|Model $model The model class name or instance used for operations.
 */
trait UseDelete
{

   /**
    * The last successfully created model instance.
    *
    * @var object|null
    */
   protected ?object $modelDeleted = null;


   public function delete(int|string $id, ?Closure $onFinish = null): bool
   {
      $model = $this->model::findOrFail($id);
      if (!$model) {
         return false;
      }
      $this->modelDeleted = $model;
      if (config('model-operations.use_trash', true)) {
         $this->moveToTrash($this->modelDeleted);
      }
      $result = $model->delete();
      if ($onFinish) {
         $onFinish?->call($this, $model);
      }
      return $result;
   }

   /**
    * Delete multiple models by their IDs.
    * @param Request $request
    * @param Closure|null $onFinish
    * @return bool
    */
   public function deleteMany(Request $request, ?Closure $onFinish = null): bool
   {
      try {
         $ids = $request->input('ids', []);
         if (empty($ids)) {
            throw new Exception('No IDs provided for deletion.');
         }

         $models = $this->model::whereIn('id', $ids)->get();
         if ($models->isEmpty()) {
            return false;
         }
         /**
          * @var bool[] $results
          */
         $results = [];
         foreach ($models as $model) {
            $results[] = $this->delete($model->id, $onFinish);
         }

         return isSuccessfulResult($results);
      } catch (ManyOperationException $e) {
         $this->modelFailedIndex = $e->getIndex();
         return false;
      }
   }

   /**
    * Delete all models of the current model type.
    * @param ?Closure(object $model): void $onFinish
    * @return bool
    */
   public function deleteAll(?Closure $onFinish = null): bool
   {
      try {
         $models = $this->model::all();
         $ids = $models->pluck('id')->toArray();
         if ($models->isEmpty()) {
            return false;
         }
         /**
          * @var bool[] $results
          */
         $results = [];
         foreach ($models as $model) {
            $results[] = $this->delete($model->id, $onFinish);
         }
         Cache::put(
            $this->trashKeyForAll(),
            $ids,
            config('model-operations.trash_ttl', 3600)
         );
         return isSuccessfulResult($results);
      } catch (ManyOperationException $e) {
         $this->modelFailedIndex = $e->getIndex();
         return false;
      }
   }

   protected function trashKeyForAll(): string
   {
      return sprintf('trashed_all_model_%s_keys', $this->resolveModelName());
   }

   protected function getTrashedIdsDeletedWithDeleteAll(): array
   {
      return Cache::get($this->trashKeyForAll(), []);
   }

   /**
    * Move a deleted model to trash (cache) for potential recovery.
    * @param object $model
    * @param int $ttl
    * @return bool
    */
   protected function moveToTrash(object $model, int $ttl = 3600): bool
   {
      return Cache::put($this->trashKey($model->id), $model->getAttributes(), $ttl);
   }

   /**
    * Generate a unique cache key for a trashed model.
    * @param int|string $id
    * @return string
    */
   public function trashKey(int|string $id): string
   {
      return sprintf('trashed_model_%s_%s', $this->resolveModelName(), $id);
      ;
   }

   /**
    * Get the base key name for trashed models of the current model type.
    * @return string
    */
   private function trashKeyName(): string
   {
      return sprintf('trashed_model_%s_', $this->resolveModelName());
   }

   /**
    * Empty the trash by clearing all cached trashed models.
    * @return void
    */
   protected function emptyTrash()
   {
      // Clear all cached trashed models using preg functions
      $cacheStore = Cache::getStore();
      if (method_exists($cacheStore, 'getRedis')) {
         $redis = $cacheStore->getRedis();
         $prefix = $cacheStore->getPrefix() . $this->trashKeyName();
         $allKeys = $redis->keys($prefix . '*');
         foreach ($allKeys as $key) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '\d+$/', $key)) {
               $redis->del($key);
            }
         }
      }
   }

   /**
    * Undo delete for a single model by its ID.
    *
    * @param int|string $id
    * @return bool
    */
   public function undoDelete(int|string $id): bool
   {
      $cacheKey = $this->trashKey($id);
      $cachedData = Cache::get($cacheKey);

      if ($cachedData) {
         $modelClass = $this->model;
         $model = new $modelClass();

         // Explicitly set the ID to avoid auto-increment
         if (isset($cachedData['id'])) {
            $model->id = $cachedData['id'];
         }

         // Remove ID from fillable attributes (if fill() is used)
         $attributes = $cachedData;
         unset($attributes['id']);

         $model->fill($attributes);

         // Save the model
         $restored = $model->save();

         if ($restored) {
            $this->modelCreated = $model;
            Cache::forget($cacheKey);
            return true;
         }
      }

      return false;
   }

   /**
    * Undo delete for multiple models by their IDs.
    * @param Request|array<int|string> $ids
    * @return bool
    */
   public function undoDeleteMany(Request|array $ids): bool
   {
      $results = [];
      if ($ids instanceof Request) {
         $ids = $ids->input('ids', []);
      }
      foreach ($ids as $id) {
         $results[] = $this->undoDelete($id);
      }
      return isSuccessfulResult($results);
   }

   /**
    * Undo delete for all trashed models of the current model type.
    * @return bool
    */
   public function undoDeleteAll(): bool
   {
      $ids = $this->getTrashedIdsDeletedWithDeleteAll();
      if (empty($ids)) {
         return false;
      }
      $results = [];
      foreach ($ids as $key) {
         $results[] = $this->undoDelete($key);
         Cache::forget($this->trashKeyForAll());
      }
      return isSuccessfulResult($results);
   }
}

