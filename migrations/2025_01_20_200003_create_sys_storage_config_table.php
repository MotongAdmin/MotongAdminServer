<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysStorageConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_storage_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->comment('配置名称');
            $table->text('description')->nullable()->comment('配置描述');
            $table->string('provider', 20)->comment('服务商类型(关联字典:sys_cloud_platform)');
            $table->string('access_key', 100)->comment('AccessKey');
            $table->string('secret_key', 100)->comment('SecretKey(加密存储)');
            $table->string('bucket', 50)->comment('Bucket名称');
            $table->string('region', 50)->comment('存储区域');
            $table->string('domain', 255)->comment('访问域名');
            $table->string('access_type', 20)->default('public')->comment('访问控制(public/private)');
            $table->string('main_directory', 255)->nullable()->comment('主目录，可以不设置，如果设置了，就为该bucket下的对应目录作为所有存储的根目录');
            $table->json('extra_config')->nullable()->comment('额外配置(JSON格式)');

            $table->index('provider');
            $table->index('name', 'idx_sys_storage_config_name');
            $table->index('access_type', 'idx_sys_storage_config_access_type');
            $table->index('region', 'idx_sys_storage_config_region');
            // 复合索引用于按服务商和访问类型查询
            $table->index(['provider', 'access_type'], 'idx_sys_storage_config_provider_access');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_storage_config');
    }
} 