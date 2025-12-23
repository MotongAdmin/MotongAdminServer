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

use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use Hyperf\Di\Annotation\Inject;
use App\Model\SysRole;
use App\Model\User;
use App\Service\Base\StorageService;

/**
 * 认证服务类
 */
class AuthService extends BaseService
{
    /**
     * @Inject
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * @Inject
     * @var StorageService
     */
    protected StorageService $storageService;

    /**
     * 获取用户菜单
     */
    public function getUserMenus(): array
    {
        return $this->permissionService->getUserMenus($this->userId());
    }

    /**
     * 获取用户权限
     */
    public function getUserPermissions(): array
    {
        return $this->permissionService->getUserPermissions($this->userId());
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo(): array
    {
        $user = $this->user();
        if (!$user instanceof User) {
            throw new HyperfCommonException(ErrorCode::USER_NOT_FOUND, '用户未登录');
        }

        $userRole = SysRole::where('role_id', $user->role_id)->first();

        //获取用户头像
        if(!empty($user->avatar)) {
            $avatar = $this->storageService->getImageUrlByObjectId($user->avatar);
        }
        
        return [
            'userId' => $user->user_id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'avatar' => $avatar ?? '',
            'roleId' => $user->role_id,
            'dataScope' => $userRole->data_scope,
            'role' => $userRole ? [
                'roleId' => $userRole->role_id,
                'roleName' => $userRole->role_name,
                'roleKey' => $userRole->role_key
            ] : null,
            'status' => $user->status
        ];
    }

    /**
     * 检查用户权限
     */
    public function checkPermission(string $resource): array
    {
        $hasPermission = $this->permissionService->checkPermission($this->userId(), $resource);
        
        return [
            'hasPermission' => $hasPermission,
            'resource' => $resource
        ];
    }

    /**
     * 为用户分配角色
     */
    public function assignRole(int $userId, int $roleId): void
    {
        $this->permissionService->assignRoleToUser($userId, $roleId);
    }
} 