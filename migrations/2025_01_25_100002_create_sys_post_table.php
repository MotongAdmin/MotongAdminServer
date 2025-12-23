<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSysPostTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sys_post', function (Blueprint $table) {
            $table->bigIncrements('post_id')->comment('职位ID');
            $table->string('post_name', 128)->comment('职位名称');
            $table->string('post_code', 128)->comment('职位编码');
            $table->tinyInteger('sort')->default(0)->comment('显示顺序');
            $table->tinyInteger('status')->default(1)->comment('状态（1正常 0停用）');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('post_code', 'uk_sys_post_code');
            $table->index('status');
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
        Schema::dropIfExists('sys_post');
    }
}
