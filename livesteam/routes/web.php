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
    //好友列表
    $router->post('friendList', "UserController@friendList");
    //用户房间列表
    $router->post('userRoomList', "RoomController@userRoomList");
    //创建房间
    $router->post('createRoom', "RoomController@createRoom");
    //获取房间信息
    $router->post('roomInfo', "RoomController@roomInfo");
    //获取聊天记录
    $router->post('roomChatMsgList', "ChatMsgController@roomChatMsgList");
});
//发送广播消息
$router->post('addBroadcastMsg', "ChatMsgController@addBroadcastMsg");
