<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysApiTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_api', function (Blueprint $table) {
            $table->bigIncrements('api_id')->comment('接口ID');
            $table->string('api_name', 50)->comment('接口名称');
            $table->string('api_path', 200)->comment('接口路径');
            $table->string('api_method', 10)->comment('请求方法');
            $table->string('api_group', 50)->nullable()->comment('接口分组');
            $table->string('description', 500)->nullable()->comment('接口描述');
            $table->tinyInteger('status')->default(1)->comment('接口状态 1:启用 0:禁用');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('api_path');
            $table->index('api_group');
            $table->index('status', 'idx_sys_api_status');
            $table->index('api_method', 'idx_sys_api_method');
            $table->index(['api_group', 'status'], 'idx_sys_api_group_status');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_api');
    }
} 