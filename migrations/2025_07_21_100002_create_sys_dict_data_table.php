<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysDictDataTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_dict_data', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('字典编码');
            $table->integer('dict_sort')->default(0)->comment('字典排序');
            $table->string('dict_label', 100)->comment('字典标签');
            $table->string('dict_value', 100)->comment('字典键值');
            $table->string('dict_type', 100)->comment('字典类型');
            $table->string('css_class', 100)->nullable()->comment('样式属性');
            $table->string('list_class', 100)->nullable()->comment('表格回显样式');
            $table->tinyInteger('status')->default(1)->comment('状态（1正常 0停用）');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamps();
            
            // 添加索引
            $table->index('dict_type', 'idx_sys_dict_data_dict_type');
            $table->index('status', 'idx_sys_dict_data_status');
            $table->index('dict_sort', 'idx_sys_dict_data_sort');
            // 复合索引用于常用查询场景
            $table->index(['dict_type', 'status'], 'idx_sys_dict_data_type_status');
            $table->index(['dict_type', 'status', 'dict_sort'], 'idx_sys_dict_data_type_status_sort');
            
            // 添加外键约束
            $table->foreign('dict_type')
                  ->references('dict_type')
                  ->on('sys_dict_type')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_dict_data');
    }
} 