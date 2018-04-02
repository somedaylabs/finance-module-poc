<?php

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

$router->get('customers', 'CustomerController@index');
$router->get('customers/{id}', 'CustomerController@show');
$router->post('customers', 'CustomerController@create');
$router->get('customers/{id}/billings', 'BillingController@indexByCustomer');
$router->post('customers/{id}/billings', 'BillingController@create');

$router->get('billings/{id}', 'BillingController@show');
