<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/dashboard'));

// Отдельные страницы по контекстам
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
Route::get('/iss', [\App\Http\Controllers\IssController::class, 'index'])->name('iss');
Route::get('/osdr', [\App\Http\Controllers\OsdrController::class, 'index'])->name('osdr');
Route::get('/jwst', [\App\Http\Controllers\JwstController::class, 'index'])->name('jwst');
Route::get('/astro', [\App\Http\Controllers\AstroController::class, 'index'])->name('astro');
Route::get('/telemetry', [\App\Http\Controllers\TelemetryController::class, 'index'])->name('telemetry');

// API endpoints
Route::get('/api/iss/last', [\App\Http\Controllers\ProxyController::class, 'last']);
Route::get('/api/iss/trend', [\App\Http\Controllers\ProxyController::class, 'trend']);
Route::get('/api/jwst/feed', [\App\Http\Controllers\DashboardController::class, 'jwstFeed']);
Route::get('/api/astro/events', [\App\Http\Controllers\AstroController::class, 'events']);

// CMS
Route::get('/page/{slug}', [\App\Http\Controllers\CmsController::class, 'page'])->name('cms.page');
