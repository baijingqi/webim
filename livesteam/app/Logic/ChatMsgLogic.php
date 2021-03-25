<?php

namespace App\Logic;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatMsgLogic
{
    const TBL_NAME = 'chat_message';

    /**
     * @param int $roomId
     * @param int $uid
     * @param int $page
     * @param int $size
     *
     * @return array
     */
    public static function getFormatRoomChatMsgList(int $roomId, int $uid, $page = 10, $size = 10)
    {
        $messages  = self::searchChatMsg($roomId, $uid, $page, $size);
        $userInfos = UserLogic::batchGetUser(array_column($messages, 'uid'));
        foreach ($messages as $key => $value) {
            $value->userInfo     = $userInfos[$value->uid];
            $value->createdAtStr = date('Y-m-d H:i:s', $value->createdAt);
        }
        //清除该房间未读消息数
        if ($page == 1) {
            app('redis')->set(makeCacheKey('unReadRoomMsgCount', [
                $uid,
                $roomId
            ]), 0);
        }
        return $messages;
    }

    /**
     * @param int $roomId
     * @param int $uid
     * @param int $page
     * @param int $size
     *
     * @return array
     */
    public static function searchChatMsg(int $roomId, int $uid, $page = 10, $size = 10)
    {
        return DB::table(self::TBL_NAME)
            ->where('room_id', $roomId)
            ->where('status', ChatMessage::STATUS_NORMAL)
            ->select(ChatMessage::$select)
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * @param int    $uid
     * @param int    $roomId
     * @param string $content
     * @param null   $chatServer
     *
     * @return int|mixed
     */
    public static function addChatMsg(int $uid, int $roomId, string $content, &$chatServer = null)
    {
        $arr = [
            'uid'        => $uid,
            'room_id'    => $roomId,
            'content'    => $content,
            'created_at' => time(),
        ];
        if ($chatServer) {
            $db    = $chatServer->db->table(self::TBL_NAME);
            $redis = $chatServer->redis;
        } else {
            $db    = DB::table(self::TBL_NAME);
            $redis = app('redis');
        }
        $id = $db->insertGetId($arr);
        if (empty($id)) return false;

        $userIds = RoomLogic::getUidByRoomId($roomId, $chatServer);
        //增加该房间用户的未读消息数量
        foreach ($userIds as $key => $id) {
            if ($id != $uid) {
                $redis->incr(makeCacheKey('unReadRoomMsgCount', [
                    $uid,
                    $roomId
                ]));
            }
        }
        return $id;
    }

    /**
     * 添加广播信息
     *
     * @param string $msg
     */
    public static function addBroadcastMsg(string $msg)
    {
        $publicChannel = config('common.chatServer')['public_channel'];
        $redis         = app('redis');
        Log::info("接收到广播消息：".$msg);
        foreach ($publicChannel as $channel) {
            $redis->publish($channel, json_encode([
                'roomId'  => 0,
                'msg'     => $msg,
                'fromUid' => 0,
                'msgType' => ChatServerLogic::MSG_BROADCAST,
                'toUid'   => 0,
            ]));
        }
    }

}
