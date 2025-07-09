<?php

use App\Services\IgdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TODO: Remove after testing
Route::get('/test-igdb', function (IgdbService $igdbService) {
    // Expedition 33 AppID
    $steamAppId = 1903340;
    dd($igdbService->searchBySteamAppId($steamAppId));
});