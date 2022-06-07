<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Dashboard
$router->get('dashboard', 'DashboardController@index');

$router->get('tests', 'TestController@index');
$router->post('tests/downloading', ['uses' => 'TestController@downloading']);

$router->post('downloadzip', ['uses' => 'DownloadZip@downloading']);

$router->get('vdownloads', 'VDownloadController@index');
$router->post('vdownloads/downloading', ['uses' => 'VDownloadController@downloading']);

$router->post('register', ['uses' => 'AuthController@register']);
$router->post('login', ['uses' => 'AuthController@login']);
$router->post('listusser', ['uses' => 'UserController@listusser']);

//Payments
$router->post('paymentlist', 'PaymentController@paymentlist');
$router->post('payments', 'PaymentController@store');
$router->put('payments/{id}', 'PaymentController@update');
$router->delete('payments/{id}', 'PaymentController@destroy');
$router->post('listtablebyuser', 'PaymentController@listtablebyuser');

//Customers
$router->post('customerlist', 'ClienteAuditwholeController@customerlist');
$router->post('customers', ['uses' => 'ClienteAuditwholeController@store']);
$router->post('customers/update', ['uses' => 'ClienteAuditwholeController@update']);

//Ruta que genera el ATS
$router->get('archivos', ['uses' => 'AtsController@index']);
$router->get('newarchivos', ['uses' => 'GenerateController@index']);

//Guardar archivo
$router->post('archivos', ['uses' => 'ArchivoController@store']);
$router->get('archivos/show', ['as' => 'show', 'uses' => 'ArchivoController@show']);
$router->get('archivos/showtype', ['as' => 'showtype', 'uses' => 'ArchivoController@showtype']);

$router->post('contactos', ['uses' => 'ContactoController@update']);
$router->post('contactosgetmasive', ['uses' => 'ContactoController@getmasive']);
$router->post('contactos/store', ['uses' => 'ContactoController@store']);
$router->get('contactos/{id}', 'ContactoController@show');

//SendToSri
$router->get('sends', ['uses' => 'SendToSriController@index']);

$router->post('reportcompra', ['uses' => 'ReportComprasController@report']);

$router->post('reportventa', ['uses' => 'ReportVentasController@report']);
