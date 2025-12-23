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


class Constants
{
    // 用户角色相关
    //超级管理员
    const USER_ROLE_SUPER_ADMIN = 1;
    
    // 用户状态
    const USER_STATUS_NORMAL = 1;
    const USER_STATUS_DISABLED = 0;
    
    // 角色状态
    const ROLE_STATUS_NORMAL = 1;
    const ROLE_STATUS_DISABLED = 0;
    
    // 菜单类型
    const MENU_TYPE_DIRECTORY = 'M';
    const MENU_TYPE_MENU = 'C';
    const MENU_TYPE_BUTTON = 'F';
    
    // 菜单状态
    const MENU_STATUS_NORMAL = 1;
    const MENU_STATUS_DISABLED = 0;
    
    // 菜单可见性
    const MENU_VISIBLE_SHOW = 1;
    const MENU_VISIBLE_HIDE = 0;
    
    // API状态
    const API_STATUS_NORMAL = 1;
    const API_STATUS_DISABLED = 0;
    
    // 删除标志
    const DELETE_FLAG_EXIST = 0;
    const DELETE_FLAG_DELETED = 1;
    
    // 缓存相关
    const CACHE_FRAME_YES = 1;
    const CACHE_FRAME_NO = 0;
    
    // 外链标识
    const IS_FRAME_YES = 1;
    const IS_FRAME_NO = 0;
    
    // 权限标识分隔符
    const PERMISSION_SEPARATOR = ':';
    
    // 默认权限动作
    const PERMISSION_ACTION_ACCESS = 'access';
    const PERMISSION_ACTION_VIEW = 'view';
    const PERMISSION_ACTION_CREATE = 'create';
    const PERMISSION_ACTION_UPDATE = 'update';
    const PERMISSION_ACTION_DELETE = 'delete';
    
    // Casbin主体前缀
    const CASBIN_USER_PREFIX = 'user_';
    const CASBIN_ROLE_PREFIX = 'role_';
    
    // 超级管理员角色ID
    const SUPER_ADMIN_ROLE_ID = 1;
    
    // 配置项状态
    const CONFIG_STATUS_NORMAL = 1;
    const CONFIG_STATUS_DISABLED = 0;
    
    // 是否默认配置
    const CONFIG_IS_DEFAULT_YES = 1;
    const CONFIG_IS_DEFAULT_NO = 0;
    
    // HTTP请求方法
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    
    // 存储服务提供商
    const STORAGE_PROVIDER_LOCAL = 'local';
    const STORAGE_PROVIDER_ALIYUN = 'aliyun';
    const STORAGE_PROVIDER_QINIU = 'qiniu';
    const STORAGE_PROVIDER_TENCENT = 'tencent';
    
    // 存储访问类型
    const STORAGE_ACCESS_PUBLIC = 'public';
    const STORAGE_ACCESS_PRIVATE = 'private';
    
    // 短信服务提供商
    const SMS_PROVIDER_QINIU = 'qiniu';
    const SMS_PROVIDER_ALIYUN = 'aliyun';
    
    // 支付平台
    const PAYMENT_PLATFORM_ALIPAY = 'alipay';
    const PAYMENT_PLATFORM_WECHAT = 'wechat';
    
    // 小程序平台
    const MINIAPP_PLATFORM_WECHAT = 'wechat';
    const MINIAPP_PLATFORM_ALIPAY = 'alipay';
    const MINIAPP_PLATFORM_BYTEDANCE = 'bytedance';
}