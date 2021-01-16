<?php

namespace App\Http\Controllers;

use App\Logic\UserLogic;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $params    = $request->all();
        $validator = Validator::make($params, [
            'username' => 'bail|required|string',
            'password' => 'bail|required|string',
        ], ['参数错误']);
        if ($validator->fails()) {
            return makeStdJson([], Response::HTTP_UNAUTHORIZED, $validator->errors()->first());
        }
        $userInfo = UserLogic::searchUser($params['username']);

        if (!empty($userInfo)) {
            $userInfo = $userInfo[0];
            if (!Hash::check($params['password'], $userInfo->password)) {
                return makeStdJson([], Response::HTTP_UNAUTHORIZED, '密码错误');
            }
            $uid = $userInfo->id;
        } else {
            $res = UserLogic::register($params['username'], $params['password']);
            if ($res['status'] < 0) {
                return makeStdJson([], Response::HTTP_PRECONDITION_FAILED, $res['message']);
            }
            $uid = $res['data'];
        }
        $userInfo = UserLogic::autoLogin($uid);
        return makeStdJson([
            'uid'      => $uid,
            'token'    => $userInfo->token,
            'userInfo' => $userInfo
        ]);
    }

    public function friendList()
    {
        return makeStdJson(UserLogic::getUserList());
    }

}
