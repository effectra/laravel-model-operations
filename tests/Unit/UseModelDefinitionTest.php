<?php

use Effectra\LaravelModelOperations\Exceptions\ModelNotFoundException;
use Effectra\LaravelModelOperations\Traits\UseModelDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


beforeEach(function () {
    Cache::flush();
});

class DummyModel extends Model
{
    protected $table = 'dummy_models';
}

class DummyController
{
    use UseModelDefinition;

    public function __construct()
    {
        $this->setModel(DummyModel::class);
    }
}

test('can get model failed index', function () {
    $controller = new DummyController();

    $reflection = new ReflectionClass($controller);
    $property = $reflection->getProperty('modelFailedIndex');
    $property->setAccessible(true);
    $property->setValue($controller, 5);

    expect($controller->getModelFailedIndex())->toBe(5);
});

test('can get model name', function () {
    $controller = new DummyController();
    expect($controller->getModelName())->toBe('DummyModel');
});

test('can generate cache key without id', function () {
    $controller = new class extends DummyController {
        public function resolveModelName(string $source = 'Controller'): string
        {
            return 'App\Models\DummyModel';
        }
    };

    $key = $controller->cacheKey('read');
    expect($key)->toBe('App\Models\DummyModel_read');
});

test('can generate cache key with id', function () {
    $controller = new class extends DummyController {
        public function resolveModelName(string $source = 'Controller'): string
        {
            return 'App\Models\DummyModel';
        }
    };

    $key = $controller->cacheKey('read_one', 42);
    expect($key)->toBe('App\Models\DummyModel_read_one_42');
});

test('can get cache ttl with fallback default', function () {
    $controller = new DummyController();

    expect($controller->getCacheTtl('non_existing_key', 120))->toBe(120);
});

test('can find one by id', function () {
    $model = DummyModel::create();
    $controller = new DummyController();

    $found = $controller->findOneBy($model->id);
    expect($found->id)->toBe($model->id);
})->skip('Requires database setup');

test('can empty cache read', function () {
    $controller = new class extends DummyController {
        public function resolveModelName(string $source = 'Controller'): string
        {
            return 'App\Models\DummyModel';
        }
    };

    Cache::put($controller->cacheKey('read'), 'value', 60);
    expect(Cache::has($controller->cacheKey('read')))->toBeTrue();

    $controller->emptyCacheRead();
    expect(Cache::has($controller->cacheKey('read')))->toBeFalse();
});

test('can empty cache read one', function () {
    $controller = new class extends DummyController {
        public function resolveModelName(string $source = 'Controller'): string
        {
            return 'App\Models\DummyModel';
        }
    };

    $key = $controller->cacheKey('read_one', 99);
    Cache::put($key, 'value', 60);
    expect(Cache::has($key))->toBeTrue();

    $controller->emptyCacheReadOne(99);
    expect(Cache::has($key))->toBeFalse();
});

test('can empty cache read many', function () {
    $controller = new class extends DummyController {
        public function resolveModelName(string $source = 'Controller'): string
        {
            return 'App\Models\DummyModel';
        }
    };

    $ids = [1, 2, 3];
    $key = $controller->cacheKey('read_many', implode('_', $ids));
    Cache::put($key, 'value', 60);
    expect(Cache::has($key))->toBeTrue();

    $controller->emptyCacheReadMany($ids);
    expect(Cache::has($key))->toBeFalse();
});

test('throws exception if model class does not exist', function () {
    $controller = new class {
        use UseModelDefinition;
    };

    $mockClass = new class {
        public static function className() {
            return 'NonExistentController';
        }
    };

    expect(fn() => $controller->resolveModelName('Controller'))
        ->toThrow(ModelNotFoundException::class);
})->skip('Depends on controller naming and autoloading');

