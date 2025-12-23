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

namespace App\Constants;

/**
 * 权限管理专用常量类
 */
class PermissionConstants
{
    
    // ZGW接口白名单
    const ZGW_WHITE_LIST_INTERFACES = [
        'system.user.login',
        'system.user.logout',
        'system.auth.getUserInfo',
        'system.auth.getUserMenus',
        'system.auth.getUserPermissions',
        'system.auth.checkPermission',
        'system.config.getConfigByKey',
        'system.config.getConfigByKeys',
    ];
    
    // 默认角色名称
    const DEFAULT_ROLES = [
        1 => '超级管理员',
        2 => '系统管理员',
        3 => '普通用户'
    ];
    
    // 默认角色键值
    const DEFAULT_ROLE_KEYS = [
        1 => 'super_admin',
        2 => 'sys_admin',
        3 => 'user'
    ];
    
    // 默认菜单图标
    const DEFAULT_MENU_ICONS = [
        'system' => 'system',
        'user' => 'user',
        'role' => 'role',
        'menu' => 'menu',
        'api' => 'api'
    ];
    
    // HTTP请求方法
    const HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'HEAD',
        'OPTIONS'
    ];
    
    // API分组名称
    const API_GROUPS = [
        'user' => '用户管理',
        'role' => '角色管理',
        'menu' => '菜单管理',
        'api' => 'API管理',
        'auth' => '认证授权',
        'system' => '系统管理'
    ];
    
    // 权限规则模板
    const PERMISSION_PATTERNS = [
        'list' => '%s:list',          // 列表权限
        'view' => '%s:view',          // 查看权限  
        'create' => '%s:create',      // 创建权限
        'update' => '%s:update',      // 更新权限
        'delete' => '%s:delete',      // 删除权限
        'export' => '%s:export',      // 导出权限
        'import' => '%s:import'       // 导入权限
    ];
    
    // 系统预设菜单权限
    const SYSTEM_MENU_PERMISSIONS = [
        'system:main',
        'system:user:list',
        'system:role:list', 
        'system:menu:list',
        'system:api:list'
    ];
    
    // 缓存键前缀
    const CACHE_KEYS = [
        'user_permissions' => 'permission:user:%d',
        'user_menus' => 'permission:menus:%d',
        'role_permissions' => 'permission:role:%d',
        'menu_tree' => 'permission:menu_tree',
        'api_list' => 'permission:api_list'
    ];
    
    // 缓存过期时间(秒)
    const CACHE_TTL = [
        'user_permissions' => 1800,    // 30分钟
        'user_menus' => 1800,          // 30分钟
        'role_permissions' => 3600,    // 1小时
        'menu_tree' => 7200,           // 2小时
        'api_list' => 7200             // 2小时
    ];
}
