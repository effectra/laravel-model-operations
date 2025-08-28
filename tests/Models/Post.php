<?php 

namespace Effectra\LaravelModelOperations\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'body'];

    protected static function newFactory()
    {
        return \Effectra\LaravelModelOperations\Tests\Factories\PostFactory::new();
    }
}
