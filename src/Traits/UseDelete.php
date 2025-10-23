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

   use useTrash;

   /**
    * The last successfully created model instance.
    *
    * @var object{id:string}|null
    */
   protected ?object $modelDeleted = null;


   /**
    * Delete a single model by its ID.
    * @param int|string $id
    * @param Closure{model:object} $onFinish
    * @return bool|null
    */
   public function delete(int|string $id, ?Closure $onFinish = null): bool
   {
      $model = $this->model::findOrFail($id);
      if (!$model) {
         return false;
      }
      $this->modelDeleted = $model;
      if (config('model-operations.use_trash', false)) {
         $ttl = config('model-operations.trash_ttl', 3600);
         $this->moveToTrash($this->modelDeleted, $ttl);
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
   public function deleteMany(array|Request $request, ?Closure $onFinish = null): bool
   {
      try {
         $ids = $request instanceof Request ? $request->input('ids', []) : $request;
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

         $canUseTrash = config('model-operations.use_trash', false);

         $ttl = config('model-operations.trash_ttl', 3600);

         foreach ($models as $model) {
            $result = $this->delete($model->id, $onFinish);
            $results[] = $result;
            if ($result && $canUseTrash) {
               $idsDeleted = $this->getModelsDeletedIds();
               $this->addToTrashModelsDeleted($this->modelDeleted->id, $idsDeleted, $ttl);
            }
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

         $canUseTrash = config('model-operations.use_trash', false);

         $ttl = config('model-operations.trash_ttl', 3600);

         if ($models->isEmpty()) {
            return false;
         }

         /**
          * @var bool[] $results
          */
         $results = [];
         foreach ($models as $model) {
            $result = $this->delete($model->id, $onFinish);
            $results[] = $result;
            if ($result && $canUseTrash) {
               $idsDeleted = $this->getModelsDeletedIds();
               $this->addToTrashModelsDeleted($this->modelDeleted->id, $idsDeleted, $ttl);
            }
         }

         return isSuccessfulResult($results);
      } catch (ManyOperationException $e) {
         $this->modelFailedIndex = $e->getIndex();
         return false;
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
      $ids = $this->getModelsDeletedIds();
      if (empty($ids)) {
         return false;
      }
      $results = [];
      foreach ($ids as $idDeleted) {
         if ($result = $this->undoDelete($idDeleted)) {
            $this->addToTrashModelsDeleted(
               $this->modelDeleted->id,
               array_filter($ids, fn($id) => $id !== $idDeleted),
               config('model-operations.trash_ttl', 3600)
            );
         }
         $results[] = $result;
      }
      return isSuccessfulResult($results);
   }
}

