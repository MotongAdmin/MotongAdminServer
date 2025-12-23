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

use App\Annotation\Description;
use App\Service\Admin\System\DeptService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Http\AuthedRequest;

/**
 * @AutoController(prefix="/system/dept")
 */
class DeptController extends AbstractController
{
    /**
     * @Inject
     * @var DeptService
     */
    protected DeptService $service;

    /**
     * 自定义验证错误消息
     * @return array
     */
    public function messages()
    {
        return [
            'required' => ':attribute不能为空',
            'string' => ':attribute必须是字符串',
            'integer' => ':attribute必须是整数',
            'max' => ':attribute长度不能超过:max位',
            'in' => ':attribute的值不在允许范围内',
            
            'dept_name.required' => '部门名称不能为空',
            'dept_name.max' => '部门名称长度不能超过128位',
            'parent_id.integer' => '上级部门ID必须是整数',
            'sort.integer' => '显示顺序必须是整数',
            'leader.max' => '负责人姓名长度不能超过128位',
            'phone.max' => '联系电话长度不能超过11位',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱长度不能超过64位',
            'status.in' => '状态值只能是0或1',
        ];
    }

    /**
     * @Description(value="获取部门列表")
     */
    final public function getDeptList(AuthedRequest $request)
    {
        // ZGW协议：system.dept.getDeptList
        $this->validate([
            'keyword' => 'nullable|string|max:50',
            'status' => 'nullable|in:0,1'
        ]);

        $params = $request->getParams();
        $result = $this->service->getDeptList($params);

        return $this->success($result);
    }

    /**
     * @Description(value="获取部门树选择器数据")
     */
    final public function getDeptTree(AuthedRequest $request)
    {
        // ZGW协议：system.dept.getDeptTree
        $result = $this->service->getDeptTree();

        return $this->success($result);
    }

    /**
     * @Description(value="创建部门")
     */
    final public function createDept(AuthedRequest $request)
    {
        // ZGW协议：system.dept.createDept
        $this->validate([
            'parent_id' => 'integer|min:0',
            'dept_name' => 'required|string|max:128',
            'sort' => 'integer|min:0',
            'leader' => 'string|max:128',
            'phone' => 'string|max:11',
            'email' => 'email|max:64',
            'status' => 'in:0,1'
        ]);

        $params = $request->getParams();
        $result = $this->service->createDept($params);

        return $this->success($result);
    }

    /**
     * @Description(value="更新部门")
     */
    final public function updateDept(AuthedRequest $request)
    {
        // ZGW协议：system.dept.updateDept
        $this->validate([
            'dept_id' => 'required|integer|min:1|exists:sys_dept,dept_id',
            'parent_id' => 'integer|min:0',
            'dept_name' => 'required|string|max:128',
            'sort' => 'integer|min:0',
            'leader' => 'string|max:128',
            'phone' => 'string|max:11',
            'email' => 'email|max:64',
            'status' => 'in:0,1'
        ]);

        $params = $request->getParams();
        $result = $this->service->updateDept($params);

        return $this->success($result);
    }

    /**
     * @Description(value="删除部门")
     */
    final public function deleteDept(AuthedRequest $request)
    {
        // ZGW协议：system.dept.deleteDept
        $this->validate([
            'dept_id' => 'required|integer|min:1|exists:sys_dept,dept_id'
        ]);

        $deptId = $request->param('dept_id');
        $this->service->deleteDept($deptId);

        return $this->success();
    }

    /**
     * @Description(value="切换部门状态")
     */
    final public function toggleStatus(AuthedRequest $request)
    {
        // ZGW协议：system.dept.toggleStatus
        $this->validate([
            'dept_id' => 'required|integer|min:1|exists:sys_dept,dept_id'
        ]);

        $deptId = $request->param('dept_id');
        $result = $this->service->toggleStatus($deptId);

        return $this->success($result);
    }

    /**
     * @Description(value="获取角色所拥有的部门数据权限")
     */
    final public function getDeptListByLoginUserRole()
    {
        $result = $this->service->getDeptListByLoginUserRole();

        return $this->success($result);
    }
}
