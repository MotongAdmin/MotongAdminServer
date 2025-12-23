<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysFieldDictTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_field_dict', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键');
            $table->string('table_name', 100)->comment('数据表名');
            $table->string('field_name', 100)->comment('字段名');
            $table->string('dict_type', 100)->comment('字典类型');
            $table->string('description', 255)->nullable()->comment('描述');
            $table->timestamps();
            
            // 添加唯一索引
            $table->unique(['table_name', 'field_name'], 'uk_table_field');
            
            // 添加查询索引
            $table->index('dict_type', 'idx_sys_field_dict_dict_type');
            $table->index('table_name', 'idx_sys_field_dict_table_name');
            $table->index(['table_name', 'dict_type'], 'idx_sys_field_dict_table_type');
            
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
        Schema::dropIfExists('sys_field_dict');
    }
} 