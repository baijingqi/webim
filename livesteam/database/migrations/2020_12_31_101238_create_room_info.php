<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roominfo', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string("name",50)->default('')->nullable(false)->comment('房间名称');
            $table->unsignedInteger("owner_uid")->default(0)->nullable(false)->comment('创建人uid');
            $table->unsignedTinyInteger("status")->default(1)->nullable(false)->comment('状态 1：正常 2：解散');
            $table->unsignedInteger("created_at")->default(0)->nullable(false)->comment('创建时间');
            $table->unsignedInteger("updated_at")->default(0)->nullable(false)->comment('上次修改时间');
            $table->unsignedInteger("people_num")->default(0)->nullable(false)->comment('人数');
            $table->unsignedInteger("single_chat_uid")->default(0)->nullable(false)->comment('单聊房间聊天对象uid');
        });
        DB::statement("ALTER TABLE `ls_roominfo` comment '房间表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roominfo', function (Blueprint $table) {
            //
        });
    }
}
