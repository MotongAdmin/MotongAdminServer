<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysDictTypeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_dict_type', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('字典主键');
            $table->string('dict_name', 100)->comment('字典名称');
            $table->string('dict_type', 100)->unique()->comment('字典类型');
            $table->tinyInteger('value_type')->default(1)->comment('值类型 1:字符串 2:整型 3:浮点型 4:高精度浮点型 5:json字符串');
            $table->tinyInteger('status')->default(1)->comment('状态（1正常 0停用）');
            $table->tinyInteger('is_system')->default(0)->comment('是否系统内置（1是 0否）');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamps();
            
            // 添加索引
            $table->index('status', 'idx_sys_dict_type_status');
            $table->index('is_system', 'idx_sys_dict_type_is_system');
            $table->index(['status', 'is_system'], 'idx_sys_dict_type_status_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_dict_type');
    }
} 