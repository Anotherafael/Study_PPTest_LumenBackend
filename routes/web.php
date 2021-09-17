<?php

use App\Models\User;

$router->get('/', function () use ($router) {
    User::factory()->create(['email' => 'hey@gmail.com']);
    User::factory()->create(['email' => 'hey2@gmail.com']);
    return $router->app->version();
});

$router->post('/auth/{provider}', ['as' => 'authenticate', 'uses' => 'AuthController@postAuthenticate']);

$router->get('/users/me', ['as' => 'usersMe', 'uses' => 'MeController@getMe']);

$router->post('/transactions', ['as' => 'postTransaction', 'uses' => 'TransactionController@postTransaction']);