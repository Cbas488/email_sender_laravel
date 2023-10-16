<?php

namespace App\Http\Responses;

class ApiResponse{
    public static function success(String $message = 'Success', int $statusCode = 200, $data = []){
        return response() -> json([
            'message' => $message,
            'status' => $statusCode,
            'error' => false,
            'data' => $data
        ],$statusCode);
    }

    public static function fail(String $message = 'Fail', int $statusCode = 500, Array $errors = []){
        return response() -> json([
            'message' => $message,
            'status' => $statusCode,
            'error' => true,
            'errors' => $errors
        ],$statusCode);
    }
}