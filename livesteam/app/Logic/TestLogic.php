<?php

namespace App\Logic;


use Illuminate\Support\Facades\Log;

class TestLogic
{

    public function __construct($msg)
    {
        Log::info("测试消息:".$msg);
    }
}
