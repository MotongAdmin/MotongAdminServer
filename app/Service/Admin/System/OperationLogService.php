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

use App\Constants\LogConstants;
use App\Model\SysOperationLog;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Log\Log;


class OperationLogService extends BaseService
{
    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;


    /**
     * 记录操作日志
     *
     * @param string $module 模块
     * @param string $operation 操作类型
     * @param int $userId 用户ID
     * @param string $username 用户名
     * @param int $level 日志等级
     * @param int $status 操作状态
     * @param string|null $errorMessage 错误信息
     * @param mixed $requestParam 请求参数
     * @param mixed $responseData 响应数据
     * @param int $executionTime 执行时间(毫秒)
     * @return bool
     */
    public function record(
        string $module,
        string $operation,
        int $userId,
        string $username,
        int $level = LogConstants::LOG_LEVEL_NORMAL,
        int $status = LogConstants::OPERATION_STATUS_SUCCESS,
        ?string $errorMessage = null,
        $requestParam = null,
        $responseData = null,
        int $executionTime = 0,
        string $ip = '',
        string $userAgent = ''
    ): bool {
        try {

            // 过滤敏感信息
            if (isset($requestParam['password'])) {
                $requestParam['password'] = '******';
            }

            // 转换为JSON
            if (is_array($requestParam)) {
                $requestParam = json_encode($requestParam, JSON_UNESCAPED_UNICODE);
            }

            // 转换响应数据为JSON
            if (is_array($responseData)) {
                $responseData = json_encode($responseData, JSON_UNESCAPED_UNICODE);
            }

            // 创建日志记录
            SysOperationLog::create([
                'module' => $module,
                'operation' => $operation,
                'method' => 'POST',
                'request_url' => 'ZGW_PROTOCOL',
                'request_param' => $requestParam,
                'response_data' => $responseData,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'user_id' => $userId,
                'username' => $username,
                'level' => $level,
                'status' => $status,
                'error_message' => $errorMessage,
                'execution_time' => $executionTime,
            ]);

            return true;
        } catch (\Throwable $e) {
            // 记录详细的错误日志
            Log::error('操作日志记录失败: ' . $e->getMessage() . json_encode([
                'exception' => $e,
                'module' => $module,
                'operation' => $operation,
                'user_id' => $userId,
                'username' => $username,
            ]));
            return false;
        }
    }

    /**
     * 获取操作日志列表
     *
     * @param array $params 查询参数
     * @return array
     */
    public function getList(array $params): array
    {
        $query = SysOperationLog::query();

        // 用户ID筛选
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        // 用户名筛选
        if (!empty($params['username'])) {
            $query->where('username', 'like', '%' . $params['username'] . '%');
        }

        // 模块筛选
        if (!empty($params['module'])) {
            $query->where('module', $params['module']);
        }

        // 操作类型筛选
        if (!empty($params['operation'])) {
            $query->where('operation', $params['operation']);
        }

        // 日志等级筛选
        if (isset($params['level'])) {
            $query->where('level', $params['level']);
        }

        // 操作状态筛选
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        // IP筛选
        if (!empty($params['ip'])) {
            $query->where('ip', 'like', '%' . $params['ip'] . '%');
        }

        // 时间范围筛选
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetween('created_at', [$params['start_time'], $params['end_time']]);
        }

        // 排序
        $query->orderBy('created_at', 'desc');

        // 分页
        $page = $params['page'] ?? 1;
        $pageSize = $params['size'] ?? 20;

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get();

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    /**
     * 获取操作日志详情
     *
     * @param int $logId 日志ID
     * @return array|null
     */
    public function getDetail(int $logId): ?array
    {
        $log = SysOperationLog::find($logId);
        return $log ? $log->toArray() : null;
    }

    /**
     * 删除操作日志
     *
     * @param array $logIds 日志ID数组
     * @return bool
     */
    public function delete(array $logIds): bool
    {
        try {
            SysOperationLog::whereIn('log_id', $logIds)->delete();

            //记录操作日志
            $this->addOperationLog();

            return true;
        } catch (\Throwable $e) {
            Log::error('删除操作日志失败: ' . $e->getMessage() . json_encode([
                'exception' => $e,
                'log_ids' => $logIds,
            ]));
            return false;
        }
    }

    /**
     * 清空操作日志
     *
     * @return bool
     */
    public function clear(): bool
    {
        try {
            SysOperationLog::query()->delete();

            //记录操作日志
            $this->addOperationLog();

            return true;
        } catch (\Throwable $e) {
            Log::error('清空操作日志失败: ' . $e->getMessage() . json_encode([
                'exception' => $e,
            ]));
            return false;
        }
    }

}