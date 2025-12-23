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


namespace App\Controller\Admin\System;
use App\Annotation\Description;
use App\Service\Admin\System\UserService;
use App\Service\Admin\System\RolePermissionService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Http\AuthedRequest;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * @AutoController (prefix="/system/user")
 * Class UserController
 * @package App\Controller\Admin
 */
class UserController extends AbstractController
{
    /**
     * @Inject
     * @var UserService
     */
    protected UserService $service;

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
            'email' => ':attribute格式不正确',
            'min' => ':attribute长度不能少于:min位',
            'max' => ':attribute长度不能超过:max位',
            'unique' => ':attribute已存在',
            'exists' => ':attribute不存在',
            'same' => ':attribute与:other不匹配',
            'in' => ':attribute的值不在允许范围内',
            
            // 字段特定消息
            'username.required' => '用户名不能为空',
            'username.min' => '用户名长度不能少于3位',
            'username.max' => '用户名长度不能超过12位',
            'username.unique' => '用户名已存在',
            
            'password.required' => '密码不能为空',
            'password.min' => '密码长度不能少于6位',
            'password.max' => '密码长度不能超过15位',
            
            'mobile.required' => '手机号不能为空',
            'mobile.min' => '手机号长度必须为11位',
            'mobile.max' => '手机号长度必须为11位',
            'mobile.unique' => '手机号已存在',
            
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '邮箱已存在',
            
            'nickname.max' => '昵称长度不能超过50位',
            
            'role_id.exists' => '角色不存在',
            
            'user_id.required' => '用户ID不能为空',
            'user_id.integer' => '用户ID必须是整数',
            'user_id.exists' => '用户不存在',
            
            'status.in' => '状态值只能是0或1',
            
            'origin.required' => '原密码不能为空',
            'origin.min' => '原密码长度不能少于6位',
            'origin.max' => '原密码长度不能超过15位',
            
            'update.required' => '新密码不能为空',
            'update.min' => '新密码长度不能少于6位',
            'update.max' => '新密码长度不能超过15位',
            
            'confirm.required' => '确认密码不能为空',
            'confirm.same' => '确认密码与新密码不匹配',
            
            'captcha.key.required' => '验证码key不能为空',
            'captcha.key.min' => '验证码key不能为空',
            'captcha.code.required' => '验证码不能为空',
            'captcha.code.min' => '验证码不能为空',
            
            // 更新资料相关消息
            'nickname.required' => '昵称不能为空',
            'nickname.min' => '昵称不能为空',
            'avatar.max' => '头像路径长度不能超过500个字符',
        ];
    }

    /**
     * @Description(value="用户登录认证")
     * 用户登录
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function login()
    {
        $this->validate([
            'username' => 'required|string|min:3|max:12',
            'password' => 'required|string|min:6|max:15',
            'captcha.key' => 'required|string|min:1',
            'captcha.code' => 'required|string|min:1',
        ]);
        //校验验证码是否通过
        $this->validateCaptcha();
        $username = $this->request->param('username');
        $password = $this->request->param('password');
        $result = $this->service->login($username, $password);
        return $this->success($result->toArray());
    }

    /**
     * @Description(value="用户退出登录")
     * 用户退出登录
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function logout()
    {
        $this->service->logout();
        return $this->success();
    }

    /**
     * @Description(value="获取用户列表")
     * 获取用户列表
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function getUserList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100'
        ]);
        
        $params = $this->request->getParams();
        $result = $this->service->getUserList($params);
        return $this->success($result);
    }

    /**
     * @Description(value="创建新用户")
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function createUser(AuthedRequest $request)
    {
        $this->validate([
            "username" => "required|string|min:3|max:12|unique:user,username",
            "mobile" => "required|string|min:11|max:11|unique:user,mobile",
            "role_id" => "exists:sys_role,role_id",
            "email" => "required|email|unique:user,email",
            "password" => "required|string|min:6|max:15",
            "nickname" => "string|max:50",
            "dept_id" => "nullable|integer|exists:sys_dept,dept_id",
            "post_id" => "nullable|integer|exists:sys_post,post_id"
        ]);
        
        $params = $request->getParams();
        
        // 验证是否可以分配该角色
        if (isset($params['role_id']) && !$this->rolePermissionService->canAssignRole((int)$params['role_id'])) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '您没有权限分配该角色');
        }
        
        $result = $this->service->createUser($params);
        return $this->success($result);
    }

    /**
     * @Description(value="更新用户")
     * 更新用户
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function updateUser(AuthedRequest $request)
    {
        $userId = $request->param('user_id');
        $this->validate([
            "user_id" => "required|integer|exists:user,user_id",
            "username" => "required|string|min:3|max:12|unique:user,username,{$userId},user_id",
            "avatar" => "nullable|string|max:255",
            "mobile" => "required|string|min:11|max:11|unique:user,mobile,{$userId},user_id",
            "role_id" => "exists:sys_role,role_id",
            "email" => "required|email|unique:user,email,{$userId},user_id",
            "nickname" => "nullable|string|max:50",
            "dept_id" => "nullable|integer|exists:sys_dept,dept_id",
            "post_id" => "nullable|integer|exists:sys_post,post_id",
            "status" => "in:0,1"
        ]);
        
        $params = $request->getParams();
        
        // 验证是否可以分配该角色
        if (isset($params['role_id']) && !$this->rolePermissionService->canAssignRole((int)$params['role_id'])) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '您没有权限分配该角色');
        }
        
        $this->service->updateUser($params);
        return $this->success();
    }

    /**
     * @Description(value="修改用户密码")
     * 用户更新密码
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function updatePassword(AuthedRequest $request)
    {
        $this->validate([
            "origin" => "required|string|min:6|max:15",
            "update" => "required|string|min:6|max:15",
            "confirm" => "required|same:update"
        ]);
        $params = $request->getParams();
        $result = $this->service->updatePassword($params);
        return $this->success($result);
    }

    /**
     * @Description(value="切换用户状态")
     * 切换用户状态
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function toggleStatus(AuthedRequest $request)
    {
        $this->validate([
            "user_id" => "required|integer|exists:user,user_id"
        ]);
        $userId = $this->request->param('user_id');
        $this->service->toggleStatus($userId);
        return $this->success();
    }

    /**
     * @Description(value="更新用户资料")
     * 用户更新自己的资料（昵称、邮箱、头像）
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    final public function updateProfile(AuthedRequest $request)
    {
        $this->validate([
            "nickname" => "required|string|min:1|max:20",
            "email" => "email|max:100",
            "avatar" => "string|max:500"
        ]);
        
        $params = [];
        
        // 昵称是必填的
        $params['nickname'] = $request->param('nickname');
        
        // 邮箱可选，但如果提供了就必须格式正确
        $email = $request->param('email');
        if (!empty($email)) {
            $params['email'] = $email;
        }
        
        // 头像可选
        $avatar = $request->param('avatar');
        if (!empty($avatar)) {
            $params['avatar'] = $avatar;
        }
        
        $result = $this->service->updateProfile($params);
        return $this->success($result);
    }
}