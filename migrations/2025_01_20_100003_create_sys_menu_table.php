<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysMenuTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_menu', function (Blueprint $table) {
            $table->bigIncrements('menu_id')->comment('菜单ID');
            $table->string('menu_name', 50)->comment('菜单名称');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父菜单ID');
            $table->unsignedInteger('order_num')->default(0)->comment('显示顺序');
            $table->string('path', 200)->nullable()->comment('路由地址');
            $table->string('component', 255)->nullable()->comment('组件路径');
            $table->string('query', 255)->nullable()->comment('路由参数');
            $table->tinyInteger('is_frame')->default(1)->comment('是否为外链 0:是 1:否');
            $table->tinyInteger('is_cache')->default(0)->comment('是否缓存 0:缓存 1:不缓存');
            $table->char('menu_type', 1)->comment('菜单类型 M:目录 C:菜单 F:按钮');
            $table->tinyInteger('visible')->default(1)->comment('菜单状态 1:显示 0:隐藏');
            $table->tinyInteger('status')->default(1)->comment('菜单状态 1:正常 0:停用');
            $table->string('perms', 100)->nullable()->comment('权限标识');
            $table->string('icon', 100)->nullable()->comment('菜单图标');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('parent_id');
            $table->index('menu_type');
            $table->index('status');
            $table->index('visible', 'idx_sys_menu_visible');
            // 复合索引用于树形结构查询
            $table->index(['parent_id', 'status', 'order_num'], 'idx_sys_menu_parent_status_order');
            // 复合索引用于权限查询
            $table->index(['menu_type', 'status', 'visible'], 'idx_sys_menu_type_status_visible');
            // 权限标识索引用于权限验证
            $table->index('perms', 'idx_sys_menu_perms');
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
        Schema::dropIfExists('sys_menu');
    }
} 