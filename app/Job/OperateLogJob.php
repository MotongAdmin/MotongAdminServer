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
declare(strict_types=1);

namespace App\Job;

use App\Constants\LogConstants;
use Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use App\Service\Admin\System\OperationLogService;
use ZYProSoft\Log\Log;

/**
 * 操作日志异步任务
 */
class OperateLogJob extends Job
{
    /**
     * 模块名称
     * @var string
     */
    protected $module;

    /**
     * 操作类型
     * @var string
     */
    protected $operation;

    /**
     * 用户ID
     * @var int
     */
    protected $userId;

    /**
     * 用户名
     * @var string
     */
    protected $username;

    /**
     * 日志等级
     * @var int
     */
    protected $level;

    /**
     * 操作状态
     * @var int
     */
    protected $status;

    /**
     * 错误信息
     * @var string|null
     */
    protected $errorMessage;

    /**
     * 请求参数
     * @var mixed
     */
    protected $requestParam;

    /**
     * 响应数据
     * @var mixed
     */
    protected $responseData;

    /**
     * 执行时间(毫秒)
     * @var int
     */
    protected $executionTime;

    /**
     * IP地址
     * @var string
     */
    protected $ip;

    /**
     * User-Agent
     * @var string
     */
    protected $userAgent;

    /**
     * 构造函数
     *
     * @param string $module 模块名称
     * @param string $operation 操作类型
     * @param int $userId 用户ID
     * @param string $username 用户名
     * @param array $requestParam 请求参数
     * @param int $level 日志等级
     * @param string $ip IP地址
     */
    public function __construct(
        string $module,
        string $operation,
        int $userId,
        string $username,
        $requestParam = [],
        int $level = LogConstants::LOG_LEVEL_NORMAL,
        string $ip = ''
    ) {
        $this->module = $module;
        $this->operation = $operation;
        $this->userId = $userId;
        $this->username = $username;
        $this->level = $level;
        $this->status = 1;
        $this->errorMessage = '0k';
        $this->requestParam = $requestParam;
        $this->responseData = [];
        $this->executionTime = 0;
        $this->ip = $ip;
        $this->userAgent = '';
        $this->maxAttempts = 3;
    }

    /**
     * 执行任务
     */
    public function handle(): void
    {
        try {

            Log::info('开始记录操作日志:'.json_encode([
                'module' => $this->module,
                'operation' => $this->operation,
                'userId' => $this->userId,
                'username' => $this->username,
                'requestParam' => $this->requestParam,
            ]));

            // 获取容器
            $container = ApplicationContext::getContainer();
            
            // 获取日志记录器
            $logService = $container->get(OperationLogService::class);
            $logService->record($this->module, $this->operation, $this->userId, $this->username, $this->level, $this->status, $this->errorMessage, $this->requestParam, $this->responseData, $this->executionTime, $this->ip, $this->userAgent);
            
        } catch (\Throwable $e) {
            Log::error('操作日志记录失败: ' . $e->getMessage().json_encode([
                'exception' => $e,
                'module' => $this->module,
                'operation' => $this->operation,
                'user_id' => $this->userId,
                'username' => $this->username,
            ]));
        }
    }
}
