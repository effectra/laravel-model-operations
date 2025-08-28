<?php

use Effectra\LaravelModelOperations\Tests\Controllers\PostController;
use Effectra\LaravelModelOperations\Tests\Mocks\SimulatedFormRequest;
use Effectra\LaravelModelOperations\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Effectra\LaravelModelOperations\Exceptions\ManyOperationException;
use Illuminate\Support\Str;
use Effectra\LaravelModelOperations\Tests\Models\Post;

beforeEach(function () {
    Cache::clear();
});
uses(TestCase::class);


test('it updates a single model using array and stores old data in cache', function () {
    $post = Post::factory()->create(['title' => 'Old Title']);
    $service = new PostController();

    $result = $service->update($post->id, ['title' => 'New Title']);

    expect($result)->toBeTrue()
        ->and($service->getModelUpdated()->title)->toBe('New Title');

    $cached = Cache::get($service->oldModelCacheKey($post->id));
    expect($cached['title'])->toBe('Old Title');
});

test('simulated request validates data', function () {
    $request = new SimulatedFormRequest([
        'name' => 'Test',
        'email' => 'test@example.com',
    ], [
        'name' => 'required|string',
        'email' => 'required|email',
    ]);

    $validated = $request->validated();

    expect($validated['name'])->toBe('Test');
});

test('it updates a single model using Request', function () {
    $post = Post::factory()->create(['title' => 'Hello']);

    $request = new SimulatedFormRequest([
        'title' => 'Updated',
        'body' => 'Some content',
    ], [
        'title' => 'required|string|max:255',
        'body' => 'required|string',
    ]);

    $service = new PostController();
    $result = $service->update($post->id, $request);

    expect($result)->toBeTrue();
    expect(Post::find($post->id)->title)->toBe('Updated');
});

test('it runs callback after single update', function () {
    $post = Post::factory()->create(['title' => 'Before']);
    $service = new PostController();

    $callbackCalled = false;

    $service->update($post->id, ['title' => 'After'], function () use (&$callbackCalled) {
        $callbackCalled = true;
    });

    expect($callbackCalled)->toBeTrue();
});

test('it updates many models successfully', function () {
    $posts = Post::factory()->count(2)->create();
    $service = new PostController();

    $request = new Request([
        ['id' => $posts[0]->id, 'title' => 'A'],
        ['id' => $posts[1]->id, 'title' => 'B'],
    ]);

    $result = $service->updateMany($request);

    expect($result)->toBeTrue()
        ->and(Post::find($posts[0]->id)->title)->toBe('A')
        ->and(Post::find($posts[1]->id)->title)->toBe('B');
});

test('it handles updateAll correctly', function () {
    Post::factory()->count(3)->create(['title' => 'Old']);
    $service = new PostController();

    $request = new Request(['title' => 'New']);

    $result = $service->updateAll($request);

    expect($result)->toBeTrue();
    Post::all()->each(function ($post) {
        expect($post->title)->toBe('New');
    });
});

test('it stores old data in cache', function () {
    $post = Post::factory()->create(['title' => 'Original']);
    $service = new PostController();

    $service->saveOldDataModelInCache($post);

    $cached = Cache::get($service->oldModelCacheKey($post->id));

    expect($cached)->toBeArray()
        ->and($cached['title'])->toBe('Original');
});