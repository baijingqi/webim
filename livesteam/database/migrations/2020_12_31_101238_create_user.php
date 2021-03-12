<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string("username", 30)->default('')->nullable(false)->comment('用户名');
            $table->string("password", 60)->default('')->nullable(false)->comment('密码');
            $table->unsignedInteger("status")->default(1)->nullable(false)->comment('状态 1：正常');
            $table->string("avatar",150)->default('')->nullable(false)->comment('头像');
            $table->unsignedInteger("created_at")->default(0)->nullable(false)->comment('创建时间');
            $table->unsignedInteger("updated_at")->default(0)->nullable(false)->comment('上次修改时间');
        });
        DB::statement("ALTER TABLE `ls_user` comment '用户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            //
        });
    }
}
