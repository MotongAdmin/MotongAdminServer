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
declare (strict_types=1);
namespace App\Model;

/**
 * @property int $log_id 日志ID
 * @property string $module 操作模块
 * @property string $operation 操作类型
 * @property string $method 请求方式
 * @property string $request_url 请求URL
 * @property string $request_param 请求参数
 * @property string $response_data 响应数据
 * @property string $ip 操作IP
 * @property string $user_agent 用户代理
 * @property int $user_id 操作用户ID
 * @property string $username 操作用户名
 * @property int $level 日志等级：1=普通，2=重要，3=关键
 * @property int $status 操作状态：1=成功，0=失败
 * @property string $error_message 错误消息
 * @property int $execution_time 执行时间(毫秒)
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class SysOperationLog extends Model
{
    protected $table = 'sys_operation_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'module',
        'operation',
        'method',
        'request_url',
        'request_param',
        'response_data',
        'ip',
        'user_agent',
        'user_id',
        'username',
        'level',
        'status',
        'error_message',
        'execution_time',
    ];
}