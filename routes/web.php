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

Route::group(['middleware' => 'cors'], function(){
    Route::get('/categories', 'CategoryController@getCategories');
    Route::post('/category/save', 'CategoryController@saveCategory');
    Route::post('/category/delete', 'CategoryController@deleteCategory');

	Route::get('/tags', 'TagController@getTags');
    Route::post('/tag/save', 'TagController@saveTag');
    Route::post('/tag/delete', 'TagController@deleteTag');

    Route::get('/pet/params', 'PetController@paramsPets');
    Route::post('/pet', 'PetController@savePet');
    Route::get('/pet', 'PetController@getPets');
    Route::post('/pet/delete', 'PetController@deletePet');
});
