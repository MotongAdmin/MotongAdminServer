<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysEndpointConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_endpoint_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_key', 50)->unique()->comment('业务标识（唯一）');
            $table->string('name', 100)->comment('配置名称');
            $table->text('description')->nullable()->comment('配置描述');
            $table->string('endpoint_url', 255)->comment('服务地址');
            $table->string('request_method', 10)->comment('请求方式(关联字典:sys_http_method)');
            $table->integer('timeout')->default(30)->comment('超时设置(秒)');
            $table->json('headers')->nullable()->comment('请求头配置(JSON格式)');
            $table->json('auth_config')->nullable()->comment('鉴权信息(JSON格式)');
            $table->json('extra_config')->nullable()->comment('额外配置(JSON格式)');
            $table->timestamps();
            
            // 添加索引
            $table->index('name', 'idx_sys_endpoint_config_name');
            $table->index('request_method', 'idx_sys_endpoint_config_method');
            $table->index('timeout', 'idx_sys_endpoint_config_timeout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_endpoint_config');
    }
} 