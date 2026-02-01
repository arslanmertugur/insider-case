<?php
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\FixtureController;
use Illuminate\Support\Facades\Route;

Route::get('/groups', [LeagueController::class, 'groups']);
Route::post('/draw-groups', [LeagueController::class, 'draw']);

Route::post('/fixtures', [FixtureController::class, 'generate']);


Route::post('/play-next-week', [FixtureController::class, 'playNextWeek']);
Route::post('/play-next-match', [FixtureController::class, 'playNextMatch']);
Route::get('/fixtures/all', [FixtureController::class, 'getAllFixtures']);
Route::post('/play-all', [FixtureController::class, 'playAll']);
Route::get('/predictions', [FixtureController::class, 'getPredictions']);
Route::put('/matches/{id}', [FixtureController::class, 'updateMatch']);
Route::get('/standings', [FixtureController::class, 'index']);
Route::post('/reset', [App\Http\Controllers\FixtureController::class, 'reset']);