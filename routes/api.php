<?php

use App\Http\Controllers\ChocoBillyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::controller(ChocoBillyController::class)->group(function() {
    Route::post('calculate-order', 'calculateOrder');
    Route::post('crc', 'crc');
});