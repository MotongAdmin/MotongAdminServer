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
use App\Service\Admin\System\SmsConfigService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 短信配置管理控制器
 * @AutoController(prefix="/system/smsConfig")
 */
class SmsConfigController extends AbstractController
{
    /**
     * @Inject
     * @var SmsConfigService
     */
    protected SmsConfigService $service;

    /**
     * @Description("获取短信配置列表")
     * ZGW接口名: system.smsConfig.getList
     */
    public function getList(AuthedRequest $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $name = $request->param('name', '');
        $provider = $request->param('provider', '');

        $params = [
            'page' => $page,
            'size' => $size,
            'name' => $name,
            'provider' => $provider
        ];

        $result = $this->service->getList($params);
        return $this->success($result);
    }

    /**
     * @Description("获取短信配置详情")
     * ZGW接口名: system.smsConfig.getDetail
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
     * @Description("创建短信配置")
     * ZGW接口名: system.smsConfig.create
     */
    public function create(AuthedRequest $request)
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'provider' => 'required|string|max:20',
            'access_key' => 'required|string|max:100',
            'secret_key' => 'required|string|max:100',
            'sign_name' => 'required|string|max:50',
            'extra_config' => 'nullable|array',
            'template_map' => 'nullable|array'
        ]);
        
        $data = $request->getParams();
        $id = $this->service->create($data);
        return $this->success(['id' => $id]);
    }

    /**
     * @Description("更新短信配置")
     * ZGW接口名: system.smsConfig.update
     */
    public function update(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer',
            'name' => 'string|max:100',
            'provider' => 'string|max:20',
            'access_key' => 'string|max:100',
            'sign_name' => 'string|max:50',
            'extra_config' => 'nullable|array',
            'template_map' => 'nullable|array'
        ]);
        
        $id = $request->param('id');
        $data = $request->getParams();
        unset($data['id']);
        
        $this->service->update($id, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除短信配置")
     * ZGW接口名: system.smsConfig.delete
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