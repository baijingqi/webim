<?php

namespace App\Http\Middleware;

use App\Logic\UserLogic;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Redis;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->header('token') ?? $request->post('token');
        $uid   = $request->header('uid') ?? $request->post('uid');
        if (empty($token) || empty($uid)) {
            return makeStdJson([], 401, '缺失参数');
        }
        $cacheToken = app('redis')->get(makeCacheKey('token', $uid));

        if (empty($cacheToken) || $cacheToken != $token) {
            return makeStdJson([], 401, 'token错误');
        }

        $userInfo = app('redis')->get(makeCacheKey('userInfo', $uid));
        if (empty($userInfo)) {
            $userInfo = UserLogic::getUser($uid);
            if (empty($userInfo)) {
                return makeStdJson([], 401, '获取用户信息失败！');
            }
        } else {
            $userInfo = json_decode($userInfo, true);
        }
        $request = $request->merge(['user' => $userInfo]);
        return $next($request);
    }
}
