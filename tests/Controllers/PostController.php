<?php

namespace Effectra\LaravelModelOperations\Tests\Controllers;

use Effectra\LaravelModelOperations\Traits\UseCreate;
use Effectra\LaravelModelOperations\Traits\UseDelete;
use Effectra\LaravelModelOperations\Traits\UseFetch;
use Effectra\LaravelModelOperations\Traits\UseModelDefinition;
use Effectra\LaravelModelOperations\Traits\UseResponse;
use Effectra\LaravelModelOperations\Traits\UseUpdate;
use Illuminate\Http\JsonResponse;

class PostController
{
    use UseModelDefinition, UseCreate, UseUpdate, UseDelete, UseFetch, UseResponse;

    public function __construct()
    {
        $this->setModelNameSpace(modelNameSpace: 'Effectra\\LaravelModelOperations\\Tests\\Models\\');
        $this->model = $this->resolveModelName();
    }

    public function read(): JsonResponse
    {
        return $this->success($this->fetch());
    }
}
