<?php

use App\Http\Controllers\GUATEXController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('guatex')->group(function () {
    Route::get('/recolectar/{tracking}', [GUATEXController::class, 'recolectar']);
    Route::get('/entregar/{tracking}', [GUATEXController::class, 'entregar']);
    Route::post('/liquidar', [GUATEXController::class, 'liquidar']);
});
