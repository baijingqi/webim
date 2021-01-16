<?php

namespace App\Logic;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserLogic
{
    const TBL_NAME = 'user';

    /**
     * @param $username
     * @param $password
     *
     * @return array
     */
    public static function register($username, $password)
    {
        $user = self::searchUser($username);

        if (!empty($user)) {
            return makeStdRes(-1, '用户名已存在');
        }

        $avatars = config('common.avatars');
        $user    = [
            'username'   => $username,
            'password'   => Hash::make($password),
            'created_at' => time(),
            'avatar'     => $avatars[array_rand($avatars)]
        ];
        $id      = DB::table(self::TBL_NAME)->insertGetId($user);
        if (!$id) {
            return makeStdRes(-1, '注册失败');
        }
        return makeStdRes(1, '注册成功', $id);
    }

    public static function autoLogin(int $uid)
    {
        $user        = self::getUser($uid);
        $user->token = Str::random(40);
        app('redis')->set(makeCacheKey('token', [$uid]), $user->token);
        return $user;
    }

    /**
     * @param string $username
     * @param int    $num
     *
     * @return array
     */
    public static function searchUser(string $username, int $num = 1)
    {
        $where = [
            'username' => $username,
        ];
        return DB::table(self::TBL_NAME)
            ->where($where)
            ->limit($num)
            ->get()->toArray();
    }

    /**
     * @param int $uid
     *
     * @return array|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public static function getUser(int $uid)
    {
        $redis = app('redis');
        if ($user = $redis->get(makeCacheKey('userInfo', [$uid]))) {
            return json_decode($user);
        }
        $user = DB::table(self::TBL_NAME)->select(User::$select)->where('id', $uid)->first();
        if (empty($user)) {
            return [];
        }

        app('redis')->set(makeCacheKey('userInfo', [$uid]), json_encode($user));
        return $user;
    }

    /**
     * @param array $uids
     *
     * @return array
     */
    public static function batchGetUser(array $uids)
    {
        $res  = [];
        $uids = array_filter(array_unique($uids));
        foreach ($uids as $id) {
            $res[$id] = self::getUser($id);
        }
        return $res;
    }

    public static function getUserList()
    {
        return DB::table(self::TBL_NAME)->select(User::$select)
            ->where('status', User::STATUS_NORMAL)
            ->get()->toArray();
    }
}
