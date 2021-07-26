<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('login', 'AuthController@login'); // Login
$router->post('register', 'AuthController@register'); // Register

// $router->group(['middleware' => 'auth'], function() use ($router) { // Harus Login / Pakai Token JWT
    $router->post('check_token', 'AuthController@check_token'); // Route Check Token masih aktif ?
    $router->post('logout', 'AuthController@logout'); // Clear token

    $router->get('cleansing', 'PreprocessingController@start'); // Cleansing / Preprosessing kata
    $router->get('stop', 'PreprocessingController@stop_removal'); // Coba coba sahaja

    $router->get('pembobotan', 'MethodController@index');

    $router->post('import', 'DataController@import'); // Proses Import dataset

    // Route Data
    $router->get('data/raw-data', 'DataController@getDataRaw');
    $router->get('data/preprosessing', 'DataController@getDataPreprosessing');
    $router->get('data/clasification', 'DataController@getClasification');
    $router->get('data/acuracy-etc', 'DataController@getAcuracyEtc');
// });
$router->group(['prefix' => 'dev'], function() use ($router) {
    $router->get('method-baru', 'MethodController@index');
});
