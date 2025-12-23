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

class LogConstants
{
    // 操作日志等级
    const LOG_LEVEL_NORMAL = 1;    // 普通
    const LOG_LEVEL_IMPORTANT = 2; // 重要
    const LOG_LEVEL_CRITICAL = 3;  // 关键
    
    // 操作状态
    const OPERATION_STATUS_SUCCESS = 1; // 成功
    const OPERATION_STATUS_FAIL = 0;    // 失败
    
    // 操作类型
    const OPERATION_TYPE_LOGIN = 'LOGIN';           // 登录
    const OPERATION_TYPE_LOGOUT = 'LOGOUT';         // 登出
    const OPERATION_TYPE_INSERT = 'INSERT';         // 新增
    const OPERATION_TYPE_DELETE = 'DELETE';         // 删除
    const OPERATION_TYPE_UPDATE = 'UPDATE';         // 修改
    const OPERATION_TYPE_QUERY = 'QUERY';           // 查询
    const OPERATION_TYPE_GRANT = 'GRANT';           // 授权
    const OPERATION_TYPE_EXPORT = 'EXPORT';         // 导出
    const OPERATION_TYPE_IMPORT = 'IMPORT';         // 导入
    const OPERATION_TYPE_FORCE_LOGOUT = 'FORCE_LOGOUT'; // 强制登出
    const OPERATION_TYPE_OTHER = 'OTHER';           // 其他
    
    // 模块名称
    const MODULE_USER = 'USER';       // 用户模块
    const MODULE_ROLE = 'ROLE';       // 角色模块
    const MODULE_MENU = 'MENU';       // 菜单模块
    const MODULE_API = 'API';         // 接口模块
    const MODULE_SYSTEM = 'SYSTEM';   // 系统模块
    const MODULE_CONFIG = 'CONFIG';   // 配置模块
    const MODULE_DICT = 'DICT';       // 字典模块
    const MODULE_OTHER = 'OTHER';     // 其他模块
} 