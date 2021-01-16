<?php

namespace App\Console\Commands;


use App\Logic\ChatServerLogic;
use Illuminate\Console\Command;

class StartChatServerCommand extends Command
{
    /**
     * 命令行执行命令
     *
     * @var string
     */
    protected $signature = 'startChatServer';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '开启聊天服务';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 处理
     */
    public function handle()
    {
        $obj = new ChatServerLogic();
    }
}
