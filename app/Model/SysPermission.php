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
 * 权限模型
 */
class SysPermission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_permission';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id',
        'resource_type',
        'resource_key',
    ];

    /**
     * 资源类型常量
     */
    const RESOURCE_TYPE_API = 'api';
    const RESOURCE_TYPE_MENU = 'menu';
    
    /**
     * 角色关联
     */
    public function role()
    {
        return $this->belongsTo(SysRole::class, 'role_id', 'id');
    }
} 