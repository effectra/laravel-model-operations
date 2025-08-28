<?php

use Illuminate\Http\Request;
use Effectra\LaravelModelOperations\Tests\Controllers\PostController;
use Effectra\LaravelModelOperations\Tests\Models\Post;



it('creates a single model instance', function () {
    Post::truncate();
    $creator = new PostController();

    $data = ['title' => 'Test Post', 'body' => 'Test body'];

    $success = $creator->create($data);

    expect($success)->toBeTrue();
    expect($creator->getModelCreated())->not()->toBeNull();
    expect($creator->getModelCreated()->title)->toBe('Test Post');
});

it('creates multiple model instances', function () {
    Post::truncate();
    $creator = new PostController();

    $request = new Request([
        ['title' => 'Post 1', 'body' => 'Body 1'],
        ['title' => 'Post 2', 'body' => 'Body 2'],
    ]);

    $success = $creator->createMany($request);

    expect($success)->toBeTrue();
    expect(Post::count())->toBe(2);
});

it('replicates a model instance', function () {
    Post::truncate();
    $post = Post::create([
        'title' => 'Original',
        'body' => 'Original Body'
    ]);

    $creator = new PostController();
    $success = $creator->replicateOne($post->id);

    expect($success)->toBeTrue();
    expect(Post::count())->toBe(2);
    expect($creator->getModelCreated()->title)->toBe('Original');
});

it('replicates multiple model instances', function () {
    Post::truncate();
    $post1 = Post::create(['title' => 'Post 1', 'body' => 'Body 1']);
    $post2 = Post::create(['title' => 'Post 2', 'body' => 'Body 2']);

    $creator = new PostController();
    $success = $creator->replicateMany([$post1->id, $post2->id]);

    expect($success)->toBeTrue();
    expect(Post::count())->toBe(4);
});

it('throws exception when replicating non-existent model', function () {
    $creator = new PostController();

    $creator->replicateOne(999); // Non-existent ID
})->throws(Exception::class, 'Model with ID 999 not found.');
