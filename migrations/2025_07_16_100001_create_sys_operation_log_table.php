<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysOperationLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_operation_log', function (Blueprint $table) {
            $table->bigIncrements('log_id')->comment("日志ID");
            $table->string('module', 50)->comment("操作模块");
            $table->string('operation', 50)->comment("操作类型");
            $table->string('method', 10)->comment("请求方式");
            $table->string('request_url', 255)->comment("请求URL");
            $table->text('request_param')->nullable()->comment("请求参数");
            $table->text('response_data')->nullable()->comment("响应数据");
            $table->string('ip', 64)->comment("操作IP");
            $table->string('user_agent', 255)->nullable()->comment("用户代理");
            $table->bigInteger('user_id')->comment("操作用户ID");
            $table->string('username', 50)->comment("操作用户名");
            $table->tinyInteger('level')->default(1)->comment("日志等级：1=普通，2=重要，3=关键");
            $table->tinyInteger('status')->default(1)->comment("操作状态：1=成功，0=失败");
            $table->string('error_message', 500)->nullable()->comment("错误消息");
            $table->integer('execution_time')->default(0)->comment("执行时间(毫秒)");
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('module');
            $table->index('operation');
            $table->index('level');
            $table->index('status');
            $table->index('created_at');
            $table->index('ip', 'idx_sys_operation_log_ip');
            $table->index('execution_time', 'idx_sys_operation_log_execution_time');
            // 复合索引用于日志分析
            $table->index(['user_id', 'module', 'created_at'], 'idx_sys_operation_log_user_module_time');
            $table->index(['status', 'level', 'execution_time'], 'idx_sys_operation_log_status_level_time');
            // 用户名索引用于模糊查询
            $table->index('username', 'idx_sys_operation_log_username');
            
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
        Schema::dropIfExists('sys_operation_log');
    }
} 