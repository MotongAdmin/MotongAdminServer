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
 * 角色部门关联模型
 */
class SysRoleDept extends Model
{
    protected $table = 'sys_role_dept';
    
    protected $fillable = [
        'role_id',
        'dept_id',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'dept_id' => 'integer',
    ];

    /**
     * 关联角色
     */
    public function role()
    {
        return $this->belongsTo(SysRole::class, 'role_id', 'role_id');
    }

    /**
     * 关联部门
     */
    public function dept()
    {
        return $this->belongsTo(SysDept::class, 'dept_id', 'dept_id');
    }
}
