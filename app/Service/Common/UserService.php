<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  1003081775@qq.com
 * @author   zyvincent 
 * @Company  Icodefuture Information Technology Co., Ltd.
 * @license  GPL
 */


namespace App\Service\Common;
use App\Model\User;


class UserService extends BaseService
{
    public function createUserForOAuth(array $oauthUser)
    {
        $user = User::where('username', $oauthUser['username'])->first();
        if (!$user instanceof User) {
            $user = User::create($oauthUser);
        }
        return $user;
    }

    public function getUserById(int $userId)
    {
        return User::find($userId);
    }
}