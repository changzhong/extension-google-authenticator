<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleAuthToAdminUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
	        $table->string('google_auth')->nullable()->comment('谷歌密钥');
	        $table->tinyInteger('is_open_google_auth')->nullable()->comment('是否开启谷歌验证登录');
            $table->tinyInteger('enabled')->default(1)->comment('状态(0:禁用,1:正常)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn( 'google_auth' );
            $table->dropColumn( 'is_open_google_auth' );
            $table->dropColumn('enabled');
        });
    }
}
