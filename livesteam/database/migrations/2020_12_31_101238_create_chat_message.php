<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_message', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->unsignedInteger("room_id")->default(0)->nullable(false)->comment('房间id')->index('CM_ROOM_ID');
            $table->unsignedInteger("uid")->default(0)->nullable(false)->comment('信息发送人id')->index('CM_ROOM_UID');
            $table->unsignedTinyInteger("status")->default(1)->nullable(false)->comment('1 正常 2撤回');
            $table->string("content", 2000)->default('')->nullable(false)->comment('内容');
            $table->unsignedInteger("created_at")->default(0)->nullable(false)->comment('创建时间');
            $table->unsignedInteger("updated_at")->default(0)->nullable(false)->comment('上次修改时间');
        });
        DB::statement("ALTER TABLE `ls_chat_message` comment '聊天记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat_message', function (Blueprint $table) {
            //
        });
    }
}
