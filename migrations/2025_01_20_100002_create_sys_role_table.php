<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysRoleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_role', function (Blueprint $table) {
            $table->bigIncrements('role_id')->comment('角色ID');
            $table->string('role_name', 50)->comment('角色名称');
            $table->string('role_key', 50)->comment('角色权限字符串');
            $table->unsignedInteger('role_sort')->default(0)->comment('显示顺序');
            $table->tinyInteger('data_scope')->default(1)->comment('数据范围 1:全部 2:本部门 3:本部门及子部门 4:自定义');
            $table->tinyInteger('status')->default(1)->comment('角色状态 1:正常 0:停用');
            $table->tinyInteger('del_flag')->default(0)->comment('删除标志 0:存在 1:删除');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('role_key');
            $table->index('status');
            $table->index('data_scope', 'idx_sys_role_data_scope');
            $table->index('del_flag', 'idx_sys_role_del_flag');
            $table->index('role_sort', 'idx_sys_role_sort');
            // 复合索引用于角色列表查询
            $table->index(['status', 'del_flag', 'role_sort'], 'idx_sys_role_status_del_sort');
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
        Schema::dropIfExists('sys_role');
    }
} 