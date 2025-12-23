<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysPaymentConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_payment_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->comment('配置名称');
            $table->text('description')->nullable()->comment('配置描述');
            $table->string('platform', 20)->comment('支付平台(关联字典:sys_pay_channel)');
            $table->string('mch_id', 50)->comment('商户ID');
            $table->string('pay_key', 100)->comment('支付密钥(加密存储)');
            $table->string('cert_path', 255)->nullable()->comment('证书文件路径(如适用)');
            $table->string('pay_notify_url', 255)->nullable()->comment('支付回调地址');
            $table->string('refund_notify_url', 255)->nullable()->comment('退款回调地址');
            $table->json('extra_config')->nullable()->comment('额外配置(JSON格式)');
            $table->timestamps();
            
            // 添加索引
            $table->index('name', 'idx_sys_payment_config_name');
            $table->index('platform', 'idx_sys_payment_config_platform');
            $table->index('mch_id', 'idx_sys_payment_config_mch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_payment_config');
    }
} 