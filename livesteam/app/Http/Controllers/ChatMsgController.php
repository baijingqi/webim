<?php

namespace App\Http\Controllers;

use App\Logic\ChatMsgLogic;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ChatMsgController extends Controller
{
    public function roomChatMsgList(Request $request)
    {
        $params    = $request->all();
        $validator = Validator::make($params, [
            'roomId' => 'bail|required|numeric',
            'page'   => 'bail|required|numeric',
            'size'   => 'bail|required|numeric',
        ], ['参数错误']);
        if ($validator->fails()) {
            return makeStdJson([], Response::HTTP_UNAUTHORIZED, $validator->errors()->first());
        }
        $uid = $request['user']['id'];
        return makeStdJson(ChatMsgLogic::getFormatRoomChatMsgList($request['roomId'], $uid, $request['page'], $request['size']));
    }

}
