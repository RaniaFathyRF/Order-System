<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-redis', function () {
    try {
        $redis = app('redis');
        $redis->set('test_key', 'Hello Redis!');
        $value = $redis->get('test_key');

        return response()->json([
            'status' => 'success',
            'data' => $value,
            'driver' => config('database.redis.client')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
