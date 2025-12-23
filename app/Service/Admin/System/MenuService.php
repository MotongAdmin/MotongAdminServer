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

use App\Model\SysMenu;
use App\Model\SysMenuApi;
use App\Model\SysApi;
use App\Model\SysRoleMenu;
use App\Model\User;
use App\Constants\ErrorCode;
use App\Constants\Constants;
use ZYProSoft\Exception\HyperfCommonException;
use Hyperf\Di\Annotation\Inject;
use App\Service\Admin\System\PermissionService;
use ZYProSoft\Facade\Auth;
use ZYProSoft\Log\Log;

/**
 * 菜单管理服务类
 */
class MenuService extends BaseService
{

    /**
     * @Inject
     * @var PermissionService
     */
    protected PermissionService $permissionService; 

    /**
     * 获取菜单树
     */
    public function getMenuTree(bool $onlyVisible = false): array
    {
        $query = SysMenu::where('status', 1);
        
        if ($onlyVisible) {
            $query->where('visible', 1);
        }
        
        $menus = $query->orderBy('parent_id')
            ->orderBy('order_num')
            ->get()
            ->toArray();
            
        return $this->buildMenuTree($menus);
    }

    /**
     * 获取菜单列表（扁平化）
     */
    public function getMenuList(string $keyword = '', string $status = ''): array
    {
        $query = SysMenu::query();
        
        if (!empty($keyword)) {
            $query->where('menu_name', 'like', "%{$keyword}%");
        }
        
        if ($status !== '') {
            $query->where('status', $status);
        }
        
        return $query->orderBy('parent_id')
            ->orderBy('order_num')
            ->get()
            ->toArray();
    }

    /**
     * 创建菜单
     */
    public function createMenu(array $data): int
    {
        // 验证父菜单是否存在
        if ($data['parent_id'] > 0) {
            $parentMenu = SysMenu::find($data['parent_id']);
            if (!$parentMenu) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '父菜单不存在');
            }
        }
        
        // 验证权限标识唯一性
        if (!empty($data['perms'])) {
            if (SysMenu::where('perms', $data['perms'])->exists()) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '权限标识已存在');
            }
        }
        
        $menu = SysMenu::create($data);

        // 刷新权限缓存
        $this->permissionService->refreshPermissionCache();

        //记录操作日志
        $this->addOperationLog();
        
        return $menu->menu_id;
    }

    /**
     * 更新菜单
     */
    public function updateMenu(int $menuId, array $data): bool
    {
        $menu = SysMenu::find($menuId);
        if (!$menu) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '菜单不存在');
        }
        
        // 验证父菜单
        if (isset($data['parent_id'])) {
            // 不能设置自己为父菜单
            if ($data['parent_id'] == $menuId) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '不能设置自己为父菜单');
            }
            
            // 验证父菜单是否存在
            if ($data['parent_id'] > 0) {
                $parentMenu = SysMenu::find($data['parent_id']);
                if (!$parentMenu) {
                    throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '父菜单不存在');
                }
                
                // 验证不能设置子菜单为父菜单（避免循环）
                if ($this->isChildMenu($menuId, $data['parent_id'])) {
                    throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '不能设置子菜单为父菜单');
                }
            }
        }
        
        // 验证权限标识唯一性
        if (!empty($data['perms'])) {
            if (SysMenu::where('perms', $data['perms'])
                ->where('menu_id', '!=', $menuId)
                ->exists()) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '权限标识已存在');
            }
        }
        
        $menu->update($data);

        //刷新权限缓存
        $this->permissionService->refreshPermissionCache();

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 删除菜单
     */
    public function deleteMenu(int $menuId): bool
    {
        $menu = SysMenu::find($menuId);
        if (!$menu) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '菜单不存在');
        }
        
        // 检查是否有子菜单
        if (SysMenu::where('parent_id', $menuId)->exists()) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '存在子菜单，不能删除');
        }
        
        // 删除菜单角色关联
        SysRoleMenu::where('menu_id', $menuId)->delete();
        
        // 删除菜单API关联
        SysMenuApi::where('menu_id', $menuId)->delete();
        
        // 软删除菜单
        $menu->delete();

        //刷新权限缓存
        $this->permissionService->refreshPermissionCache();

        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 获取菜单详情
     */
    public function getMenuDetail(int $menuId): array
    {
        $menu = SysMenu::find($menuId);
        if (!$menu) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '菜单不存在');
        }
        
        $menuData = $menu->toArray();
        
        // 获取关联的API
        $apiIds = SysMenuApi::where('menu_id', $menuId)->pluck('api_id')->toArray();
        $menuData['api_ids'] = $apiIds;
        
        return $menuData;
    }

    /**
     * 获取父级菜单选项
     */
    public function getParentMenuOptions(): array
    {
        $menus = SysMenu::where('status', 1)
            ->whereIn('menu_type', ['M', 'C']) // 只有目录和菜单可以作为父菜单
            ->orderBy('parent_id')
            ->orderBy('order_num')
            ->get(['menu_id', 'menu_name', 'parent_id'])
            ->toArray();
            
        return $this->buildMenuTree($menus);
    }

    /**
     * 分配API给菜单
     */
    public function assignApisToMenu(int $menuId, array $apiIds): bool
    {
        // 直接调用更新菜单接口关联的方法，它会处理所有逻辑包括更新角色权限
        $result = $this->updateMenuApiRelations($menuId, $apiIds);

        //记录操作日志
        $this->addOperationLog();

        return $result;
    }

    /**
     * 启用/禁用菜单
     */
    public function toggleMenuStatus(int $menuId): bool
    {
        $menu = SysMenu::find($menuId);
        if (!$menu instanceof SysMenu) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '菜单不存在');
        }
        
        $menu->status = $menu->status == 1 ? 0 : 1;
        $menu->save();

        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 获取当前用户可分配的菜单树
     */
    public function getAssignableMenuTree(): array
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser instanceof User) {
                Log::error("MenuService: Current user not found");
                return [];
            }
            
            // 超级管理员可以分配所有菜单
            if ($currentUser->role_id == Constants::USER_ROLE_SUPER_ADMIN) {
                Log::info("MenuService: Super admin getting all menus");
                return $this->getMenuTree();
            }
            
            // 获取当前用户拥有的菜单权限
            $userMenuIds = SysRoleMenu::where('role_id', $currentUser->role_id)
                ->pluck('menu_id')
                ->toArray();
            
            if (empty($userMenuIds)) {
                Log::info("MenuService: User has no menu permissions" . json_encode([
                    'user_id' => $currentUser->user_id,
                    'role_id' => $currentUser->role_id
                ]));
                return [];
            }
            
            // 获取用户有权限的菜单
            $menus = SysMenu::whereIn('menu_id', $userMenuIds)
                ->where('status', Constants::MENU_STATUS_NORMAL)
                ->orderBy('parent_id')
                ->orderBy('order_num')
                ->get()
                ->toArray();
            
            Log::info("MenuService: Getting assignable menu tree" . json_encode([
                'user_id' => $currentUser->user_id,
                'role_id' => $currentUser->role_id,
                'menu_count' => count($menus)
            ]));
            
            // 构建菜单树
            return $this->buildMenuTree($menus);
            
        } catch (\Throwable $e) {
            Log::error("MenuService: Error getting assignable menu tree" . json_encode([
                'error' => $e->getMessage()
            ]));
            return [];
        }
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
     * 检查是否为子菜单
     */
    private function isChildMenu(int $parentId, int $childId): bool
    {
        $menu = SysMenu::find($childId);
        if (!$menu instanceof SysMenu) {
            return false;
        }
        
        if ($menu->parent_id == $parentId) {
            return true;
        }
        
        if ($menu->parent_id == 0) {
            return false;
        }
        
        return $this->isChildMenu($parentId, $menu->parent_id);
    }

    /**
     * 获取菜单权限路径
     */
    public function getMenuPermissions(): array
    {
        return SysMenu::whereNotNull('perms')
            ->where('perms', '!=', '')
            ->where('status', 1)
            ->get(['menu_id', 'menu_name', 'perms'])
            ->toArray();
    }

    /**
     * 批量更新菜单排序
     */
    public function updateMenuSort(array $menuSorts): bool
    {
        foreach ($menuSorts as $item) {
            SysMenu::where('menu_id', $item['menu_id'])
                ->update(['order_num' => $item['order_num']]);
        }
        
        return true;
    }

    /**
     * 获取菜单已绑定和未绑定的API
     */
    public function getMenuApis(int $menuId, string $keyword = '', string $group = ''): array
    {
        $menu = SysMenu::find($menuId);
        if (!$menu) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '菜单不存在');
        }
        
        // 获取已绑定API的ID
        $boundApiIds = SysMenuApi::where('menu_id', $menuId)
            ->pluck('api_id')
            ->toArray();
        
        // 构建基础查询
        $query = function($q) use ($keyword, $group) {
            if (!empty($keyword)) {
                $q->where(function($subQuery) use ($keyword) {
                    $subQuery->where('api_name', 'like', "%{$keyword}%")
                        ->orWhere('api_path', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }
            
            if (!empty($group)) {
                $q->where('api_group', $group);
            }
            
            return $q->where('status', 1);
        };
        
        // 获取已绑定的API详情
        $boundApis = [];
        if (!empty($boundApiIds)) {
            $boundApis = SysApi::whereIn('api_id', $boundApiIds)
                ->where(function($q) use ($query) {
                    return $query($q);
                })
                ->get()
                ->toArray();
                
            // 提取实际符合条件的已绑定API ID
            $filteredBoundApiIds = array_column($boundApis, 'api_id');
        } else {
            $filteredBoundApiIds = [];
        }
        
        // 获取未绑定的API
        $unboundApis = SysApi::whereNotIn('api_id', $boundApiIds)
            ->where(function($q) use ($query) {
                return $query($q);
            })
            ->get()
            ->toArray();
        
        return [
            'boundApis' => $boundApis,
            'unboundApis' => $unboundApis,
            'boundApiIds' => $boundApiIds,        // 所有已绑定的API ID
            'filteredBoundApiIds' => $filteredBoundApiIds // 过滤后符合条件的已绑定API ID
        ];
    }

    /**
     * 更新菜单接口关联并重新计算角色权限
     *
     * 解决当菜单接口关联变更时角色权限不自动更新的问题
     * 
     * @param int $menuId 菜单ID
     * @param array $apiIds 接口ID数组
     * @return bool
     */
    public function updateMenuApiRelations(int $menuId, array $apiIds): bool
    {
        // 验证菜单是否存在
        $menu = SysMenu::find($menuId);
        if (!$menu) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '菜单不存在');
        }
        
        // 验证API ID是否存在
        if (!empty($apiIds)) {
            $existApiIds = SysApi::whereIn('api_id', $apiIds)->pluck('api_id')->toArray();
            $invalidApiIds = array_diff($apiIds, $existApiIds);
            if (!empty($invalidApiIds)) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '存在无效的API ID: ' . implode(',', $invalidApiIds));
            }
        }
        
        // 1. 删除现有菜单API关联
        SysMenuApi::where('menu_id', $menuId)->delete();
        
        // 2. 添加新的菜单API关联
        if (!empty($apiIds)) {
            $data = [];
            foreach ($apiIds as $apiId) {
                $data[] = [
                    'menu_id' => $menuId,
                    'api_id' => $apiId
                ];
            }
            SysMenuApi::insert($data);
        }
        
        // 3. 获取所有关联此菜单的角色
        $roleIds = SysRoleMenu::where('menu_id', $menuId)
            ->pluck('role_id')
            ->toArray();
        
        // 4. 对每个角色重新计算并更新权限
        if (!empty($roleIds)) {
            $permissionService = $this->container->get(PermissionService::class);
            
            foreach ($roleIds as $roleId) {
                // 获取角色所有菜单
                $roleMenuIds = SysRoleMenu::where('role_id', $roleId)
                    ->pluck('menu_id')
                    ->toArray();
                
                // 重建该角色权限
                $permissionService->assignMenuToRole($roleId, $roleMenuIds);
            }
        }
        
        return true;
    }
}
