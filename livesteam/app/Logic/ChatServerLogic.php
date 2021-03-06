<?php

namespace App\Logic;

use Swoole\Http\Request;
use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;

class ChatServerLogic
{
    public  $server = null;
    public  $config;
    private $redis  = null;

    const MSG_CHAT        = 1; //消息类型 聊天
    const MSG_WELCOME     = 2; //欢迎消息
    const MSG_CLOSE       = 3; //离开消息
    const MSG_NOTICE      = 4; //通知消息
    const MSG_CREATE_ROOM = 5; //创建房间消息


    /**
     * socketServer constructor.
     */
    public function __construct()
    {
        $config       = config('common.chatServer');
        $this->server = new Server($config['ip'], $config['port']);

        $this->server->on('open', [
            $this,
            'onOpen'
        ]);
        $this->server->on('message', [
            $this,
            'onMessage'
        ]);
        $this->server->on('Close', [
            $this,
            'onClose'
        ]);
        $this->server->on('task', [
            $this,
            'onTask'
        ]);

        $this->server->set([
            'task_worker_num' => 2,
            'worker_num'      => 2
        ]);

        $this->redis = app('redis');
        echo "服务启动\n";
        $this->server->start();
    }

    /**
     * @param $server
     * @param $request
     */
    public function onOpen(Server $server, Request $request)
    {
        $uid   = $request->get['uid'] ?? 0;
        $token = $request->get['token'] ?? '';
        if (empty($uid) || empty($token)) {
            $cacheToken = $this->redis->get(makeCacheKey('token', $uid));
            if (empty($cacheToken) || $cacheToken != $token) {
                $this->server->push($request->fd, json_encode(makeStdRes(-1, 'token错误')));
            }
            $this->server->push($request->fd, json_encode(makeStdRes(-1, '缺失参数')));
            $this->server->close($request->fd);
            echo "已拒绝无效链接！ \n";
            return;
        }
        $this->openInit($request->fd, $uid);
    }

    /**
     * 初始化连接参数
     *
     * @param $fd
     * @param $uid
     */
    public function openInit($fd, $uid)
    {
        $this->redis->set(makeCacheKey('fdUid', [$fd]), $uid);  //通过文件描述符id找到对应uid
        $this->redis->set(makeCacheKey('uidFd', [$uid]), $fd);  //通过uid找到对应文件描述符id

        //将用户的uid加入房间的redis集合中
        $roomIds = RoomLogic::getUserRoomIds($uid);
        foreach ($roomIds as $id) {
            $cacheKey = makeCacheKey('roomConnectionInfo', [$id]);
            $this->redis->sAdd($cacheKey, $uid . '-' . $fd);
        }
    }

    /**
     * 关闭连接后数据清除
     *
     * @param $fd
     * @param $uid
     */
    public function closeFlush($fd, $uid)
    {
        $this->redis->del(makeCacheKey('fdUid', [$fd]));  //通过文件描述符id找到对应uid
        $this->redis->del(makeCacheKey('uidFd', [$uid]));  //通过uid找到对应文件描述符id

        //将用户的uid从房间的redis中移除
        $roomIds = RoomLogic::getUserRoomIds($uid);
        foreach ($roomIds as $id) {
            $cacheKey = makeCacheKey('roomConnectionInfo', [$id]);
            $this->redis->sRem($cacheKey, $uid . '-' . $fd);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame  $frame
     */
    public function onMessage(Server $server, Frame $frame)
    {
        echo "接到信息" . $frame->data . PHP_EOL;

        $requestData = json_decode($frame->data, true);
        $userInfo    = json_decode($requestData['userInfo']);
        $type        = $requestData['type'];
        switch ($type) {
            //聊天消息
            case self::MSG_CHAT:
                $roomId = $requestData['data']['roomId'];
                ChatMsgLogic::addChatMsg(intval($userInfo->id), intval($roomId), $requestData['data']['chatMsg']);
                $this->server->task([
                    $roomId,
                    $requestData['data']['chatMsg'],
                    $userInfo->id,
                    self::MSG_CHAT
                ]);
                break;

        }

    }


    /**
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        $cacheKey = makeCacheKey('fdUid', [$fd]);
        $uid      = $this->redis->get($cacheKey);
        if (!$uid) {
            return;
        }
        $this->closeFlush($fd, $uid);
    }

    public function onTask($server, $task_id, $from_id, $data)
    {
        [
            $roomId,
            $message,
            $msgSenderUid,
            $msgType
        ] = $data;
        $msgSender = UserLogic::getUser($msgSenderUid);
        switch ($msgType) {
            case self::MSG_CHAT:
                $roomConnections = $this->redis->sMembers(makeCacheKey('roomConnectionInfo', [$roomId]));
                var_dump($roomConnections);
                foreach ($roomConnections as $value) {
                    [
                        $uid,
                        $fd
                    ] = explode('-', $value);
                    if ($uid != $msgSenderUid) {
                        echo "向{$fd}发送{$message}" . PHP_EOL;
                        $this->server->push($fd, $this->makeResponseMsg($msgType, $message, [
                            'msgSender' => $msgSender,
                            'roomId'    => $roomId
                        ]));
                    }
                }
                break;
        }

    }

    public function makeResponseMsg(int $type, $message, array $data = [])
    {
        return json_encode([
            'type'    => $type,
            'message' => $message,
            'data'    => $data
        ]);
    }
}
