<?php

namespace Effectra\LaravelModelOperations\Traits;

use Effectra\LaravelModelOperations\Exceptions\TrashException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Trait UseTrash
 *
 * Provides reusable methods for deleting single or multiple Eloquent model instances.
 * 
 * @property class-string<Model>|Model $model The model class name or instance used for operations.
 */
trait UseTrash
{

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
    * Generate a unique cache key for a trashed model.
    * @param int|string $id
    * @return string
    */
   public function trashKey(int|string $id): string
   {
      return sprintf('trashed_model_%s_%s', $this->resolveModelName(), $id);
   }

   /**
    * Generate a unique cache key for a trashed model.
    * @param int|string $id
    * @return string
    */
   public function trashKeyMany(): string
   {
      return sprintf('trashed_model_%s_many', $this->resolveModelName());
   }

   /**
    * get trash Key For All
    * @return string
    */
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

   public function addToTrashModelsDeleted($id, array $deletedIds = [], int $ttl = 3600)
   {
      $deletedIds[] = $id;
      if (!Cache::put($this->trashKeyMany(), $deletedIds, $ttl)) {
         throw new TrashException("Store deleted model with [ID:{$id}] fails");
      }
   }

   /**
    * get Models Deleted Ids
    * @return array<int|string>
    */
   public function getModelsDeletedIds(): array
   {
      return Cache::get($this->trashKeyMany(), []);
   }
}