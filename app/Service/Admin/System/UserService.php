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

use App\Model\User;
use App\Constants\ErrorCode;
use Carbon\Carbon;
use Psr\Container\ContainerInterface;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use App\Service\Base\DataScopeService;
use Hyperf\Di\Annotation\Inject;
use App\Traits\UserServiceTrait;

class UserService extends BaseService
{
    use UserServiceTrait;

    //token过期时间配置
    protected int $authTTL;

    //token刷新过期时间配置
    protected int $authRefreshTTL;

    /**
     * @Inject
     * @var DataScopeService
     */
    protected DataScopeService $dataScopeService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->authTTL = config('auth.guards.jwt.ttl');
        $this->authRefreshTTL = config('auth.guards.jwt.refresh_ttl');
        Log::info("auth ttl:{$this->authTTL}");
        Log::info("auth refresh ttl:{$this->authRefreshTTL}");
    }

    final public function login(string $username, string $password)
    {
        $user = User::where('username', $username)->firstOrFail();
        if (!$user instanceof User) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST);
        }

        if (password_verify($password, $user->password) === false) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,'密码输入错误!');
        }

        //登录
        $user = $this->innerCreateOrRefreshUserTicket($user);

        //记录操作日志
        $this->addOperationLog();

        return $user;
    }

    final public function createUser(array $params)
    {
        $user = new User();
        $user->username = data_get($params, 'username');
        $user->nickname = data_get($params, 'nickname', '');
        $user->role_id = data_get($params, 'role_id', 0);
        $user->dept_id = data_get($params, 'dept_id', 0);
        $user->post_id = data_get($params, 'post_id', 0);
        $user->mobile = data_get($params, 'mobile');
        $user->email = data_get($params, 'email');
        $user->password = password_hash(data_get($params, 'password'), PASSWORD_DEFAULT);
        $user->saveOrFail();
        $user->refresh();

        //记录操作日志
        $this->addOperationLog();

        return $user;
    }

    final public function updateUser(array $params)
    {
        $userId = data_get($params,'user_id');
        $user = User::find($userId);
        if (!$user instanceof User) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST,"用户不存在!");
        }

        $user->nickname = data_get($params,'nickname','');
        $user->avatar = data_get($params,'avatar','');
        $user->role_id = data_get($params,'role_id',0);
        $user->dept_id = data_get($params,'dept_id',0);
        $user->post_id = data_get($params,'post_id',0);
        $mobile = data_get($params, 'mobile', '');
        // 如果手机号中包含*号，说明本次没有修改手机号，可以忽略不更新
        if (!empty($mobile) && !str_contains($mobile, '*')) {
            $user->mobile = $mobile;
        }
        $user->email = data_get($params,'email','');
        $user->status = data_get($params,'status',1);
        $user->saveOrFail();
        $user->refresh();

        // 判断用户的角色Id是否发生变化，如果变化了，清除权限缓存
        if($user->role_id != data_get($params,'role_id',0)) {
            $this->dataScopeService->clearUserDataScopeCache($userId);
        }

        //记录操作日志
        $this->addOperationLog();

        return $user;
    }

    final public function updatePassword(array $params)
    {
        $user = User::find($this->userId());
        if (!$user instanceof User) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST,"用户不存在!");
        }

        $origin = data_get($params, 'origin');
        $update = data_get($params, 'update');

        if (password_verify($origin, $user->password) === false) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,"原始密码错误!");
        }

        if (password_verify($update, $user->password) === true) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,"新密码不能和旧密码一致!");
        }

        $user->password = password_hash($update, PASSWORD_DEFAULT);
        $user->saveOrFail();

        //记录操作日志
        $this->addOperationLog();

        return $user;
    }
    
    /**
     * 获取用户列表
     * @param array $params 查询参数
     * @return array 用户列表数据
     */
    final public function getUserList(array $params = [])
    {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $size = isset($params['size']) ? (int)$params['size'] : 20;
        $keyword = isset($params['keyword']) ? $params['keyword'] : '';
        
        $query = User::query();
        
        // 根据角色ID筛选
        if (isset($params['role_id']) && $params['role_id'] > 0) {
            $query->where('role_id', $params['role_id']);
        }
        
        // 根据部门ID筛选
        if (isset($params['dept_id']) && $params['dept_id'] > 0) {
            $query->where('dept_id', $params['dept_id']);
        }
        
        // 根据职位ID筛选
        if (isset($params['post_id']) && $params['post_id'] > 0) {
            $query->where('post_id', $params['post_id']);
        }
        
        // 关键字搜索
        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('mobile', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }
        
        // 状态筛选
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        // 添加数据范围筛选
        $this->dataScopeService->applyDataScopeFilter($query);
        
        $total = $query->count();
        $list = $query->with(['role', 'dept', 'post']) // 加载角色、部门、职位关联
                      ->orderBy('user_id', 'desc')
                      ->offset(($page - 1) * $size)
                      ->limit($size)
                      ->get()
                      ->toArray();

        $list = array_map(function($item) {
            // 如果手机号不为空，隐藏中间部分为*号，只保留前三位和后四位
            if (!empty($item['mobile'])) {
                $item['mobile'] = substr($item['mobile'], 0, 3) . '****' . substr($item['mobile'], -4);
            }
            return $item;
        }, $list);
                      
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'pages' => ceil($total / $size)
        ];
    }

    final public function logout()
    {
        //记录操作日志
        $this->addOperationLog();

        //登出
        $this->auth->logout();
    }

    final public function toggleStatus(int $userId)
    {
        $user = User::find($userId);
        if (!$user instanceof User) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST,"用户不存在!");
        }

        $user->status = $user->status == 1 ? 0 : 1;
        $user->saveOrFail();

        //记录操作日志
        $this->addOperationLog();

        return $user;
    }

    /**
     * 更新用户资料（用户自己修改）
     * @param array $params 资料参数
     * @return User 更新后的用户信息
     */
    final public function updateProfile(array $params)
    {
        $user = User::find($this->userId());
        if (!$user instanceof User) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST,"用户不存在!");
        }

        // 只允许更新昵称、邮箱、头像
        if (isset($params['nickname'])) {
            $user->nickname = $params['nickname'];
        }
        
        if (isset($params['email'])) {
            // 检查邮箱是否已被其他用户使用
            $existingUser = User::where('email', $params['email'])
                               ->where('user_id', '!=', $user->user_id)
                               ->first();
            if ($existingUser) {
                throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "邮箱已被其他用户使用!");
            }
            $user->email = $params['email'];
        }
        
        if (isset($params['avatar'])) {
            $user->avatar = $params['avatar'];
        }

        $user->saveOrFail();
        $user->refresh();

        //记录操作日志
        $this->addOperationLog();

        Log::info("用户{$user->user_id}更新了个人资料");

        return $user;
    }
}