<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  motong0306@hotmail.com
 * @author   zyvincent 
 * @Company  Motong Admin @ 2025
 * @license  GPL
 */

declare(strict_types=1);

namespace App\Model;

/**
 * 字典类型模型
 */
class SysDictType extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'sys_dict_type';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'dict_name',
        'dict_type',
        'value_type',
        'status',
        'is_system',
        'remark'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'value_type' => 'integer',
        'status' => 'integer',
        'is_system' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 获取字典数据
     */
    public function dictData()
    {
        return $this->hasMany(SysDictData::class, 'dict_type', 'dict_type');
    }
} 