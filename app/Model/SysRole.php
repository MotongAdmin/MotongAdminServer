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
 * 系统角色模型
 */
class SysRole extends Model
{
    use SoftDeletes;

    protected $table = 'sys_role';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_name',
        'role_key', 
        'role_sort',
        'data_scope',
        'status',
        'del_flag',
        'remark',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'role_sort' => 'integer',
        'data_scope' => 'integer',
        'status' => 'integer',
        'del_flag' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 关联菜单
     */
    public function menus()
    {
        return $this->belongsToMany(SysMenu::class, 'sys_role_menu', 'role_id', 'menu_id');
    }

    /**
     * 关联用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }

    /**
     * 关联部门（数据范围为自定义时使用）
     */
    public function depts()
    {
        return $this->belongsToMany(SysDept::class, 'sys_role_dept', 'role_id', 'dept_id');
    }

    /**
     * 状态范围查询
     */
    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    /**
     * 正常状态的角色
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 1)->where('del_flag', 0);
    }
}