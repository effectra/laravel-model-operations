<?php

namespace Effectra\LaravelModelOperations\Traits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Trait UseResponse
 *
 * Provides methods to generate standardized JSON responses for various operations.
 */
trait UseResponse
{

    /**
     * Generate a standardized JSON response.
     *
     * @param  bool   $success Indicates if the operation was successful
     * @param  string $message Message describing the result
     * @param  mixed  $data    Additional data to include in the response
     * @param  int    $status  HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonResponse(bool $success, string $message, mixed $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if (!empty($this->additionalInfo())) {
            $response = array_merge($response, $this->additionalInfo());
        }

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Set additional information to include in the response.
     *
     * @return array
     */ 
    public function additionalInfo()
    {
        return [
        ];
    }

    public function success($data = [],  $code = 200, $message = null,)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function error($message = 'Error', $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }
}