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
 * 菜单接口关联模型
 */
class SysMenuApi extends Model
{
    protected $table = 'sys_menu_api';
    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'api_id',
    ];

    protected $casts = [
        'menu_id' => 'integer',
        'api_id' => 'integer',
    ];

    /**
     * 关联菜单
     */
    public function menu()
    {
        return $this->belongsTo(SysMenu::class, 'menu_id', 'menu_id');
    }

    /**
     * 关联接口
     */
    public function api()
    {
        return $this->belongsTo(SysApi::class, 'api_id', 'api_id');
    }
}
