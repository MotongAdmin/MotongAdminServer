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

namespace App\Controller\Admin\System;

use ZYProSoft\Http\AuthedRequest;
use App\Service\Admin\System\RoleService;
use App\Service\Admin\System\RolePermissionService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 角色管理控制器
 * @AutoController(prefix="/system/role")
 */
class RoleController extends AbstractController
{
    /**
     * @Inject
     * @var RoleService
     */
    protected RoleService $roleService;

    /**
     * @Inject
     * @var RolePermissionService
     */
    protected RolePermissionService $rolePermissionService;

    /**
     * 自定义验证错误消息
     * @return array
     */
    public function messages()
    {
        return [
            // 通用规则消息
            'required' => ':attribute不能为空',
            'string' => ':attribute必须是字符串',
            'integer' => ':attribute必须是整数',
            'max' => ':attribute长度不能超过:max位',
            'min' => ':attribute的值不能小于:min',
            'unique' => ':attribute已存在',
            'exists' => ':attribute不存在',
            'in' => ':attribute的值不在允许范围内',
            'array' => ':attribute必须是数组',
            
            // 字段特定消息
            'role_id.required' => '角色ID不能为空',
            'role_id.integer' => '角色ID必须是整数',
            'role_id.exists' => '角色不存在',
            
            'role_name.required' => '角色名称不能为空',
            'role_name.string' => '角色名称必须是字符串',
            'role_name.max' => '角色名称长度不能超过50位',
            
            'role_key.required' => '角色权限字符串不能为空',
            'role_key.string' => '角色权限字符串必须是字符串',
            'role_key.max' => '角色权限字符串长度不能超过50位',
            'role_key.unique' => '角色权限字符串已存在',
            
            'role_sort.integer' => '显示顺序必须是整数',
            'role_sort.min' => '显示顺序不能小于0',
            
            'status.in' => '角色状态只能是0或1',
            
            'remark.string' => '备注必须是字符串',
            'remark.max' => '备注长度不能超过500位',
            
            'menu_ids.required' => '菜单权限不能为空',
            'menu_ids.array' => '菜单权限必须是数组',
        ];
    }

    /**
     * @Description("获取角色列表")
     * ZGW接口名: system.role.getRoleList
     */
    public function getRoleList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'keyword' => 'string|max:50'
        ]);
        
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $keyword = $request->param('keyword', '');
        
        $result = $this->roleService->getRoleList($page, $size, $keyword);
        
        return $this->success($result);
    }

    /**
     * @Description("创建新角色")
     * ZGW接口名: system.role.createRole
     */
    public function createRole(AuthedRequest $request)
    {
        $this->validate([
            'role_name' => 'required|string|max:50',
            'role_key' => 'required|string|max:50|unique:sys_role,role_key',
            'role_sort' => 'integer|min:0',
            'status' => 'in:0,1',
            'remark' => 'string|max:500',
            'data_scope' => 'in:1,2,3,4',
            'dept_ids' => 'nullable|array',
            'dept_ids.*' => 'integer|exists:sys_dept,dept_id'
        ]);
        
        $data = [
            'role_name' => $request->param('role_name'),
            'role_key' => $request->param('role_key'),
            'role_sort' => $request->param('role_sort', 0),
            'status' => $request->param('status', 1),
            'remark' => $request->param('remark', ''),
            'data_scope' => $request->param('data_scope', 1),
            'dept_ids' => $request->param('dept_ids', [])
        ];
        
        $roleId = $this->roleService->createRole($data);
        
        return $this->success(['role_id' => $roleId]);
    }

    /**
     * @Description("更新角色信息")
     * ZGW接口名: system.role.updateRole
     */
    public function updateRole(AuthedRequest $request)
    {
        $roleId = $request->param('role_id');
        $this->validate([
            'role_id' => 'required|integer|exists:sys_role,role_id',
            'role_name' => 'string|max:50',
            'role_key' => "string|max:50|unique:sys_role,role_key,{$roleId},role_id",
            'role_sort' => 'integer|min:0',
            'status' => 'in:0,1',
            'remark' => 'string|max:500',
            'data_scope' => 'in:1,2,3,4',
            'dept_ids' => 'nullable|array',
            'dept_ids.*' => 'integer|exists:sys_dept,dept_id'
        ]);
        
        $data = array_filter([
            'role_name' => $request->param('role_name'),
            'role_key' => $request->param('role_key'),
            'role_sort' => $request->param('role_sort'),
            'status' => $request->param('status'),
            'remark' => $request->param('remark'),
            'data_scope' => $request->param('data_scope'),
        ], function($value) {
            return $value !== null;
        });
        
        // 单独处理dept_ids，因为它可能是空数组
        if ($request->hasParam('dept_ids')) {
            $data['dept_ids'] = $request->param('dept_ids', []);
        }
        
        $this->roleService->updateRole($roleId, $data);
        
        return $this->success([]);
    }

    /**
     * @Description("删除角色")
     * ZGW接口名: system.role.deleteRole
     */
    public function deleteRole(AuthedRequest $request)
    {
        $this->validate([
            'role_id' => 'required|integer|exists:sys_role,role_id'
        ]);
        
        $roleId = $request->param('role_id');
        $this->roleService->deleteRole($roleId);
        
        return $this->success([]);
    }

    /**
     * @Description("获取角色详情")
     * ZGW接口名: system.role.getRoleDetail
     */
    public function getRoleDetail(AuthedRequest $request)
    {
        $this->validate([
            'role_id' => 'required|integer|exists:sys_role,role_id'
        ]);
        
        $roleId = $request->param('role_id');
        $role = $this->roleService->getRoleDetail($roleId);
        
        return $this->success(['role' => $role]);
    }
    
    /**
     * @Description("获取角色菜单权限")
     * ZGW接口名: system.role.getRoleMenus
     */
    public function getRoleMenus(AuthedRequest $request)
    {
        $this->validate([
            'role_id' => 'required|integer|exists:sys_role,role_id'
        ]);
        
        $roleId = $request->param('role_id');
        $result = $this->roleService->getRoleMenus($roleId);
        
        return $this->success(['menu_ids' => $result['menuIds']]);
    }
    
    /**
     * @Description("设置角色菜单权限")
     * ZGW接口名: system.role.setRoleMenus
     */
    public function setRoleMenus(AuthedRequest $request)
    {
        $this->validate([
            'role_id' => 'required|integer|exists:sys_role,role_id',
            'menu_ids' => 'required|array'
        ]);
        
        $roleId = $request->param('role_id');
        $menuIds = $request->param('menu_ids');
        
        // 验证菜单权限是否在当前用户权限范围内
        if (!$this->rolePermissionService->canAssignMenus($menuIds)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '您没有权限分配部分菜单权限');
        }
        
        $this->roleService->assignMenusToRole($roleId, $menuIds);
        
        return $this->success([]);
    }

    /**
     * @Description("获取角色的部门权限")
     * ZGW接口名: system.role.getRoleDepts
     */
    public function getRoleDepts(AuthedRequest $request)
    {
        $this->validate([
            'role_id' => 'required|integer|exists:sys_role,role_id'
        ]);
        
        $roleId = $request->param('role_id');
        $deptIds = $this->roleService->getRoleDepts($roleId);
        
        return $this->success(['dept_ids' => $deptIds]);
    }

    /**
     * @Description("获取所有角色")
     * ZGW接口名: system.role.getAllRoles
     */
    public function getAllRoles(AuthedRequest $request)
    {
        $roles = $this->roleService->getAllRoles();
        
        return $this->success(['roles' => $roles]);
    }

    /**
     * @Description("获取可分配的角色列表")
     * ZGW接口名: system.role.getAssignableRoles
     */
    public function getAssignableRoles(AuthedRequest $request)
    {
        $roles = $this->rolePermissionService->getAssignableRoles();
        
        return $this->success(['roles' => $roles]);
    }
}
