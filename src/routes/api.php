<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/notifications/send', [NotificationController::class, 'send']);
Route::get('/notifications/{id}', [NotificationController::class, 'show']);
Route::get('/subscribers/{subscriberId}/notifications', [NotificationController::class, 'history']);