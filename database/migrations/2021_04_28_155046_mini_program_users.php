<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MiniProgramUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mini_program_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('open_id',64)->comment('OpenId');
            $table->string('provider', 32)->comment('平台');
            $table->string('union_id')->index()->nullable()->comment('UnionID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID');
            $table->string('name')->nullable()->comment('Name');
            $table->string('nickname')->nullable()->comment('NickName');
            $table->string('email')->nullable()->comment('email');
            $table->string('mobile',11)->nullable()->comment('mobile');
            $table->string('avatar')->nullable()->comment('avatar');
            $table->json('data')->nullable()->comment('Data');
            $table->timestamps();

            $table->unique(['provider', 'open_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mini_program_users');
    }
}
