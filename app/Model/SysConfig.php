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
 * 系统配置模型
 */
class SysConfig extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'sys_config';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'config_key',
        'config_value',
        'config_name',
        'remark',
        'is_system',
        'config_type',
        'dict_type'
    ];

    /**
     * 隐藏的属性
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getConfigValueByKey(string $key): string
    {
        return $this->where('config_key', $key)->value('config_value');
    }
} 