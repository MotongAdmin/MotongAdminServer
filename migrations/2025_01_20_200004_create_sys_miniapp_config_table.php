<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysMiniappConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_miniapp_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->comment('配置名称');
            $table->text('description')->nullable()->comment('配置描述');
            $table->string('platform', 20)->comment('平台类型(关联字典:sys_open_platform)');
            $table->string('app_id', 100)->comment('AppID');
            $table->string('app_secret', 100)->comment('AppSecret(加密存储)');
            $table->string('auth_redirect', 255)->nullable()->comment('授权回调地址');
            $table->string('message_token', 100)->nullable()->comment('消息校验Token(如适用)');
            $table->string('message_aeskey', 100)->nullable()->comment('消息加解密密钥(如适用)');
            $table->json('extra_config')->nullable()->comment('额外配置(JSON格式)');
            $table->timestamps();
            
            // 添加索引
            $table->index('name', 'idx_sys_miniapp_config_name');
            $table->index('platform', 'idx_sys_miniapp_config_platform');
            $table->index('app_id', 'idx_sys_miniapp_config_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_miniapp_config');
    }
} 