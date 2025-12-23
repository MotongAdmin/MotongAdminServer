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
use Hyperf\Di\Annotation\Inject;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 角色管理服务类
 */
class RoleService extends BaseService
{
    /**
     * @Inject
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * 获取角色列表
     */
    public function getRoleList(int $page = 1, int $size = 20, string $keyword = ''): array
    {
        $query = SysRole::normal();
        
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('role_name', 'like', "%{$keyword}%")
                  ->orWhere('role_key', 'like', "%{$keyword}%");
            });
        }
        
        $total = $query->count();
        $list = $query->orderBy('role_sort')
            ->orderBy('role_id')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get()
            ->toArray();
            
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'pages' => ceil($total / $size)
        ];
    }    

    /**
     * 创建角色
     */
    public function createRole(array $data): int
    {
        // 检查角色标识唯一性（包括已软删除的记录）
        $existingRole = SysRole::withTrashed()->where('role_key', $data['role_key'])->first();
        if ($existingRole) {
            if ($existingRole->deleted_at === null) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色标识已存在');
            } else {
                // 如果角色标识存在于已软删除的记录中，恢复该记录
                $existingRole->restore();
                //记录操作日志
                $this->addOperationLog();
                return $existingRole->role_id;
            }
        }
        
        $role = SysRole::create($data);

        // 仅在数据范围为自定义时处理部门关联
        if (($data['data_scope'] ?? 1) == 4 && !empty($data['dept_ids'])) {
            $role->depts()->sync($data['dept_ids']);
        }

        //记录操作日志
        $this->addOperationLog();
        
        return $role->role_id;
    }

    /**
     * 更新角色
     */
    public function updateRole(int $roleId, array $data): bool
    {
        $role = SysRole::find($roleId);
        if (!$role instanceof SysRole) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        // 检查角色标识唯一性
        if (isset($data['role_key']) && 
            SysRole::where('role_key', $data['role_key'])
                ->where('role_id', '!=', $roleId)
                ->exists()) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色标识已存在');
        }

        // 检查是否修改了数据权限范围
        if(isset($data['data_scope']) && $data['data_scope'] != $role->data_scope) {
            //清理数据缓存
            $this->dataScopeService->clearRoleDataScopeCache($roleId);
        }
        
        $role->update($data);

        // 处理部门权限
        if (isset($data['dept_ids'])) {
            if (($data['data_scope'] ?? $role->data_scope) == 4) { // 自定义数据范围
                $role->depts()->sync($data['dept_ids']);
            } else {
                // 非自定义数据范围，清空部门关联
                $role->depts()->sync([]);
            }

            //清理数据缓存
            $this->dataScopeService->clearRoleDataScopeCache($roleId);
        }

        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 删除角色
     */
    public function deleteRole(int $roleId): bool
    {
        $role = SysRole::find($roleId);
        if (!$role) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        // 检查是否为超级管理员角色
        if ($roleId == 1) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '超级管理员角色不能删除');
        }
        
        // 检查是否有用户关联此角色
        if ($role->users()->exists()) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '该角色下还有用户，不能删除');
        }
        
        // 删除角色菜单关联
        SysRoleMenu::where('role_id', $roleId)->delete();
        
        // 软删除角色
        $role->delete();

        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 获取角色详情
     */
    public function getRoleDetail(int $roleId): array
    {
        $role = SysRole::find($roleId);
        if (!$role) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        return $role->toArray();
    }

    /**
     * 分配菜单权限给角色
     */
    public function assignMenusToRole(int $roleId, array $menuIds): bool
    {
        $role = SysRole::find($roleId);
        if (!$role) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        // 验证菜单ID是否存在
        $existMenuIds = SysMenu::whereIn('menu_id', $menuIds)->pluck('menu_id')->toArray();
        $invalidMenuIds = array_diff($menuIds, $existMenuIds);
        if (!empty($invalidMenuIds)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '存在无效的菜单ID: ' . implode(',', $invalidMenuIds));
        }
        
        // 调用权限服务分配菜单
        $this->permissionService->assignMenuToRole($roleId, $menuIds);
        
        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 获取角色的菜单权限
     */
    public function getRoleMenus(int $roleId): array
    {
        $role = SysRole::find($roleId);
        if (!$role) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        $menuIds = SysRoleMenu::where('role_id', $roleId)->pluck('menu_id')->toArray();
        
        $menus = SysMenu::whereIn('menu_id', $menuIds)
            ->orderBy('parent_id')
            ->orderBy('order_num')
            ->get()
            ->toArray();
            
        return [
            'menuIds' => $menuIds,
            'menus' => $menus
        ];
    }

    /**
     * 获取所有可用角色
     */
    public function getAllRoles(): array
    {
        return SysRole::normal()
            ->orderBy('role_sort')
            ->orderBy('role_id')
            ->get(['role_id', 'role_name', 'role_key'])
            ->toArray();
    }

    /**
     * 启用/禁用角色
     */
    public function toggleRoleStatus(int $roleId): bool
    {
        $role = SysRole::find($roleId);
        if (!$role instanceof SysRole) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        // 超级管理员角色不能禁用
        if ($roleId == 1) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '超级管理员角色不能禁用');
        }
        
        $role->status = $role->status == 1 ? 0 : 1;
        $role->save();

        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 复制角色权限
     */
    public function copyRolePermissions(int $fromRoleId, int $toRoleId): bool
    {
        $fromRole = SysRole::find($fromRoleId);
        $toRole = SysRole::find($toRoleId);
        
        if (!$fromRole || !$toRole) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }
        
        // 获取源角色的菜单权限
        $menuIds = SysRoleMenu::where('role_id', $fromRoleId)->pluck('menu_id')->toArray();
        
        // 分配给目标角色
        $this->assignMenusToRole($toRoleId, $menuIds);
        
        return true;
    }

    /**
     * 获取角色的部门权限
     */
    public function getRoleDepts(int $roleId): array
    {
        $role = SysRole::find($roleId);
        if (!$role) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '角色不存在');
        }

        return $role->depts()->pluck('dept_id')->toArray();
    }
}
