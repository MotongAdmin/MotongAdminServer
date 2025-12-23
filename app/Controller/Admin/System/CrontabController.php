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

namespace App\Controller\Admin\System;

use ZYProSoft\Http\AuthedRequest;
use App\Service\Admin\System\CrontabService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 定时任务控制器
 * @AutoController(prefix="/system/crontab")
 */
class CrontabController extends AbstractController
{
    /**
     * @Inject
     * @var CrontabService
     */
    protected CrontabService $service;

    /**
     * @Description("获取所有定时任务列表")
     * ZGW接口名: system.crontab.getAllCrontabs
     */
    public function getAllCrontabs(AuthedRequest $request)
    {
        $result = $this->service->getAllCrontabs();
        return $this->success(['crontabs' => $result]);
    }
    
    /**
     * @Description("执行一次定时任务")
     * ZGW接口名: system.crontab.executeTask
     */
    public function executeTask(AuthedRequest $request)
    {
        $this->validate([
            'class_name' => 'required|string',
            'method' => 'string|max:100'
        ]);
        
        $className = $request->param('class_name');
        $method = $request->param('method', 'execute');
        
        $this->service->asyncExecuteTask($className, $method);
        
        return $this->success([]);
    }
    
    /**
     * @Description("根据任务名称执行任务")
     * ZGW接口名: system.crontab.executeTaskByName
     */
    public function executeTaskByName(AuthedRequest $request)
    {
        $this->validate([
            'name' => 'required|string|max:100'
        ]);
        
        $name = $request->param('name');
        $this->service->asyncExecuteTaskByName($name);
        
        return $this->success([]);
    }
}
