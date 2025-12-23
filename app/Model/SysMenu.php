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

use Hyperf\Database\Model\SoftDeletes;

/**
 * 系统菜单模型
 */
class SysMenu extends Model
{
    use SoftDeletes;

    protected $table = 'sys_menu';
    protected $primaryKey = 'menu_id';

    protected $fillable = [
        'menu_name',
        'parent_id',
        'order_num',
        'path',
        'component',
        'query',
        'is_frame',
        'is_cache',
        'menu_type',
        'visible',
        'status',
        'perms',
        'icon',
        'remark',
    ];

    protected $casts = [
        'menu_id' => 'integer',
        'parent_id' => 'integer',
        'order_num' => 'integer',
        'is_frame' => 'integer',
        'is_cache' => 'integer',
        'visible' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 关联角色
     */
    public function roles()
    {
        return $this->belongsToMany(SysRole::class, 'sys_role_menu', 'menu_id', 'role_id');
    }

    /**
     * 关联接口
     */
    public function apis()
    {
        return $this->belongsToMany(SysApi::class, 'sys_menu_api', 'menu_id', 'api_id');
    }

    /**
     * 父菜单
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'menu_id');
    }

    /**
     * 子菜单
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'menu_id');
    }

    /**
     * 状态范围查询
     */
    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    /**
     * 可见性范围查询
     */
    public function scopeVisible($query, $visible = 1)
    {
        return $query->where('visible', $visible);
    }

    /**
     * 菜单类型范围查询
     */
    public function scopeMenuType($query, $type)
    {
        return $query->where('menu_type', $type);
    }

    /**
     * 正常状态的菜单
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 1)->where('visible', 1);
    }
}
