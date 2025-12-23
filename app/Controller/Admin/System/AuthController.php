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
use App\Service\Admin\System\AuthService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Http\AuthedRequest;

/**
 * 权限认证控制器
 * @AutoController(prefix="/system/auth")
 */
class AuthController extends AbstractController
{
    /**
     * @Inject
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * @Description(value="获取用户菜单")
     * ZGW接口名: system.auth.getUserMenus
     */
    public function getUserMenus(AuthedRequest $request)
    {
        $menus = $this->authService->getUserMenus();
        
        return $this->success([
            'menus' => $menus
        ]);
    }

    /**
     * @Description(value="获取用户权限")
     * ZGW接口名: system.auth.getUserPermissions
     */
    public function getUserPermissions(AuthedRequest $request)
    {
        $permissions = $this->authService->getUserPermissions();
        
        return $this->success([
            'permissions' => $permissions
        ]);
    }

    /**
     * @Description(value="获取用户信息")
     * ZGW接口名: system.auth.getUserInfo
     */
    public function getUserInfo(AuthedRequest $request)
    {
        $userInfo = $this->authService->getUserInfo();
        
        return $this->success([
            'user' => $userInfo
        ]);
    }

    /**
     * @Description(value="检查权限状态")
     * ZGW接口名: system.auth.checkPermission
     */
    public function checkPermission(AuthedRequest $request)
    {
        $this->validate([
            'resource' => 'required|string'
        ]);

        $resource = $this->request->param('resource');
        
        $result = $this->authService->checkPermission($resource);
        
        return $this->success($result);
    }

    /**
     * @Description(value="分配用户角色")
     * ZGW接口名: system.auth.assignRole
     */
    public function assignRole(AuthedRequest $request)
    {
        $this->validate([
            'user_id' => 'required|integer|exists:user,user_id',
            'role_id' => 'required|integer|exists:sys_role,role_id'
        ]);

        $userId = $this->request->param('user_id');
        $roleId = $this->request->param('role_id');
        
        $this->authService->assignRole($userId, $roleId);
        
        return $this->success([]);
    }
}
