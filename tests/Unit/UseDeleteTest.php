<?php
use Effectra\LaravelModelOperations\Tests\Controllers\PostController;
use Effectra\LaravelModelOperations\Tests\Models\Post;
use Illuminate\Http\Request;
use Symfony\Component\VarDumper\VarDumper;

test('it deletes a model by id', function () {
    $post = Post::create(['title' => 'Test Post', 'body' => 'This is a test post.']);

    $controller = new PostController();

    $result = $controller->delete($post->id);

    expect($result)->toBeTrue();
});

test('it deletes multiple models by ids', function () {
    $posts = Post::factory()->count(3)->create();
    $ids = $posts->pluck('id')->toArray();

    $controller = new PostController();

    $request = Request::create('/delete-many', 'POST', ['ids' => $ids]);
    $result = $controller->deleteMany($request);

    expect($result)->toBeTrue();

});

test('it moves a deleted model to trash', function () {
    $post = Post::create(['title' => 'Trash Me', 'body' => 'I should be in the trash.']);
    $controller = new PostController();
    $controller->delete($post->id);

    $key = $controller->trashKey($post->id);
    expect(Cache::has($key))->toBeTrue();
    // expect(Cache::get($key))->toBe($post->getAttributes());
});

test('it deletes all models', function () {
    Post::factory()->count(5)->create();
    $controller = new PostController();
    $result = $controller->deleteAll();

    expect($result)->toBeTrue();
    expect(Post::count())->toBe(0);
});

test('it handles deletion of non-existent model gracefully', function () {
    $controller = new PostController();

    expect(fn() => $controller->delete(9999))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('it can undo a single deletion', function () {
    $post = Post::create(['title' => 'Restore Me', 'body' => 'I should be restored.']);
    $controller = new PostController();
    $controller->delete($post->id);

    $restored = $controller->undoDelete($post->id);

    expect($restored)->toBeTrue();
    expect(Post::find($post->id))->not()->toBeNull();
    expect($post->id)->toBe($controller->getModelCreated()->id);
});

test('it fails to undo deletion if not in trash', function () {
    $post = Post::create(['title' => 'Cannot Restore', 'body' => 'This post was never deleted.']);
    $controller = new PostController();

    $restored = $controller->undoDelete($post->id);

    expect($restored)->toBeFalse();
    expect(Post::find($post->id))->not()->toBeNull();
    expect($controller->getModelCreated())->toBeNull();
});


test('it can undo multiple deletions', function () {
    $posts = Post::factory()->count(3)->create();
    $ids = $posts->pluck('id')->toArray();

    $controller = new PostController();

    // Delete many
    $request = Request::create('/delete-many', 'POST', ['ids' => $ids]);
    $controller->deleteMany($request);

    // Undo delete many
    $undoRequest = Request::create('/undo-delete-many', 'POST', ['ids' => $ids]);
    $restored = $controller->undoDeleteMany($undoRequest);

    expect($restored)->toBeTrue();
    foreach ($ids as $id) {
        expect(Post::find($id))->not()->toBeNull();
    }
});

test('it fails to undo multiple deletions if not in trash', function () {
    $posts = Post::factory()->count(2)->create();
    $ids = $posts->pluck('id')->toArray();

    $controller = new PostController();

    // Attempt to undo delete many without deleting first
    $undoRequest = Request::create('/undo-delete-many', 'POST', ['ids' => $ids]);
    $restored = $controller->undoDeleteMany($undoRequest);

    expect($restored)->toBeFalse();
    foreach ($ids as $id) {
        expect(Post::find($id))->not()->toBeNull();
    }
});


test('it can undo deletion of all trashed models', function () {
    Post::truncate();
    Cache::flush();
    expect(Post::count())->toBe(0);
    // Create and delete multiple posts
    $posts = Post::factory()->count(4)->create();
    $ids = $posts->pluck('id')->toArray();

    $controller = new PostController();

    $controller->deleteAll();

    // Ensure all posts are deleted and in trash
    foreach ($ids as $id) {
        expect(Post::find($id))->toBeNull();
        $key = $controller->trashKey($id);
        expect(Cache::has($key))->toBeTrue();
    }

    // Undo delete all
    $restored = $controller->undoDeleteAll();

    expect(Post::count())->toBe(4);
    expect($restored)->toBeTrue();

    //    Ensure all posts are restored and no longer in trash
    foreach ($ids as $id) {
        expect(Post::find($id))->not()->toBeNull();
        $key = $controller->trashKey($id);
        expect(Cache::has($key))->toBeFalse();
    }
});

test('it fails to undo deletion if trash is empty', function () {
    $controller = new PostController();

    // Ensure trash is empty
    Cache::flush();

    $restored = $controller->undoDeleteAll();

    expect($restored)->toBeFalse();
});

