<?php

namespace App\Logic;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;

class ChatServerLogic
{
    public  $config;
    private $tmpRedis   = null;
    private $serverName = null;

    const MSG_CHAT           = 1; //消息类型 聊天
    const MSG_WELCOME        = 2; //欢迎消息
    const MSG_CLOSE          = 3; //离开消息
    const MSG_NOTICE         = 4; //通知消息
    const MSG_CREATE_ROOM    = 5; //创建房间消息
    const MSG_BROADCAST      = 6; //广播消息
    const MSG_LISTEN_CHANNEL = 7; //当前服务监听频道消息

    /**
     * socketServer constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->init($params);
        $this->start();
    }

    /**
     * 初始化服务
     *
     * @param array $params
     */
    private function init($params = [])
    {
        $this->config = config('common.chatServer');
        if (isset($params['port'])) $this->config['port'] = $params['port'];
        if (isset($params['ip'])) $this->config['ip'] = $params['ip'];

        $this->makeServerName();                                //服务名称
        $this->config['public_channel'][] = $this->serverName;  //监听频道

        $this->makeTmpRedis();
//        $res = $this->beforeStartServer();
//        if ($res['status'] < 0) exit($res['message']);
        $this->registerServer();
        $this->closeTmpRedis();
    }

    private function start()
    {
        $server = new Server($this->config['ip'], $this->config['port']);
        $server->on('open', [
            $this,
            'onOpen'
        ]);
        $server->on('message', [
            $this,
            'onMessage'
        ]);
        $server->on('Close', [
            $this,
            'onClose'
        ]);
        $server->on('task', [
            $this,
            'onTask'
        ]);
        $server->on('WorkerStart', [
            $this,
            'onWorkerStart'
        ]);
        $server->set([
            'task_worker_num'          => 2,
            'worker_num'               => 3,
            'heartbeat_check_interval' => 5,
            'heartbeat_idle_time'      => 600,
        ]);
        echo $this->serverName . '服务启动' . PHP_EOL;
        $server->start();
    }

    private function makeServerName()
    {
        $this->serverName = getHostByName(getHostName()) . '-' . $this->config['port']; //以本机ip+端口作为唯一识别号
    }

    private function makeTmpRedis()
    {
        $this->tmpRedis = new \Redis();
        $this->tmpRedis->connect(env('REDIS_HOST'), env('REDIS_PORT'));
        if (!empty(env('REDIS_PASSWORD'))) $this->tmpRedis->auth(env('REDIS_PASSWORD'));
    }

    private function closeTmpRedis()
    {
        $this->tmpRedis->close();
    }

    public function onWorkerStart(Server $server, $workerId)
    {
        $server->redis = new \Redis();
        $server->redis->pconnect(env('REDIS_HOST'), env('REDIS_PORT'));
        $server->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $server->db = DB::connection('mysql');

        if (!$server->taskworker && $workerId == 0) {
            echo $workerId . "号 worker 开始监听" . PHP_EOL;

            $server->redis->subscribe($this->config['public_channel'], function ($redis, $chan, $msg) use (&$server) {
                echo "接收到订阅消息" . var_export($msg, true) . PHP_EOL;
                $server->task(json_decode($msg, true));
            });
        }
        echo $workerId . "号 worker ready...." . PHP_EOL;
    }

    /**
     * @return array
     */
    public function beforeStartServer()
    {
        if ($this->tmpRedis->sIsMember(makeCacheKey("chatServerList"), $this->serverName)) {
            return makeStdRes(-1, '服务名' . $this->serverName . '已被占用');
        }
        return makeStdRes(1, '');
    }


    public function getIp()
    {
        return getHostByName(getHostName());
    }

    public function registerServer()
    {
        $this->tmpRedis->sAdd(makeCacheKey("chatServerList"), $this->serverName);
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
            $cacheToken = $server->redis->get(makeCacheKey('token', $uid));
            if (empty($cacheToken) || $cacheToken != $token) {
                $server->push($request->fd, json_encode(makeStdRes(-1, 'token错误')));
            }
            $server->push($request->fd, json_encode(makeStdRes(-1, '缺失参数')));
            $server->close($request->fd);
            echo "已拒绝无效链接！ \n";
            return;
        }
        $server->redis->set(makeCacheKey('uidBelongServer', [$uid]), $this->serverName);  //通过uid找到对应服务
        $server->redis->sAdd(makeCacheKey('serverUsers', [$this->serverName]), $uid . '-' . $request->fd);  //通过服务获取用户
        $server->redis->set(makeCacheKey('fdUid', [$request->fd]), $uid);  //通过文件描述符id找到对应uid
        $server->redis->set(makeCacheKey('uidFd', [$uid]), $request->fd);  //通过uid找到对应文件描述符id

        //将用户的uid加入房间的redis集合中
        $roomIds = $this->getUserRoomIds($uid, $server);
        foreach ($roomIds as $id) {
            $cacheKey = makeCacheKey('roomConnectionInfoUser', [$id]);
            $server->redis->sAdd($cacheKey, $uid);
        }
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame  $frame
     */
    public function onMessage(Server $server, Frame $frame)
    {

        $requestData = json_decode($frame->data, true);
        $userInfo    = json_decode($requestData['userInfo']);
        $type        = $requestData['type'];
        switch ($type) {
            //聊天消息
            case self::MSG_CHAT:
                echo "接到信息" . $frame->data . PHP_EOL;
                $roomId = $requestData['data']['roomId'];

                $this->addChatMsg(intval($userInfo->id), intval($roomId), $requestData['data']['chatMsg'], $server);
                $res = $server->task($this->makeTaskPushData($roomId, $requestData['data']['chatMsg'], $userInfo->id, self::MSG_CHAT));
                break;
            default:
                break;
        }
    }

    /**
     * @param     $roomId
     * @param     $msg
     * @param     $fromUid
     * @param     $msgType
     * @param int $toUid
     *
     * @return array
     */
    public function makeTaskPushData($roomId, $msg, $fromUid, $msgType, $toUid = 0)
    {
        return [
            'roomId'  => $roomId,
            'msg'     => $msg,
            'fromUid' => $fromUid,
            'msgType' => $msgType,
            'toUid'   => $toUid,
        ];
    }

    /**
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        $cacheKey = makeCacheKey('fdUid', [$fd]);
        $uid      = $server->redis->get($cacheKey);
        if (!$uid) {
            return;
        }

        $server->redis->del(makeCacheKey('fdUid', [$fd]));
        $server->redis->del(makeCacheKey('uidFd', [$uid]));
        $server->redis->del(makeCacheKey('uidBelongServer', [$uid]));
        $server->redis->sRem(makeCacheKey('serverUsers', [$this->serverName]), $uid . '-' . $fd);

        //将用户的uid从房间的redis中移除
        $roomIds = $this->getUserRoomIds($uid, $server);
        foreach ($roomIds as $id) {
            $cacheKey = makeCacheKey('roomConnectionInfoUser', [$id]);
            $server->redis->sRem($cacheKey, $uid);
        }
    }

    /**
     * @param $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask(Server $server, $task_id, $from_id, $data)
    {
        $roomId  = $data['roomId'];
        $message = $data['msg'];
        $fromUid = $data['fromUid'];
        $toUid   = $data['toUid'];
        $msgType = $data['msgType'];

        $msgSender = empty($fromUid) ? [] : $this->getUser($fromUid, $server);
        $sendData  = $this->makeResponseMsg($msgType, $message, [
            'msgSender' => $msgSender,
            'roomId'    => $roomId
        ]);
        switch ($msgType) {
            case self::MSG_CHAT:
                $cacheKey        = makeCacheKey('roomConnectionInfoUser', [$roomId]);
                $roomConnections = $server->redis->sMembers($cacheKey);
                if (empty($roomConnections)) return;

                foreach ($roomConnections as $uid) {
                    $fd         = $server->redis->get(makeCacheKey('uidFd', [$uid]));
                    $serverName = $server->redis->get(makeCacheKey('uidBelongServer', [$uid]));
                    if ($uid != $fromUid) {
                        if ($serverName != $this->serverName) {
                            echo "当前taskId:{$task_id}, 群聊信息：向 $serverName 频道投递任务 {$message}" . PHP_EOL;
                            $arr            = $data;
                            $arr['msgType'] = self::MSG_LISTEN_CHANNEL;
                            $arr['toUid']   = $uid;
                            $server->redis->publish($serverName, json_encode($arr));
                        } else {
                            if ($server->exist($fd)) {
                                $server->push($fd, $sendData);
                                echo "当前taskId:{$task_id}, 群聊信息：向 $fd 发送 {$message}" . PHP_EOL;
                            } else {
                                echo "当前taskId:{$task_id} 群聊信息： {$fd} 不存在已删除" . PHP_EOL;
                                $server->redis->sRem($cacheKey, $value);
                            }
                        }
                    }
                }
                break;
            case self::MSG_BROADCAST:
                $cacheKey = makeCacheKey('serverUsers', [$this->serverName]);
                $users    = $server->redis->sMembers($cacheKey);
                foreach ($users as $value) {
                    [$uid, $fd] = explode('-', $value);
                    if ($server->exist($fd)) {
                        echo "当前taskId:{$task_id} 广播信息：向{$fd}发送{$message}" . PHP_EOL;
                        $server->push($fd, $sendData);
                    } else {
                        echo "当前taskId:{$task_id} 广播信息： {$fd} 不存在已删除" . PHP_EOL;
                        $server->redis->sRem($cacheKey, $value);
                    }
                }
                break;
            case self::MSG_LISTEN_CHANNEL:
                $fd       = $server->redis->get(makeCacheKey('uidFd', [$toUid]));
                $sendData = $this->makeResponseMsg(self::MSG_CHAT, $message, [
                    'msgSender' => $msgSender,
                    'roomId'    => $roomId
                ]);
                if ($server->exist($fd)) {
                    echo "当前taskId:{$task_id} 订阅消息：向{$fd}发送{$message}" . PHP_EOL;
                    $server->push($fd, $sendData);
                }
                break;
            case self::MSG_CREATE_ROOM:
                $users = $this->getUidByRoomId($roomId, $server);

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

    /**
     * 获取用户加入的房间
     *
     * @param $userId
     * @param $server
     *
     * @return array
     */
    public function getUserRoomIds($userId, &$server)
    {
        $cacheKey = makeCacheKey('userRoomIds', [$userId]);
        $ids      = $server->redis->sMembers($cacheKey);
        if ($ids) {
            return $ids;
        }

        $ids = $server->db->table('room_user')->where('uid', $userId)->select('room_id')->get()->toArray();
        $res = [];
        foreach ($ids as $value) {
            $server->redis->sAdd($cacheKey, $value->room_id);
            $res[] = $value->room_id;
        }
        return $res;
    }

    /**
     * @param int $uid
     * @param     $server
     *
     * @return array|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function getUser(int $uid, &$server)
    {
        if ($user = $server->redis->get(makeCacheKey('userInfo', [$uid]))) {
            return json_decode($user);
        }
        $user = $server->db->table('user')->select(User::$select)->where('id', $uid)->first();
        if (empty($user)) {
            return [];
        }

        $server->redis->set(makeCacheKey('userInfo', [$uid]), json_encode($user));
        return $user;
    }

    /**
     * @param int    $uid
     * @param int    $roomId
     * @param string $content
     * @param        $server
     *
     * @return int|mixed
     */
    public function addChatMsg(int $uid, int $roomId, string $content, $server)
    {
        $arr = [
            'uid'        => $uid,
            'room_id'    => $roomId,
            'content'    => $content,
            'created_at' => time(),
        ];
        $id  = $server->db->table('chat_message')->insertGetId($arr);
        if (empty($id)) return false;

        $userIds = $this->getUidByRoomId($roomId, $server);
        //增加该房间用户的未读消息数量
        foreach ($userIds as $key => $id) {
            if ($id != $uid) {
                $server->redis->incr(makeCacheKey('unReadRoomMsgCount', [
                    $uid,
                    $roomId
                ]));
            }
        }
        return $id;
    }

    /**
     * 获取房间的用户id
     *
     * @param int $roomId
     * @param     $server
     *
     * @return array
     */
    public function getUidByRoomId(int $roomId, &$server)
    {
        $cacheKey = makeCacheKey('roomUserIds', [$roomId]);
        $uids     = $server->redis->sMembers($cacheKey);
        if ($uids) {
            return $uids;
        }

        $uids = $server->db->table('room_user')->where('room_id', $roomId)->select('uid')->get()->toArray();
        $res  = [];
        foreach ($uids as $value) {
            $server->redis->sAdd($cacheKey, $value->uid);
            $res[] = $value->uid;
        }
        return $res;
    }
}
