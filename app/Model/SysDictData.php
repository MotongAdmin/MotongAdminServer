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

use App\Validator\DictDataValidator;

/**
 * 字典数据模型
 */
class SysDictData extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'sys_dict_data';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'dict_sort',
        'dict_label',
        'dict_value',
        'dict_type',
        'css_class',
        'list_class',
        'status',
        'remark'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'dict_sort' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 获取字典类型
     */
    public function dictType()
    {
        return $this->belongsTo(SysDictType::class, 'dict_type', 'dict_type');
    }


} 