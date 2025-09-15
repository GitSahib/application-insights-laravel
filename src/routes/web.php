<?php
use Illuminate\Support\Facades\Route;
use Larasahib\AppInsightsLaravel\Http\Controllers\AppInsightsController;

Route::post('/appinsights/collect', [AppInsightsController::class, 'collect']);
