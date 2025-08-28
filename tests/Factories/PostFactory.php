<?php 

namespace Effectra\LaravelModelOperations\Tests\Factories;

class PostFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = \Effectra\LaravelModelOperations\Tests\Models\Post::class;
    public function definition()
    {
        return [
            'title' => 'Sample Title',
            'body' => 'Sample body content for the post.',
        ];
    }

}
