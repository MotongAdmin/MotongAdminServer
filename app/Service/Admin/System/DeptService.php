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

namespace App\Service\Admin\System;

use App\Service\Admin\BaseService;
use App\Model\SysDept;
use App\Model\User;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use App\Model\SysRole;
use App\Model\SysRoleDept;

class DeptService extends BaseService
{
    /**
     * 获取部门列表（树形结构）
     * @param array $params 查询参数
     * @return array 部门树形数据
     */
    final public function getDeptList(array $params = [])
    {
        $keyword = data_get($params, 'keyword', '');
        $status = data_get($params, 'status');
        
        $query = SysDept::query();
        
        // 关键字搜索
        if (!empty($keyword)) {
            $query->where('dept_name', 'like', "%{$keyword}%");
        }
        
        // 状态筛选
        if (isset($status)) {
            $query->where('status', $status);
        }
        
        $depts = $query->ordered()->get()->toArray();
        
        // 构建树形结构
        $tree = $this->buildDeptTree($depts);
        
        return $tree;
    }

    /**
     * 获取部门树选择器数据
     * @return array 部门树数据
     */
    final public function getDeptTree()
    {
        $depts = SysDept::normal()->ordered()->get()->toArray();
        return $this->buildDeptTree($depts, 'tree');
    }

    /**
     * 非超级管理员的情况下，如果要授权部门数据，只能授权该角色所拥有的部门数据权限为最大范围
     * @throws \ZYProSoft\Exception\HyperfCommonException
     * @return array
     */
    final public function getDeptListByLoginUserRole()
    {
        $user = $this->user();
        if (!$user instanceof User) {
            return [];
        }

        $roleId = $user->role_id;
        $role = SysRole::find($roleId);
        if (!$role instanceof SysRole) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "角色不存在!");
        }

        // 数据范围（1：全部数据权限 2：本部门数据权限 3：本部门及以下数据权限 4：自定义数据权限）
        if ($role->data_scope == 1) {
            // 全部数据权限
            return $this->getDeptTree();
        } else if ($role->data_scope == 4) {
            // 自定义数据权限
            $deptIds = SysRoleDept::where('role_id', $roleId)->pluck('dept_id')->toArray();
            if (empty($deptIds)) {
                return [];
            }
            $depts = SysDept::whereIn('dept_id', $deptIds)->normal()->ordered()->get()->toArray();
            return $this->buildDeptTree($depts,'tree');
        } else if ($role->data_scope == 2 || $role->data_scope == 3) {

            $deptId = $user->dept_id;
            if (empty($deptId)) {
                return [];
            }
            
            $query = SysDept::where('status', 1)->ordered();
            if ($role->data_scope == 3) {
                // 本部门数据权限
                $query->where('dept_id', $deptId);
            } else {
                // 本部门及以下数据权限
                $dept = SysDept::find($deptId);
                if ($dept) {
                    $query->where('dept_path', 'like', $dept->dept_path . ',' . $deptId . '%')
                          ->orWhere('dept_id', $deptId);
                }
            }
            
            $depts = $query->get()->toArray();
            return $this->buildDeptTree($depts,'tree');
        }
        
        // 仅本人数据权限，返回空数组
        return [];
    }

    /**
     * 创建部门
     * @param array $params 部门参数
     * @return SysDept 创建的部门
     */
    final public function createDept(array $params)
    {
        $dept = new SysDept();
        $dept->parent_id = data_get($params, 'parent_id', 0);
        $dept->dept_name = data_get($params, 'dept_name');
        $dept->sort = data_get($params, 'sort', 0);
        $dept->leader = data_get($params, 'leader', '');
        $dept->phone = data_get($params, 'phone', '');
        $dept->email = data_get($params, 'email', '');
        $dept->status = data_get($params, 'status', 1);
        
        $dept->saveOrFail();
        
        // 更新部门路径
        $dept->updateDeptPath();
        
        //记录操作日志
        $this->addOperationLog();
        
        return $dept->refresh();
    }

    /**
     * 更新部门
     * @param array $params 部门参数
     * @return SysDept 更新的部门
     */
    final public function updateDept(array $params)
    {
        $deptId = data_get($params, 'dept_id');
        $dept = SysDept::find($deptId);
        if (!$dept instanceof SysDept) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "部门不存在!");
        }

        // 检查是否设置自己为上级部门
        $parentId = data_get($params, 'parent_id', 0);
        if ($parentId == $deptId) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "不能设置自己为上级部门!");
        }

        // 检查是否设置子部门为上级部门
        if ($parentId > 0) {
            $childIds = $this->getChildDeptIds($deptId);
            if (in_array($parentId, $childIds)) {
                throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "不能设置子部门为上级部门!");
            }
        }

        $oldParentId = $dept->parent_id;
        
        $dept->parent_id = $parentId;
        $dept->dept_name = data_get($params, 'dept_name');
        $dept->sort = data_get($params, 'sort', 0);
        $dept->leader = data_get($params, 'leader', '');
        $dept->phone = data_get($params, 'phone', '');
        $dept->email = data_get($params, 'email', '');
        $dept->status = data_get($params, 'status', 1);
        
        $dept->saveOrFail();
        
        // 如果上级部门改变，需要更新路径
        if ($oldParentId != $parentId) {
            $dept->updateDeptPath();
        }
        
        //记录操作日志
        $this->addOperationLog();
        
        return $dept->refresh();
    }

    /**
     * 删除部门
     * @param int $deptId 部门ID
     * @return bool 删除结果
     */
    final public function deleteDept(int $deptId)
    {
        $dept = SysDept::find($deptId);
        if (!$dept instanceof SysDept) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "部门不存在!");
        }

        // 检查是否有子部门
        $childCount = SysDept::where('parent_id', $deptId)->count();
        if ($childCount > 0) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "存在子部门，无法删除!");
        }

        // 检查是否有关联用户
        $userCount = User::where('dept_id', $deptId)->count();
        if ($userCount > 0) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "部门下存在用户，无法删除!");
        }

        $dept->delete();
        
        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 切换部门状态
     * @param int $deptId 部门ID
     * @return SysDept 更新的部门
     */
    final public function toggleStatus(int $deptId)
    {
        $dept = SysDept::find($deptId);
        if (!$dept instanceof SysDept) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "部门不存在!");
        }

        $dept->status = $dept->status == 1 ? 0 : 1;
        $dept->saveOrFail();

        // 如果禁用部门，同时禁用所有子部门
        if ($dept->status == 0) {
            $this->disableChildDepts($deptId);
        }
        
        //记录操作日志
        $this->addOperationLog();
        
        return $dept;
    }

    /**
     * 构建部门树形结构
     * @param array $depts 部门数组
     * @param string $type 类型（tree为选择器树形）
     * @param int $parentId 父级ID
     * @return array 树形数组
     */
    protected function buildDeptTree(array $depts, string $type = 'list', int $parentId = 0)
    {
        $tree = [];
        
        foreach ($depts as $dept) {
            if ($dept['parent_id'] == $parentId) {
                $children = $this->buildDeptTree($depts, $type, $dept['dept_id']);
                
                if ($type == 'tree') {
                    // 选择器树形结构
                    $node = [
                        'value' => $dept['dept_id'],
                        'label' => $dept['dept_name'],
                        'children' => $children
                    ];
                    if (empty($children)) {
                        unset($node['children']);
                    }
                } else {
                    // 普通列表树形结构
                    $node = $dept;
                    if (!empty($children)) {
                        $node['children'] = $children;
                    }
                }
                
                $tree[] = $node;
            }
        }
        
        return $tree;
    }

    /**
     * 获取子部门ID数组
     * @param int $deptId 部门ID
     * @return array 子部门ID数组
     */
    protected function getChildDeptIds(int $deptId)
    {
        $childIds = [];
        $children = SysDept::where('parent_id', $deptId)->get();
        
        foreach ($children as $child) {
            $childIds[] = $child->dept_id;
            $childIds = array_merge($childIds, $this->getChildDeptIds($child->dept_id));
        }
        
        return $childIds;
    }

    /**
     * 禁用子部门
     * @param int $deptId 部门ID
     */
    protected function disableChildDepts(int $deptId)
    {
        $children = SysDept::where('parent_id', $deptId)->get();
        foreach ($children as $child) {
            $child->status = 0;
            $child->save();
            
            // 递归禁用子部门
            $this->disableChildDepts($child->dept_id);
        }
    }
}
