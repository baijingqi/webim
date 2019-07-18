<?php
class socketServer{
    private $ip = '0.0.0.0';
    private $port = 9501;
    public $serv = null;
    public $table;
    public $config;

    /**
     * socketServer constructor.
     */
    public function __construct(){
        $this->serv = new Swoole\WebSocket\Server($this->ip, $this->port);

        $this->serv->on('open', array( $this, 'onOpen') );
        $this->serv->on('message', array( $this, 'onMessage') );
        $this->serv->on('Close', array( $this,'onClose') );

        $this->createTable();

        $this->config = include './config/webim.php';

        $this->serv->start();
        echo "服务启动\n";
    }


    /**
     * @param $serv
     * @param $request
     */
    public function onOpen($serv, $request){
        $key = 'user:'.$request->fd;
        $randNameKey = array_rand($this->config['name']);
        $randAvatarKey = array_rand($this->config['avatar']);
        $arr = [
            'fd' => $request->fd,
            'name' => $this->config['name'][$randNameKey].time(),
            'avatar' => $this->config['avatar'][$randAvatarKey],
            'isme' => 1,
        ];
        $this->table->set($key, $arr);

        $this->serv->push($request->fd, json_encode( [
            'type' => 'user-list',
            'user-list' => array_merge([$arr], $this->getAllUser($arr['fd']))
        ] ));

        $welcomeMessage = "欢迎{$arr['name']}进入聊天室";
        $this->pushMessagesToAll($welcomeMessage, 'welcome-message', $arr);
    }

    /**
     * @param $serv
     * @param $request
     */
    public function onMessage($serv, $request){
        $message = $request->data;
        $fd = $request->fd;
        $key = 'user:'.$request->fd;

        $sendMessageUser = $this->table->get($key);
        echo "接收到信息：". $message.PHP_EOL;

        $this->pushMessagesToAll($message, 'chat-message', $sendMessageUser, $fd);
    }


    /**
     * @param $serv
     * @param $fd
     */
    public function onClose($serv, $fd){
        $key = 'user:'.$fd;
        $userInfo = $this->table->get($key);
        $message = $userInfo['name'] . "离开聊天室";
        $this->table->del($key);
        $this->pushMessagesToAll($message, 'close-message', $userInfo, $fd);
        echo '进程'. $fd.'断开链接'.PHP_EOL;
    }

    /**
     * @param $exceptFd
     * @return array
     */
    public function getAllUser($exceptFd = null){
        $arr = [];
        foreach ($this->table as $row){
            if($row['fd'] == $exceptFd){
                continue;
            }
            $arr[] = $row;
        }
        return $arr;
    }


    /**
     * 推送给所有人
     * @param $message
     * @param $type
     * @param $userInfo
     * @param $exceptFd
     */
    public function pushMessagesToAll($message, $type, $userInfo, $exceptFd = null){
        foreach ($this->table as $row){
            if($row['fd'] != $exceptFd){
                if($this->serv->isEstablished($row['fd'])){
                    $this->serv->push($row['fd'], json_encode([
                        'message' => $message,
                        'type' => $type,
                        'fromId' => $userInfo['fd'],
                        'userinfo' => $userInfo
                    ]));
                }else{
                    $this->serv->close($row['fd']);
                }
            }
        }
    }

    public function createTable(){
        $this->table = new swoole_table(1024);
        $this->table->column('fd', swoole_table::TYPE_INT);
        $this->table->column('name', swoole_table::TYPE_STRING, 30);
        $this->table->column('avatar', swoole_table::TYPE_STRING, 30);
        $this->table->create();
    }
}
$server = new socketServer();