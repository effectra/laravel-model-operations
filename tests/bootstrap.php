<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Cache\Repository;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

// Set up service container
$container = new Container();
Facade::setFacadeApplication($container);

// Set up cache binding for facade + helper
$cache = new Repository(new ArrayStore);
$container->instance('cache', $cache);

// Optional: alias facade (if needed)
class_alias(\Illuminate\Support\Facades\Cache::class, 'Cache');

// Setup Eloquent (optional for model tests)
$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

Capsule::schema()->create('posts', function ($table) {
    $table->increments('id');
    $table->string('title');
    $table->text('body');
    $table->timestamps();
});
