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
use App\Service\Admin\System\PaymentConfigService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 支付配置管理控制器
 * @AutoController(prefix="/system/paymentConfig")
 */
class PaymentConfigController extends AbstractController
{
    /**
     * @Inject
     * @var PaymentConfigService
     */
    protected PaymentConfigService $service;

    /**
     * @Description("获取支付配置列表")
     * ZGW接口名: system.paymentConfig.getList
     */
    public function getList(AuthedRequest $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $name = $request->param('name', '');
        $platform = $request->param('platform', '');

        $params = [
            'page' => $page,
            'size' => $size,
            'name' => $name,
            'platform' => $platform
        ];

        $result = $this->service->getList($params);
        return $this->success($result);
    }

    /**
     * @Description("获取支付配置详情")
     * ZGW接口名: system.paymentConfig.getDetail
     */
    public function getDetail(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer'
        ]);
        
        $id = $request->param('id');
        $result = $this->service->getDetail($id);
        return $this->success($result);
    }

    /**
     * @Description("创建支付配置")
     * ZGW接口名: system.paymentConfig.create
     */
    public function create(AuthedRequest $request)
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'platform' => 'required|string|max:20',
            'mch_id' => 'required|string|max:50',
            'pay_key' => 'required|string|max:100',
            'cert_path' => 'nullable|string|max:255',
            'pay_notify_url' => 'nullable|string|max:255',
            'refund_notify_url' => 'nullable|string|max:255',
            'extra_config' => 'nullable|array'
        ]);
        
        $data = $request->getParams();
        $id = $this->service->create($data);
        return $this->success(['id' => $id]);
    }

    /**
     * @Description("更新支付配置")
     * ZGW接口名: system.paymentConfig.update
     */
    public function update(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer',
            'name' => 'string|max:100',
            'platform' => 'string|max:20',
            'mch_id' => 'string|max:50',
            'cert_path' => 'nullable|string|max:255',
            'pay_notify_url' => 'nullable|string|max:255',
            'refund_notify_url' => 'nullable|string|max:255',
            'extra_config' => 'nullable|array'
        ]);
        
        $id = $request->param('id');
        $data = $request->getParams();
        unset($data['id']);
        
        $this->service->update($id, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除支付配置")
     * ZGW接口名: system.paymentConfig.delete
     */
    public function delete(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer'
        ]);
        
        $id = $request->param('id');
        $this->service->delete($id);
        return $this->success([]);
    }
} 