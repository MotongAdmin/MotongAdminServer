<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysMenuApiTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_menu_api', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_id')->comment('菜单ID');
            $table->unsignedBigInteger('api_id')->comment('接口ID');
            
            $table->primary(['menu_id', 'api_id']);
            // 添加单字段索引用于反向查询
            $table->index('menu_id', 'idx_sys_menu_api_menu_id');
            $table->index('api_id', 'idx_sys_menu_api_api_id');
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
        Schema::dropIfExists('sys_menu_api');
    }
} 