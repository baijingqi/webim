<?php

namespace App\Http\Controllers;

use App\Logic\RoomLogic;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function userRoomList(Request $request)
    {
        return makeStdJson(RoomLogic::getUserRoomList($request['user']['id']));
    }

    public function createRoom(Request $request)
    {
        $params    = $request->all();
        $validator = Validator::make($params, [
            'userIds' => 'bail|required|string',
        ], ['参数错误']);
        if ($validator->fails()) {
            return makeStdJson([], Response::HTTP_UNAUTHORIZED, $validator->errors()->first());
        }

        $userInfo                    = app('request')->get('user');
        $userIds                     = explode(',', $request['userIds']);
        $res                         = RoomLogic::createRoom($userInfo['id'], $userIds);
        $roomId                      = $res['data']['roomId'];
        $roomDetail                  = RoomLogic::roomDetail($roomId, $userInfo['id']);
        $roomDetail->isNewCreateRoom = $res['data']['isNewCreateRoom'];
        return makeStdJson($roomDetail);
    }

    public function roomInfo(Request $request){
        $params    = $request->all();
        $validator = Validator::make($params, [
            'roomId' => 'bail|required|numeric',
        ], ['参数错误']);
        if ($validator->fails()) {
            return makeStdJson([], Response::HTTP_UNAUTHORIZED, $validator->errors()->first());
        }
        $userInfo                    = app('request')->get('user');
        $roomDetail                  = RoomLogic::roomDetail($params['roomId'], $userInfo['id']);
        return makeStdJson($roomDetail);
    }

}
