<?php

namespace App\Traits;
use App\Model\User; 
use ZYProSoft\Log\Log;
use ZYProSoft\Exception\HyperfCommonException;
use App\Constants\ErrorCode;
use Carbon\Carbon;

trait UserServiceTrait
{
    /**
     * 下发登录凭证
     * @param User|null $user
     * @param bool $forceRefresh
     * @return User
     */
    protected function innerCreateOrRefreshUserTicket(User $user = null, bool $forceRefresh = false )
    {
        if (!isset($user)) {
            $currentUser = $this->user();
            if ($currentUser instanceof User) {
                $user = $currentUser;
            }
        }

        if (!$user instanceof User) {
            Log::error("登录或者刷新票据没有找到对应用户!");
            throw new HyperfCommonException(ErrorCode::USER_NOT_FOUND,'用户不存在');
        }

        if ($forceRefresh) {
            // 临时显示敏感字段以便进行登录操作
            $user->makeVisible(['token', 'token_expire', 'token_refresh_expire']);
            
            //token过期时间,单位是秒
            $expireTime = Carbon::now()->addRealSeconds($this->authTTL);
            $user->token_expire = $expireTime;
            //token可以刷新的过期时间,允许过期多少天内的Token刷新换票，减少用户登录次数
            $refreshExpireTime = $expireTime->addRealSeconds($this->authRefreshTTL);
            $user->token_refresh_expire = $refreshExpireTime;
            $user->token = $this->auth->login($user);
            $user->saveOrFail();
            Log::info("用户{$user->user_id}登录票据已经强制刷新!");
            return $user;
        }

        $isTokenEmpty = empty($user->token);
        $isTokenExpire = false;
        if (!$isTokenEmpty) {
            //token是否过期,没过期的话直接返回，不刷新用户信息
            $tokenExpire = Carbon::createFromTimeString($user->token_expire);
            $isTokenExpire = Carbon::now()->isAfter($tokenExpire);
        }

        //redis中的登录态是否丢掉了
        $authTokenNotExist = $this->auth->guest() === true;
        if ($authTokenNotExist) {
            Log::info("登录态不存在于redis缓存中...");
        }

        //是不是需要或刷新Token
        if ($isTokenEmpty || $isTokenExpire || $authTokenNotExist) {
            // 临时显示敏感字段以便进行登录操作
            $user->makeVisible(['token', 'token_expire', 'token_refresh_expire']);
            
            Log::info("用户Token为空：{$isTokenEmpty}");
            Log::info("用户Token过期：{$isTokenExpire}");
            //token过期时间,单位是秒
            $expireTime = Carbon::now()->addRealSeconds($this->authTTL);
            $user->token_expire = $expireTime;
            //token可以刷新的过期时间,允许过期多少天内的Token刷新换票，减少用户登录次数
            $refreshExpireTime = $expireTime->addRealSeconds($this->authRefreshTTL);
            $user->token_refresh_expire = $refreshExpireTime;
            $user->token = $this->auth->login($user);
            $user->saveOrFail();
            Log::info("用户{$user->user_id}登录票据已经刷新!");
        } else {
            Log::info("用户{$user->user_id}登录票据仍然有效，无需刷新!");
        }

        return $user;
    }
}
