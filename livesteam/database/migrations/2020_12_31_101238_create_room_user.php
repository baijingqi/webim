<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_user', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->unsignedInteger("room_id")->default(0)->nullable(false)->comment('房间id')->index('RU_ROOM_ID');
            $table->unsignedInteger("uid")->default(0)->nullable(false)->comment('参与人uid')->index('RU_ROOM_UID');
            $table->unsignedInteger("created_at")->default(0)->nullable(false)->comment('创建时间');
            $table->unsignedInteger("updated_at")->default(0)->nullable(false)->comment('上次修改时间');
        });
        DB::statement("ALTER TABLE `ls_room_user` comment '房间用户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('room_user', function (Blueprint $table) {
            //
        });
    }
}
