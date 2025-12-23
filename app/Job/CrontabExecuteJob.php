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

use App\Constants\ErrorCode;
use Hyperf\AsyncQueue\Job;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Log\Log;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 定时任务异步执行任务
 */
class CrontabExecuteJob extends Job
{
    /**
     * 任务类名
     * @var string
     */
    protected $className;

    /**
     * 执行方法名
     * @var string
     */
    protected $method;

    /**
     * 任务名称（可选，用于日志记录）
     * @var string|null
     */
    protected $taskName;

    /**
     * 构造函数
     *
     * @param string $className 任务类名
     * @param string $method 执行方法名
     * @param string|null $taskName 任务名称（可选）
     */
    public function __construct(string $className, string $method = 'execute', ?string $taskName = null)
    {
        $this->className = $className;
        $this->method = $method;
        $this->taskName = $taskName;
    }

    /**
     * 执行任务
     */
    public function handle(): void
    {
        try {
            Log::info("开始异步执行定时任务".json_encode([
                'class' => $this->className,
                'method' => $this->method,
                'taskName' => $this->taskName
            ]));

            // 检查类是否存在
            if (!class_exists($this->className)) {
                Log::error("定时任务类 {$this->className} 不存在");
                return;
            }
            
            // 获取容器
            $container = ApplicationContext::getContainer();
            
            // 实例化任务类
            $instance = $container->get($this->className);
            
            // 检查方法是否存在
            if (!method_exists($instance, $this->method)) {
                Log::error("定时任务类 {$this->className} 中不存在方法 {$this->method}");
                return;
            }
            
            // 执行任务
            $startTime = microtime(true);
            $instance->{$this->method}();
            $executionTime = microtime(true) - $startTime;
            
        } catch (\Throwable $e) {

        }
    }
} 