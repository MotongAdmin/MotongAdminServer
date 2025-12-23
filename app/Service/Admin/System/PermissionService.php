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

namespace App\Service\Admin\System;
use App\Service\Admin\BaseService;

use App\Model\SysPermission;
use App\Model\SysRole;
use App\Model\SysRoleMenu;
use App\Model\SysMenu;
use App\Model\SysMenuApi;
use App\Model\SysApi;
use App\Model\User;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Log\Log;
use App\Constants\Constants;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

class PermissionService extends BaseService
{
    /**
     * 权限缓存前缀
     */
    const PERMISSION_CACHE_PREFIX = 'permission:';
    
    /**
     * 权限缓存过期时间（秒）
     */
    const PERMISSION_CACHE_TTL = 3600;
    
    /**
     * 刷新权限缓存
     */
    public function refreshPermissionCache(): void
    {
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        
        // 清除所有权限缓存
        $keys = $redis->keys(self::PERMISSION_CACHE_PREFIX . '*');
        if (!empty($keys)) {
            $redis->del(...$keys);
        }
        
        // 重建缓存
        $permissions = SysPermission::query()->get();
        $permissionMap = [];
        
        foreach ($permissions as $permission) {
            $key = $permission['role_id'] . ':' . $permission['resource_type'];
            if (!isset($permissionMap[$key])) {
                $permissionMap[$key] = [];
            }
            $permissionMap[$key][] = $permission['resource_key'];
        }
        
        foreach ($permissionMap as $key => $resources) {
            $cacheKey = self::PERMISSION_CACHE_PREFIX . $key;
            $redis->set($cacheKey, json_encode($resources), self::PERMISSION_CACHE_TTL);
        }
    }
    
    /**
     * 检查用户权限
     */
    public function checkPermission(int $userId, string $resource): bool
    {
        Log::info("checkPermission".json_encode([
            "userId" => $userId,
            "resource" => $resource
        ]));
        
        // 获取用户信息
        $user = User::find($userId);
        if (!$user) {
            Log::error("checkPermission: User not found".json_encode(["userId" => $userId]));
            return false;
        }
        
        // 超级管理员拥有所有权限
        if ($user->role_id == Constants::USER_ROLE_SUPER_ADMIN) {
            Log::info("checkPermission: Super admin always has permission");
            return true;
        }
        
        // 这是用来检查请求的，肯定是API类型
        $resourceType = SysPermission::RESOURCE_TYPE_API;
        
        // 检查用户是否有权限访问该资源
        $hasPermission = $this->checkRolePermission($user->role_id, $resourceType, $resource);
        Log::info("checkPermission: Result".json_encode([
            "userId" => $userId, 
            "resource" => $resource, 
            "resourceType" => $resourceType,
            "result" => $hasPermission
        ]));
        
        return $hasPermission;
    }
    
    /**
     * 检查角色权限
     */
    public function checkRolePermission(int $roleId, string $resourceType, string $resourceKey): bool
    {
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $cacheKey = self::PERMISSION_CACHE_PREFIX . $roleId . ':' . $resourceType;
        
        $resources = $redis->get($cacheKey);
        if ($resources === false) {
            // 缓存未命中，从数据库获取
            $resources = SysPermission::query()
                ->where('role_id', $roleId)
                ->where('resource_type', $resourceType)
                ->pluck('resource_key')
                ->toArray();
            
            $redis->set($cacheKey, json_encode($resources), self::PERMISSION_CACHE_TTL);
        } else {
            $resources = json_decode($resources, true);
        }
        
        return in_array($resourceKey, $resources);
    }
    
    /**
     * 获取用户权限列表
     */
    public function getUserPermissions(int $userId): array
    {
        // 获取用户信息
        $user = User::find($userId);
        if (!$user || !$user->role_id) {
            return [];
        }
        
        $roleId = $user->role_id;
        
        // 超级管理员拥有所有权限, 这里是给Web前端返回，就不需要接口类型的权限资源了
        if ($roleId == Constants::USER_ROLE_SUPER_ADMIN) {
            // 获取所有菜单权限
            $permissions = SysPermission::query()
                ->where('resource_type', SysPermission::RESOURCE_TYPE_MENU)
                ->pluck('resource_key')
                ->toArray();
            
            return array_unique($permissions);
        }
        
        // 获取角色的菜单权限
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        $cacheKey = self::PERMISSION_CACHE_PREFIX . $roleId . ':' . SysPermission::RESOURCE_TYPE_MENU;
        
        $permissions = $redis->get($cacheKey);
        if ($permissions === false) {
            // 缓存未命中，从数据库获取
            $permissions = SysPermission::query()
                ->where('role_id', $roleId)
                ->where('resource_type', SysPermission::RESOURCE_TYPE_MENU)
                ->pluck('resource_key')
                ->toArray();
            
            $redis->set($cacheKey, json_encode($permissions), self::PERMISSION_CACHE_TTL);
            return $permissions;
        }
        
        return json_decode($permissions, true);
    }
    
    /**
     * 为角色分配菜单权限
     */
    public function assignMenuToRole(int $roleId, array $menuIds): bool
    {
        Db::beginTransaction();
        try {
            // 删除角色现有菜单权限
            SysRoleMenu::where('role_id', $roleId)->delete();
            
            // 删除角色现有的所有权限
            SysPermission::query()
                ->where('role_id', $roleId)
                ->delete();
            
            // 收集所有需要插入的权限，用于去重
            $permissionsToInsert = [];
            
            // 分配新菜单权限
            $data = [];
            foreach ($menuIds as $menuId) {
                $data[] = [
                    'role_id' => $roleId,
                    'menu_id' => $menuId
                ];
                
                // 获取菜单权限标识
                $menu = SysMenu::find($menuId);
                if ($menu instanceof SysMenu && $menu->perms) {
                    // 收集菜单权限
                    $permissionKey = $roleId . '|' . SysPermission::RESOURCE_TYPE_MENU . '|' . $menu->perms;
                    if (!isset($permissionsToInsert[$permissionKey])) {
                        $permissionsToInsert[$permissionKey] = [
                            'role_id' => $roleId,
                            'resource_type' => SysPermission::RESOURCE_TYPE_MENU,
                            'resource_key' => $menu->perms
                        ];
                    }
                }
                
                // 获取菜单关联的所有接口
                $apiIds = SysMenuApi::where('menu_id', $menuId)->pluck('api_id')->toArray();
                if (!empty($apiIds)) {
                    $apis = SysApi::whereIn('api_id', $apiIds)
                        ->where('status', 1)
                        ->get();
                    
                    foreach ($apis as $api) {
                        // 收集API权限
                        $permissionKey = $roleId . '|' . SysPermission::RESOURCE_TYPE_API . '|' . $api->api_name;
                        if (!isset($permissionsToInsert[$permissionKey])) {
                            $permissionsToInsert[$permissionKey] = [
                                'role_id' => $roleId,
                                'resource_type' => SysPermission::RESOURCE_TYPE_API,
                                'resource_key' => $api->api_name
                            ];
                        }
                    }
                }
            }
            
            // 批量插入去重后的权限数据
            if (!empty($permissionsToInsert)) {
                SysPermission::insert(array_values($permissionsToInsert));
            }
            
            // 插入角色菜单关联数据
            if (!empty($data)) {
                SysRoleMenu::insert($data);
            }
            
            Db::commit();
            
            // 刷新权限缓存
            $this->refreshPermissionCache();
            
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
    
    /**
     * 获取用户菜单权限
     */
    public function getUserMenus(int $userId): array
    {
        // 1. 获取用户角色
        $user = User::find($userId);
        if (!$user || !$user->role_id) {
            return [];
        }
        
        $roleId = $user->role_id;
        
        // 2. 获取角色对应的菜单ID
        $menuIds = SysRoleMenu::where('role_id', $roleId)
            ->pluck('menu_id')
            ->toArray();
            
        if (empty($menuIds) && $roleId != Constants::USER_ROLE_SUPER_ADMIN) {
            return [];
        }
        
        // 3. 获取菜单列表
        $menus = SysMenu::whereIn('menu_id', $menuIds)
            ->where('status', 1)
            ->orderBy('parent_id')
            ->orderBy('order_num')
            ->get()
            ->toArray();

        //超级管理员拥有所有菜单权限
        if ($roleId == Constants::USER_ROLE_SUPER_ADMIN) {
            $menus = SysMenu::where('status', 1)
                ->orderBy('parent_id')
                ->orderBy('order_num')
                ->get()
                ->toArray();
        }
            
        // 4. 构建菜单树
        return $this->buildMenuTree($menus);
    }
    
    /**
     * 构建菜单树
     */
    private function buildMenuTree(array $menus, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($menus, $menu['menu_id']);
                if (!empty($children)) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }
        
        return $tree;
    }
    
    /**
     * 为用户分配角色
     */
    public function assignRoleToUser(int $userId, int $roleId): bool
    {
        // 更新用户表中的角色ID
        $user = User::find($userId);
        if (!$user) {
            throw new HyperfCommonException(ErrorCode::USER_NOT_FOUND);
        }
        
        $user->role_id = $roleId;
        $user->save();
        
        return true;
    }
} 