<?php

use Illuminate\Support\Facades\Route;

Route::get('/retailclient/{id}', [\App\Http\Controllers\Api\Profile\SettingsController::class, 'index']);
Route::post('/settings-save', [\App\Http\Controllers\Api\Profile\SettingsController::class, 'save']);
Route::post('/update-companies', [\App\Http\Controllers\Api\Profile\SettingsController::class, 'updateCompanies']);
//
//Route::get('retailclient/{id}', function () {
//    return view('welcome');
//});
