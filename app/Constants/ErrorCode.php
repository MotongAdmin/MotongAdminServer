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
namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Server Error！")
     */
    const SERVER_ERROR = 500;
    
    /**
     * @Message("参数错误")
     */
    const PARAM_ERROR = 1001;
    
    /**
     * @Message("记录不存在")
     */
    const RECORD_NOT_EXIST = 1002;
    
    /**
     * @Message("业务处理错误")
     */
    const BUSINESS_ERROR = 1003;
    
    /**
     * @Message("用户未找到")
     * @desc 与token未携带、无效或过期相关的错误码，前端可据此判断未登录或token失效
     */
    const USER_NOT_FOUND = 2001;
    
    
    /**
     * @Message("用户认证失败")
     * @desc 与token未携带、无效或过期相关的错误码，前端可据此判断未登录或token失效
     */
    const AUTH_ERROR = 2002;
    
    /**
     * @Message("权限不足")
     */
    const PERMISSION_DENY = 2003;
    
    /**
     * @Message("角色不存在")
     */
    const ROLE_NOT_FOUND = 3001;
    
    /**
     * @Message("角色已存在")
     */
    const ROLE_ALREADY_EXISTS = 3002;
    
    /**
     * @Message("角色不能删除")
     */
    const ROLE_CANNOT_DELETE = 3003;
    
    /**
     * @Message("菜单不存在")
     */
    const MENU_NOT_FOUND = 4001;
    
    /**
     * @Message("菜单已存在")
     */
    const MENU_ALREADY_EXISTS = 4002;
    
    /**
     * @Message("菜单不能删除")
     */
    const MENU_CANNOT_DELETE = 4003;
    
    /**
     * @Message("权限标识已存在")
     */
    const PERMISSION_ALREADY_EXISTS = 4004;
    
    /**
     * @Message("API接口不存在")
     */
    const API_NOT_FOUND = 5001;
    
    /**
     * @Message("API接口已存在")
     */
    const API_ALREADY_EXISTS = 5002;
    
    /**
     * @Message("API接口不能删除")
     */
    const API_CANNOT_DELETE = 5003;
    
    /**
     * @Message("Casbin权限规则同步失败")
     */
    const CASBIN_SYNC_FAILED = 6001;
    
    /**
     * @Message("权限初始化失败")
     */
    const PERMISSION_INIT_FAILED = 6002;
    
    /**
     * @Message("权限验证失败")
     */
    const PERMISSION_CHECK_FAILED = 6003;
    
    /**
     * @Message("定时任务操作失败")
     */
    const CRONTAB_ERROR = 7001;

    /**
     * @Message("系统参数配置错误")
     */
    const CONFIG_ERROR = 600;

    /**
     * @Message("配置不存在")
     */
    const CONFIG_NOT_EXISTS = 10001;

    /**
     * @Message("默认配置不允许删除")
     */
    const DEFAULT_CONFIG_CANNOT_DELETE = 10002;

    /**
     * @Message("默认配置不允许禁用")
     */
    const DEFAULT_CONFIG_CANNOT_DISABLE = 10003;

    /**
     * @Message("禁用状态的配置不能设为默认")
     */
    const DISABLED_CONFIG_CANNOT_SET_DEFAULT = 10004;

    /**
     * @Message ("发送验证码太频繁，请稍后再试")
     */
    public const ERROR_BUSINESS_SEND_SMS_CODE_LIMIT = 20000;

    /**
     * @Message ("发送验证码失败")
     */
    public const ERROR_BUSINESS_SEND_SMS_CODE_FAIL = 20001;

    /**
     * @Message ("验证码已失效")
     */
    public const ERROR_BUSINESS_SMS_CODE_DID_EXPIRED = 20002;

    /**
     * @Message ("验证码填写错误")
     */
    public const ERROR_BUSINESS_SMS_CODE_NOT_VALIDATE = 20003;
}
