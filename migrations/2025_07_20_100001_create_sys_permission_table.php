<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysPermissionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_permission', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('role_id')->comment('角色ID');
            $table->string('resource_type', 20)->comment('资源类型：api-接口资源, menu-菜单权限标识');
            $table->string('resource_key', 100)->comment('资源标识：接口路径或菜单权限标识');
            $table->timestamps();
            
            $table->unique(['role_id', 'resource_type', 'resource_key'], 'idx_role_resource');
            // 只需要resource_type索引，role_id已被唯一索引覆盖
            $table->index('resource_type', 'idx_resource_type');
            
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
        Schema::dropIfExists('sys_permission');
    }
} 