<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/{any}/{resource}', 'Post\StoreController@handle')
    ->where([
        'any' => 'nova-api',
        'resource' => 'posts'
    ])->name('nova.api.')->middleware('nova');

Route::put('/{any}/{resource}/{resourceId}', 'Post\UpdateController@handle')
    ->where([
        'any' => 'nova-api',
        'resource' => 'posts'
    ])->name('nova.api.')->middleware('nova');
