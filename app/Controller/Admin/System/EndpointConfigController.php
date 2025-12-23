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
use App\Service\Admin\System\EndpointConfigService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 端点配置管理控制器
 * @AutoController(prefix="/system/endpointConfig")
 */
class EndpointConfigController extends AbstractController
{
    /**
     * @Inject
     * @var EndpointConfigService
     */
    protected EndpointConfigService $service;

    /**
     * 自定义验证错误消息
     * @return array
     */
    public function messages()
    {
        return [
            // 通用规则消息
            'required' => ':attribute不能为空',
            'string' => ':attribute必须是字符串',
            'integer' => ':attribute必须是整数',
            'max' => ':attribute长度不能超过:max位',
            'unique' => ':attribute已存在',
            'array' => ':attribute必须是数组',
            'nullable' => ':attribute可以为空',
            
            // 字段特定消息
            'id.required' => '配置ID不能为空',
            'id.integer' => '配置ID必须是整数',
            'id.exists' => '端点配置不存在',
            
            'business_key.required' => '业务标识不能为空',
            'business_key.string' => '业务标识必须是字符串',
            'business_key.max' => '业务标识长度不能超过50位',
            'business_key.unique' => '业务标识已存在',
            
            'name.required' => '配置名称不能为空',
            'name.string' => '配置名称必须是字符串',
            'name.max' => '配置名称长度不能超过100位',
            
            'endpoint_url.required' => '服务地址不能为空',
            'endpoint_url.string' => '服务地址必须是字符串',
            'endpoint_url.max' => '服务地址长度不能超过255位',
            
            'request_method.required' => '请求方式不能为空',
            'request_method.string' => '请求方式必须是字符串',
            'request_method.max' => '请求方式长度不能超过10位',
            
            'timeout.integer' => '超时时间必须是整数',
            
            'headers.array' => '请求头配置必须是数组',
            
            'auth_config.array' => '鉴权信息必须是数组',
            
            'extra_config.array' => '额外配置必须是数组',
        ];
    }

    /**
     * @Description("获取端点配置列表")
     * ZGW接口名: system.endpointConfig.getList
     */
    public function getList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'name' => 'string|max:100',
            'business_key' => 'string|max:50',
            'request_method' => 'string|max:10'
        ]);
        
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $name = $request->param('name', '');
        $businessKey = $request->param('business_key', '');
        $requestMethod = $request->param('request_method', '');

        $params = [
            'page' => $page,
            'size' => $size,
            'name' => $name,
            'business_key' => $businessKey,
            'request_method' => $requestMethod
        ];

        $result = $this->service->getList($params);
        return $this->success($result);
    }

    /**
     * @Description("获取端点配置详情")
     * ZGW接口名: system.endpointConfig.getDetail
     */
    public function getDetail(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer|exists:sys_endpoint_config,id'
        ]);
        
        $id = $request->param('id');
        $result = $this->service->getDetail($id);
        return $this->success($result);
    }

    /**
     * @Description("创建端点配置")
     * ZGW接口名: system.endpointConfig.create
     */
    public function create(AuthedRequest $request)
    {
        $this->validate([
            'business_key' => 'required|string|max:50|unique:sys_endpoint_config,business_key',
            'name' => 'required|string|max:100',
            'endpoint_url' => 'required|string|max:255',
            'request_method' => 'required|string|max:10',
            'timeout' => 'integer',
            'headers' => 'nullable|array',
            'auth_config' => 'nullable|array',
            'extra_config' => 'nullable|array'
        ]);
        
        $data = $request->getParams();
        $id = $this->service->create($data);
        return $this->success(['id' => $id]);
    }

    /**
     * @Description("更新端点配置")
     * ZGW接口名: system.endpointConfig.update
     */
    public function update(AuthedRequest $request)
    {
        $id = $request->param('id');
        $this->validate([
            'id' => 'required|integer|exists:sys_endpoint_config,id',
            'business_key' => "string|max:50|unique:sys_endpoint_config,business_key,{$id},id",
            'name' => 'string|max:100',
            'endpoint_url' => 'string|max:255',
            'request_method' => 'string|max:10',
            'timeout' => 'integer',
            'headers' => 'nullable|array',
            'auth_config' => 'nullable|array',
            'extra_config' => 'nullable|array'
        ]);
        
        $data = $request->getParams();
        unset($data['id']);
        
        $this->service->update($id, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除端点配置")
     * ZGW接口名: system.endpointConfig.delete
     */
    public function delete(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer|exists:sys_endpoint_config,id'
        ]);
        
        $id = $request->param('id');
        $this->service->delete($id);
        return $this->success([]);
    }
} 