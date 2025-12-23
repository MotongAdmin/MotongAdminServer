<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysRoleDeptTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_role_dept', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('role_id')->comment('角色ID');
            $table->bigInteger('dept_id')->comment('部门ID');

            $table->unique(['role_id', 'dept_id']);
            // 只需要dept_id索引，role_id已被唯一索引覆盖
            $table->index('dept_id', 'idx_sys_role_dept_dept_id');
            $table->timestamps();
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
        Schema::dropIfExists('sys_role_dept');
    }
}
