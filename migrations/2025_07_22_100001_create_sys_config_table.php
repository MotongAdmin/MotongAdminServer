<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('config_key', 100)->unique()->comment('配置键名');
            $table->text('config_value')->comment('配置值');
            $table->string('config_name', 100)->comment('配置名称');
            $table->string('remark', 500)->nullable()->comment('备注说明');
            $table->tinyInteger('is_system')->default(0)->comment('是否系统配置(0否 1是)');
            $table->string('config_type', 20)->default('text')->comment('配置类型(text文本 image图片 file文件)');
            $table->string('dict_type', 24)->nullable()->comment('关联字典类型');
            $table->timestamps();
            
            // 添加索引
            $table->index('is_system', 'idx_sys_config_is_system');
            $table->index('config_type', 'idx_sys_config_type');
            $table->index('dict_type', 'idx_sys_config_dict_type');
            $table->index(['is_system', 'config_type'], 'idx_sys_config_system_type');
            
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('系统配置表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_config');
    }
} 