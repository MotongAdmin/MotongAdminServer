<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysSmsConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_sms_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->comment('配置名称');
            $table->text('description')->nullable()->comment('配置描述');
            $table->string('provider', 20)->comment('服务商类型(关联字典:sys_cloud_platform)');
            $table->string('access_key', 100)->comment('AccessKey');
            $table->string('secret_key', 100)->comment('SecretKey(加密存储)');
            $table->string('sign_name', 50)->comment('短信签名');
            $table->json('template_map')->nullable()->comment('模板ID映射表(JSON格式)');
            $table->string('callback_url', 255)->nullable()->comment('回调地址');
            $table->json('extra_config')->nullable()->comment('额外配置(JSON格式)');
            $table->timestamps();
            
            // 添加索引
            $table->index('name', 'idx_sys_sms_config_name');
            $table->index('provider', 'idx_sys_sms_config_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_sms_config');
    }
} 