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
 * 角色菜单关联模型
 */
class SysRoleMenu extends Model
{
    protected $table = 'sys_role_menu';
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'menu_id',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'menu_id' => 'integer',
    ];

    /**
     * 关联角色
     */
    public function role()
    {
        return $this->belongsTo(SysRole::class, 'role_id', 'role_id');
    }

    /**
     * 关联菜单
     */
    public function menu()
    {
        return $this->belongsTo(SysMenu::class, 'menu_id', 'menu_id');
    }
}
