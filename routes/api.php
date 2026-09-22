<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UrlApiController;

Route::prefix('webhook')
    ->middleware('application.api')
    ->group(function () {

    

        Route::get(
            '/districts',
            [UrlApiController::class, 'districts']
        );


    
        Route::get(
            '/original-url',
            [UrlApiController::class, 'index']
        );

        Route::post(
            '/original-url',
            [UrlApiController::class, 'store']
        );

        Route::put(
            '/original-url/{short_code}',
            [UrlApiController::class, 'update']
        );

    });