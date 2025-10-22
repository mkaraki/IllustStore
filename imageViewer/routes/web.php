<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index'])->name('home');

Route::controller(ImageController::class)->group(function () {
    Route::get('/image/', 'index');
    Route::get('/image/{imageId}', 'show');
    Route::get('/image/{imageId}/duplicate', 'duplicate');
    Route::get('/image/{imageId}/neighbor', 'neighbor');
    Route::delete('/image/{imageId}/tag/{tagId}', 'deleteTag');
    Route::put('/image/{imageId}/tag/{tagId}', 'putTag');
    Route::get('/image/{imageId}/tag/new', 'assignTagMenu');
    Route::post('/image/{imageId}/tag/new', 'assignTag');
});

Route::controller(TagController::class)->group(function () {
    Route::get('/tag/', 'index');
    Route::post('/tag/complete', 'complete');
    Route::get('/tag/new', 'newTagView');
    Route::post('/tag/new', 'newTag');
    Route::get('/tag/pending', 'pending');
    Route::get('/tag/{tagId}', 'show');
    Route::get('/tag/{tagId}/edit', 'editTagView');
    Route::post('/tag/{tagId}/edit', 'editTag');
});

Route::controller(SearchController::class)->group(function () {
    Route::get('/search', 'index');
    Route::get('/search/{hashType}/{hash}', 'hashSearch')
        ->whereIn('hashType', ['aHash', 'dHash', 'pHash', 'colorHash'])
        ->where('hash', '[a-fA-F0-9]+');
});