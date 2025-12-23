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
use App\Model\SysRole;
use App\Model\SysRoleMenu;
use App\Model\SysMenu;
use App\Model\User;
use App\Constants\Constants;
use ZYProSoft\Facade\Auth;
use ZYProSoft\Log\Log;

/**
 * 角色权限验证服务类
 * 用于验证角色分配和菜单权限分配的权限控制
 */
class RolePermissionService extends BaseService
{
    /**
     * 验证是否可以分配指定角色给用户
     * 
     * @param int $targetRoleId 目标角色ID
     * @return bool
     */
    public function canAssignRole(int $targetRoleId): bool
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser instanceof User) {
                Log::error("RolePermissionService: Current user not found");
                return false;
            }
            
            // 验证目标角色是否存在且有效
            $targetRole = SysRole::find($targetRoleId);
            if (!$targetRole instanceof SysRole || $targetRole->status != Constants::ROLE_STATUS_NORMAL) {
                Log::error("RolePermissionService: Target role not found or disabled" . json_encode(['role_id' => $targetRoleId]));
                return false;
            }
            
            // 超级管理员可以分配任何角色
            if ($currentUser->role_id == Constants::USER_ROLE_SUPER_ADMIN) {
                Log::info("RolePermissionService: Super admin can assign any role");
                return true;
            }
            
            // 普通管理员只能分配比自己role_id大或等的角色
            $canAssign = $targetRoleId >= $currentUser->role_id;
            
            Log::info("RolePermissionService: Role assignment check" . json_encode([
                'current_user_id' => $currentUser->user_id,
                'current_role_id' => $currentUser->role_id,
                'target_role_id' => $targetRoleId,
                'can_assign' => $canAssign
            ]));
            
            return $canAssign;
            
        } catch (\Throwable $e) {
            Log::error("RolePermissionService: Error checking role assignment permission" . json_encode([
                'target_role_id' => $targetRoleId,
                'error' => $e->getMessage()
            ]));
            return false;
        }
    }
    
    /**
     * 验证菜单权限是否在当前用户权限范围内
     * 
     * @param array $menuIds 要分配的菜单ID数组
     * @return bool
     */
    public function canAssignMenus(array $menuIds): bool
    {
        try {
            if (empty($menuIds)) {
                return true;
            }
            
            $currentUser = Auth::user();
            if (!$currentUser instanceof User) {
                Log::error("RolePermissionService: Current user not found");
                return false;
            }
            
            // 验证菜单ID是否都存在且有效
            $validMenuIds = SysMenu::whereIn('menu_id', $menuIds)
                ->where('status', Constants::MENU_STATUS_NORMAL)
                ->pluck('menu_id')
                ->toArray();
                
            if (count($validMenuIds) != count($menuIds)) {
                Log::error("RolePermissionService: Some menu IDs are invalid" . json_encode([
                    'requested_menus' => $menuIds,
                    'valid_menus' => $validMenuIds
                ]));
                return false;
            }
            
            // 超级管理员可以分配任何菜单
            if ($currentUser->role_id == Constants::USER_ROLE_SUPER_ADMIN) {
                Log::info("RolePermissionService: Super admin can assign any menus");
                return true;
            }
            
            // 获取当前用户拥有的菜单权限
            $userMenuIds = SysRoleMenu::where('role_id', $currentUser->role_id)
                ->pluck('menu_id')
                ->toArray();
            
            // 检查要分配的菜单是否都在用户权限范围内
            $invalidMenuIds = array_diff($menuIds, $userMenuIds);
            $canAssign = empty($invalidMenuIds);
            
            Log::info("RolePermissionService: Menu assignment check" . json_encode([
                'current_user_id' => $currentUser->user_id,
                'current_role_id' => $currentUser->role_id,
                'user_menu_ids' => $userMenuIds,
                'requested_menu_ids' => $menuIds,
                'invalid_menu_ids' => $invalidMenuIds,
                'can_assign' => $canAssign
            ]));
            
            return $canAssign;
            
        } catch (\Throwable $e) {
            Log::error("RolePermissionService: Error checking menu assignment permission" . json_encode([
                'menu_ids' => $menuIds,
                'error' => $e->getMessage()
            ]));
            return false;
        }
    }
    
    /**
     * 获取当前用户可分配的角色列表
     * 
     * @return array
     */
    public function getAssignableRoles(): array
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser instanceof User) {
                Log::error("RolePermissionService: Current user not found");
                return [];
            }
            
            $query = SysRole::normal();
            
            // 超级管理员可以分配所有角色
            if ($currentUser->role_id != Constants::USER_ROLE_SUPER_ADMIN) {
                // 普通管理员只能分配比自己role_id大或等的角色
                $query->where('role_id', '>=', $currentUser->role_id);
            }
            
            $roles = $query->orderBy('role_sort')
                ->orderBy('role_id')
                ->get(['role_id', 'role_name', 'role_key', 'role_sort'])
                ->toArray();
                
            Log::info("RolePermissionService: Get assignable roles" . json_encode([
                'current_user_id' => $currentUser->user_id,
                'current_role_id' => $currentUser->role_id,
                'assignable_roles_count' => count($roles)
            ]));
            
            return $roles;
            
        } catch (\Throwable $e) {
            Log::error("RolePermissionService: Error getting assignable roles" . json_encode([
                'error' => $e->getMessage()
            ]));
            return [];
        }
    }
    
    /**
     * 获取当前用户可分配的菜单ID列表
     * 
     * @return array
     */
    public function getAssignableMenuIds(): array
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser instanceof User) {
                Log::error("RolePermissionService: Current user not found");
                return [];
            }
            
            // 超级管理员拥有所有菜单权限
            if ($currentUser->role_id == Constants::USER_ROLE_SUPER_ADMIN) {
                $menuIds = SysMenu::where('status', Constants::MENU_STATUS_NORMAL)
                    ->pluck('menu_id')
                    ->toArray();
            } else {
                // 普通管理员只能分配自己拥有的菜单权限
                $menuIds = SysRoleMenu::where('role_id', $currentUser->role_id)
                    ->pluck('menu_id')
                    ->toArray();
            }
            
            Log::info("RolePermissionService: Get assignable menu IDs" . json_encode([
                'current_user_id' => $currentUser->user_id,
                'current_role_id' => $currentUser->role_id,
                'assignable_menu_count' => count($menuIds)
            ]));
            
            return $menuIds;
            
        } catch (\Throwable $e) {
            Log::error("RolePermissionService: Error getting assignable menu IDs" . json_encode([
                'error' => $e->getMessage()
            ]));
            return [];
        }
    }
}
