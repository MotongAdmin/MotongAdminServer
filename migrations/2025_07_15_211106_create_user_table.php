<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('user_id')->comment("用户唯一ID");
            $table->string("username",64)->comment("用户名");
            $table->string("nickname")->nullable()->comment("用户昵称");
            $table->string("avatar")->nullable()->comment("头像");
            $table->string("password",256)->nullable()->comment("密码");
            $table->string("mobile",11)->nullable()->comment("手机号");
            $table->string("wechat",32)->nullable()->comment("微信");
            $table->string("work_wechat",32)->nullable()->comment("企业微信");
            $table->string("email",64)->nullable()->comment("邮箱");
            $table->string('token', 500)->nullable()->comment('令牌');
            $table->dateTime('token_expire')->nullable()->comment('令牌过期时间');
            $table->dateTime('token_refresh_expire')->nullable()->comment('令牌可刷新过期时间');
            $table->dateTime('last_login_time')->nullable()->comment('上次登录时间');
            $table->bigInteger("role_id")->default(0)->comment("普通用户");
            $table->unsignedBigInteger('dept_id')->nullable()->comment('部门ID');
            $table->unsignedBigInteger('post_id')->nullable()->comment('职位ID');
            $table->tinyInteger('status')->default(1)->comment('用户状态 1:启用 0:禁用');
            
            $table->unique("username");
            $table->unique('token');
            $table->unique("mobile");
            $table->index("nickname");
            $table->index('status');
            $table->index('last_login_time', 'idx_user_last_login_time');
            $table->index('token_expire', 'idx_user_token_expire');
            // 邮箱索引用于邮箱唯一性检查和查询
            $table->index('email', 'idx_user_email');
            // 复合索引用于常用查询场景（包含单字段查询优化）
            $table->index(['dept_id', 'status'], 'idx_user_dept_status');
            $table->index(['post_id', 'status'], 'idx_user_post_status');
            $table->index(['role_id', 'status'], 'idx_user_role_status');
            $table->timestamps();
            $table->softDeletes();
            $table->engine = "InnoDB";
            $table->charset = "utf8mb4";
            $table->collation = "utf8mb4_unicode_ci";
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
}
