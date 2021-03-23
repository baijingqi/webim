<?php

namespace App\Logic;

use App\Models\RoomInfo;
use Illuminate\Support\Facades\DB;

class RoomLogic
{
    const TBL_NAME = 'room_user';
    const TBL_ROOM = 'roominfo';

    /**
     * 获取用户加入的聊天房间
     *
     * @param int $uid
     *
     * @return array
     */
    public static function getUserRoomList(int $uid)
    {
        $roomIds = DB::table(self::TBL_NAME)
            ->where('uid', $uid)
            ->select('room_id as roomId')
            ->get()->toArray();
        foreach ($roomIds as $key => &$value) {
            $value = self::roomDetail($value->roomId, $uid);
        }
        return $roomIds;
    }

    /**
     * 房间用户头像封面图
     *
     * @param int $roomId
     *
     * @return array
     */
    public static function roomCoverUserAvatars(int $roomId)
    {
        $data  = self::getUidByRoomId($roomId);
        $res   = [];
        $count = 0;
        foreach ($data as $uid) {
            $res[] = UserLogic::getUser($uid);
            $count++;
            if ($count >= 9) {
                break;
            }
        }
        return $res;
    }

    /**
     * 获取房间的用户id
     *
     * @param int $roomId
     *
     * @return array
     */
    public static function getUidByRoomId(int $roomId)
    {
        $cacheKey = makeCacheKey('roomUserIds', [$roomId]);
        $redis    = app('redis');
        $uids     = $redis->sMembers($cacheKey);
        if ($uids) {
            return $uids;
        }

        $uids = DB::table(self::TBL_NAME)->where('room_id', $roomId)->select('uid')->get()->toArray();
        $res  = [];
        foreach ($uids as $value) {
            $redis->sAdd($cacheKey, $value->uid);
            $res[] = $value->uid;
        }
        return $res;
    }

    /**
     * 获取用户加入的房间id
     *
     * @param int $userId
     *
     * @return array
     */
    public static function getUserRoomIds(int $userId)
    {
        $cacheKey = makeCacheKey('userRoomIds', [$userId]);
        $redis    = app('redis');
        $ids      = $redis->sMembers($cacheKey);
        if ($ids) {
            return $ids;
        }

        $ids = DB::table(self::TBL_NAME)->where('uid', $userId)->select('room_id')->get()->toArray();
        $res = [];
        foreach ($ids as $value) {
            $redis->sAdd($cacheKey, $value->room_id);
            $res[] = $value->room_id;
        }
        return $res;
    }

    /**
     * 创建房间
     *
     * @param int   $ownerUid
     * @param array $userIds
     *
     * @return array
     */
    public static function createRoom(int $ownerUid, array $userIds)
    {
        $ownerChatUsers = [];
        foreach ($userIds as $userId) {
            if ($userId != $ownerUid) {
                $ownerChatUsers[] = $userId;
            }
        }
        $singleChatUid = 0;
        if (count($ownerChatUsers) == 1) {
            $roomInfo = self::getSingleChatRoom($ownerUid, $ownerChatUsers[0]);
            if (!empty($roomInfo)) {
                return makeStdRes(1, '', [
                    'roomId'          => $roomInfo->id,
                    'isNewCreateRoom' => false
                ]);
            }
            $singleChatUid = $ownerChatUsers[0];
        }

        $arr    = [
            'owner_uid'       => $ownerUid,
            'created_at'      => time(),
            'people_num'      => count($userIds),
            'single_chat_uid' => $singleChatUid
        ];
        $roomId = DB::table(self::TBL_ROOM)->insertGetId($arr);
        self::addUserToRoom($userIds, $roomId);
        return makeStdRes(1, '', [
            'roomId'          => $roomId,
            'isNewCreateRoom' => true
        ]);
    }

    /**
     * 根据房间主人和聊天对象获取单聊房间信息
     *
     * @param int $ownerId
     * @param int $chatObjUid
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public static function getSingleChatRoom(int $ownerId, int $chatObjUid)
    {
        return DB::table(self::TBL_ROOM)->where('owner_uid', $ownerId)
            ->where('status', RoomInfo::STATUS_NORMAL)
            ->where('single_chat_uid', $chatObjUid)
            ->first();
    }

    /**
     * 添加用户到房间
     *
     * @param array $userIds
     * @param int   $roomId
     */
    public static function addUserToRoom(array $userIds, int $roomId)
    {
        $redis        = app('redis');
        $roomCacheKey = makeCacheKey('roomUserIds', [$roomId]);
        foreach ($userIds as $userId) {
            DB::table(self::TBL_NAME)->insert([
                'room_id'    => $roomId,
                'uid'        => $userId,
                'created_at' => time()
            ]);
            //将roomId添加到用户加入房间的redis集合中
            $redis->sAdd(makeCacheKey('userRoomIds', [$userId]), $roomId);
            $redis->sAdd($roomCacheKey, $userId);
        }

        //将用户加入到redis连接集合中
        //将当前在线的用户的uid加入房间的redis集合中
        $userIds       = RoomLogic::getUidByRoomId($roomId);
        $connectionKey = makeCacheKey('roomConnectionInfo', [$roomId]);
        foreach ($userIds as $uid) {
            $fd = $redis->get(makeCacheKey('uidFd', [$uid]));
            if ($fd) {
                $redis->sAdd($connectionKey, $uid . '-' . $fd);
            }
        }
    }

    /**
     * 获取房间数据库信息
     *
     * @param int $roomId
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public static function getRoomInfo(int $roomId)
    {
        $redis    = app('redis');
        $cacheKey = makeCacheKey('roomInfo', [$roomId]);
        $info     = $redis->hGetAll($cacheKey);
        if ($info) {
            return (object)$info;
        }

        $info = DB::table(self::TBL_ROOM)->where('id', $roomId)
            ->select(RoomInfo::$select)
            ->first();
        if ($info) {
            $redis->hMSet($cacheKey, (array)$info);
        }
        return $info;
    }

    /**
     * 房间详细信息
     *
     * @param int $roomId
     * @param     $curLoginUid
     *
     * @return \stdClass
     */
    public static function roomDetail(int $roomId, $curLoginUid)
    {
        $redis           = app('redis');
        $res             = new \stdClass();
        $res->coverUsers = self::roomCoverUserAvatars($roomId);
        $res->roomInfo   = self::getRoomInfo($roomId);

        $res->unReadMsgCount = intval($redis->get(makeCacheKey('unReadRoomMsgCount', [
            $curLoginUid,
            $roomId
        ])));
        //封面用户信息
        if (empty($res->roomInfo->name)) {
            if (count($res->coverUsers) > 2) {
                $res->roomInfo->name = implode(',', array_column($res->coverUsers, 'username'));
            } else {
                $name = [];
                foreach ($res->coverUsers as $user) {
                    if ($user->id != $curLoginUid) {
                        $name[] = $user->username;
                    }
                }
                $res->roomInfo->name = implode(',', $name);
            }
        }
        return $res;
    }
}
