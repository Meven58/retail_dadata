<?php

use Illuminate\Support\Facades\Route;

Route::get('/get-customers-corporate', [\App\Http\Controllers\RetailController::class, 'index']);
