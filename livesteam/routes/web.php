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
$router->post('register', "UserController@register");
$router->group([
    'prefix'     => '',
    'middleware' => 'apiAuth',
], function () use ($router) {
    $router->post('friendList', "UserController@friendList");
    $router->post('userRoomList', "RoomController@userRoomList");
    $router->post('createRoom', "RoomController@createRoom");
    $router->post('roomInfo', "RoomController@roomInfo");
    $router->post('roomChatMsgList', "ChatMsgController@roomChatMsgList");
});
