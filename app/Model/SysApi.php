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
 * 系统接口模型
 */
class SysApi extends Model
{
    protected $table = 'sys_api';
    protected $primaryKey = 'api_id';

    protected $fillable = [
        'api_name',
        'api_path',
        'api_method',
        'api_group',
        'description',
        'status',
    ];

    protected $casts = [
        'api_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 关联菜单
     */
    public function menus()
    {
        return $this->belongsToMany(SysMenu::class, 'sys_menu_api', 'api_id', 'menu_id');
    }

    /**
     * 状态范围查询
     */
    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    /**
     * 接口分组范围查询
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('api_group', $group);
    }

    /**
     * 正常状态的接口
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 1);
    }
}
