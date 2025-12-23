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

use App\Job\CrontabExecuteJob;
use App\Service\Admin\BaseService;
use Hyperf\Crontab\Crontab;
use Hyperf\Utils\ApplicationContext;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Log\Log;
use App\Constants\ErrorCode;

class CrontabService extends BaseService
{
    /**
     * 获取所有定时任务列表
     * 
     * @return array
     */
    public function getAllCrontabs(): array
    {
        $config = config('crontab');
        $crontabs = $config['crontab'] ?? [];
        
        $result = [];
        foreach ($crontabs as $crontab) {
            if ($crontab instanceof Crontab) {
                $result[] = [
                    'name' => $crontab->getName(),
                    'rule' => $crontab->getRule(),
                    'callback' => $this->formatCallback($crontab->getCallback()),
                    'memo' => $crontab->getMemo(),
                    'enable' => $crontab->isEnable(),
                    'singleton' => $crontab->isSingleton(),
                    'onOneServer' => $crontab->isOnOneServer(),
                    'mutexPool' => $crontab->getMutexPool(),
                    'mutexExpires' => $crontab->getMutexExpires(),
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * 格式化回调信息
     * 
     * @param mixed $callback
     * @return string
     */
    private function formatCallback($callback): string
    {
        if (is_array($callback)) {
            if (is_object($callback[0])) {
                return get_class($callback[0]) . '@' . $callback[1];
            }
            return $callback[0] . '::' . $callback[1];
        }
        
        if (is_string($callback)) {
            return $callback;
        }
        
        if (is_callable($callback)) {
            return 'Closure';
        }
        
        return 'Unknown';
    }
    
    /**
     * 根据任务类名主动执行一次定时任务
     * 
     * @param string $className 任务类名
     * @param string $method 方法名，默认为execute
     * @return bool
     * @throws HyperfCommonException
     */
    public function executeTask(string $className, string $method = 'execute'): bool
    {
        try {
            // 检查类是否存在
            if (!class_exists($className)) {
                Log::error("定时任务类 {$className} 不存在");
                throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "定时任务类 {$className} 不存在");
            }
            
            // 获取容器
            $container = ApplicationContext::getContainer();
            
            // 实例化任务类
            $instance = $container->get($className);
            
            // 检查方法是否存在
            if (!method_exists($instance, $method)) {
                Log::error("定时任务类 {$className} 中不存在方法 {$method}");
                throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "定时任务类 {$className} 中不存在方法 {$method}");
            }
            
            // 执行任务
            $instance->{$method}();
            Log::info("成功执行定时任务: {$className}@{$method}");
            
            return true;
        } catch (\Throwable $e) {
            throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "执行定时任务失败: " . $e->getMessage());
        }
    }
    
    /**
     * 异步执行定时任务
     * 
     * @param string $className 任务类名
     * @param string $method 方法名，默认为execute
     * @param string|null $taskName 任务名称（可选，用于日志记录）
     * @return void
     */
    public function asyncExecuteTask(string $className, string $method = 'execute', ?string $taskName = null): void
    {
        // 检查类是否存在
        if (!class_exists($className)) {
            Log::error("定时任务类 {$className} 不存在");
            throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "定时任务类 {$className} 不存在");
        }
        
        // 创建异步任务
        $job = new CrontabExecuteJob($className, $method, $taskName);
        
        // 推送到队列
        $this->driver->push($job);
        
        Log::info("定时任务已推送到异步队列: {$className}@{$method}");
    }
    
    /**
     * 获取单个定时任务详情
     * 
     * @param string $name 任务名称
     * @return array
     * @throws HyperfCommonException
     */
    public function getCrontabByName(string $name): array
    {
        $crontabs = $this->getAllCrontabs();
        
        foreach ($crontabs as $crontab) {
            if ($crontab['name'] === $name) {
                return $crontab;
            }
        }
        
        throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "定时任务 {$name} 不存在");
    }
    
    /**
     * 根据配置文件中的任务名称执行任务
     * 
     * @param string $name 任务名称
     * @return bool
     * @throws HyperfCommonException
     */
    public function executeTaskByName(string $name): bool
    {
        $crontab = $this->getCrontabByName($name);
        
        // 解析回调信息
        $callback = $crontab['callback'];
        $parts = explode('@', $callback);
        
        if (count($parts) !== 2) {
            $parts = explode('::', $callback);
            if (count($parts) !== 2) {
                throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "无法解析任务回调方法");
            }
        }
        
        $className = $parts[0];
        $method = $parts[1];
        
        return $this->executeTask($className, $method);
    }
    
    /**
     * 根据配置文件中的任务名称异步执行任务
     * 
     * @param string $name 任务名称
     * @return void
     * @throws HyperfCommonException
     */
    public function asyncExecuteTaskByName(string $name): void
    {
        $crontab = $this->getCrontabByName($name);
        
        // 解析回调信息
        $callback = $crontab['callback'];
        $parts = explode('@', $callback);
        
        if (count($parts) !== 2) {
            $parts = explode('::', $callback);
            if (count($parts) !== 2) {
                throw new HyperfCommonException(ErrorCode::CRONTAB_ERROR, "无法解析任务回调方法");
            }
        }
        
        $className = $parts[0];
        $method = $parts[1];
        
        $this->asyncExecuteTask($className, $method, $name);
    }
}
