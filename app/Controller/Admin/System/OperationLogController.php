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

use App\Annotation\Description;
use App\Constants\LogConstants;
use ZYProSoft\Http\AuthedRequest;
use App\Service\Admin\System\OperationLogService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use ZYProSoft\Controller\AbstractController;
use ZYProSoft\Log\Log;

/**
 * 操作日志控制器
 * @AutoController(prefix="/system/operationLog")
 */
class OperationLogController extends AbstractController
{
    /**
     * @Inject
     * @var OperationLogService
     */
    protected $operationLogService;
    
    /**
     * 获取操作日志列表
     * @Description("日志列表")
     * ZGW接口名: system.operationLog.list
     */
    public function list(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'module' => 'string|max:50',
            'operation' => 'string|max:100',
            'username' => 'string|max:50',
            'start_time' => 'date',
            'end_time' => 'date'
        ]);
        
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $params = [
            'page' => $page,
            'size' => $size,
            'module' => $request->param('module', ''),
            'operation' => $request->param('operation', ''),
            'username' => $request->param('username', ''),
            'start_time' => $request->param('start_time', ''),
            'end_time' => $request->param('end_time', '')
        ];
        
        $result = $this->operationLogService->getList($params);

        Log::info('操作日志列表:'.json_encode($result));
        
        return $this->success($result);
    }
    
    /**
     * 获取操作日志详情
     * @Description("日志详情")
     * ZGW接口名: system.operationLog.detail
     */
    public function detail(AuthedRequest $request)
    {
        $this->validate([
            'log_id' => 'required|integer|min:1|exists:sys_operation_log,id'
        ]);
        
        $logId = $request->param('log_id');
        $detail = $this->operationLogService->getDetail($logId);
        
        return $this->success($detail);
    }
    
    /**
     * 删除操作日志
     * @Description("删除日志")
     * ZGW接口名: system.operationLog.delete
     */
    public function delete(AuthedRequest $request)
    {
        $this->validate([
            'log_ids' => 'required|array',
            'log_ids.*' => 'integer|exists:sys_operation_log,id'
        ]);
        
        $logIds = $request->param('log_ids');
        
        $result = $this->operationLogService->delete($logIds);
        if (!$result) {
            return $this->response->fail(500, '删除失败');
        }
        
        return $this->success();
    }
    
    /**
     * 清空操作日志
     * @Description("清空日志")
     * ZGW接口名: system.operationLog.clear
     */
    public function clear(AuthedRequest $request)
    {
        return $this->success();
    }
} 