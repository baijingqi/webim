<?php
return [
    'token'              => 'token:%d',  //uid
    'userInfo'           => 'userInfo:%d',    //uid
    'roomInfo'           => 'roomInfo:%d',    //roomId
    'userRoomIds'        => 'userRoomIds:%d', //uid
    'roomUserIds'        => 'roomUserIds:%d', //roomId
    'fdUid'              => 'fdUid:%d',  //fd
    'uidFd'              => 'uidFd:%d',  //uid
    'unReadRoomMsgCount' => 'unReadRoomMsgCount:%d:%d',  //uid roomId
    'roomConnectionInfo' => 'roomConnectionInfo:%d', //roomId

    'chatServerList'  => 'chatServerList',     //ip  port 服务开启后，将ip与端口注册到该集合
    'chatServer'      => 'chatServer:%d:%d',     //ip  port 服务开启后，将ip与端口注册到该集合
    'uidBelongServer' => 'uidBelongServer:%d',     // uid
];
