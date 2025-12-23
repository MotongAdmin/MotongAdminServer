<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysDeptTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_dept', function (Blueprint $table) {
            $table->bigIncrements('dept_id')->comment('部门ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('上级部门ID');
            $table->string('dept_path', 255)->nullable()->comment('部门路径');
            $table->string('dept_name', 128)->comment('部门名称');
            $table->tinyInteger('sort')->default(0)->comment('显示顺序');
            $table->string('leader', 128)->nullable()->comment('负责人');
            $table->string('phone', 11)->nullable()->comment('联系电话');
            $table->string('email', 64)->nullable()->comment('邮箱');
            $table->tinyInteger('status')->default(1)->comment('部门状态（1正常 0停用）');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('parent_id', 'idx_sys_dept_parent_id');
            $table->index('status');
            // 部门路径索引用于数据权限查询
            $table->index('dept_path', 'idx_sys_dept_path');
            // 部门名称索引用于模糊查询
            $table->index('dept_name', 'idx_sys_dept_name');
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
        Schema::dropIfExists('sys_dept');
    }
}
