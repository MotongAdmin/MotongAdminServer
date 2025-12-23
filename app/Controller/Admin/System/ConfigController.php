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
use App\Service\Admin\System\ConfigService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 系统配置控制器
 * @AutoController(prefix="/system/config")
 */
class ConfigController extends AbstractController
{
    /**
     * @Inject
     * @var ConfigService
     */
    protected ConfigService $service;

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
            'exists' => ':attribute不存在',
            'in' => ':attribute的值不在允许范围内',
            'array' => ':attribute必须是数组',
            
            // 字段特定消息
            'config_id.required' => '配置ID不能为空',
            'config_id.integer' => '配置ID必须是整数',
            'config_id.exists' => '配置不存在',
            
            'config_key.required' => '配置键名不能为空',
            'config_key.string' => '配置键名必须是字符串',
            'config_key.max' => '配置键名长度不能超过100位',
            'config_key.unique' => '配置键名已存在',
            
            'config_value.required' => '配置值不能为空',
            'config_value.string' => '配置值必须是字符串',
            
            'config_name.required' => '配置名称不能为空',
            'config_name.string' => '配置名称必须是字符串',
            'config_name.max' => '配置名称长度不能超过100位',
            
            'remark.string' => '备注说明必须是字符串',
            'remark.max' => '备注说明长度不能超过500位',
            
            'is_system.in' => '系统配置标识只能是0或1',
            
            'config_type.string' => '配置类型必须是字符串',
            'config_type.in' => '配置类型只能是text、image、select、file',
            
            'dict_type.string' => '关联字典类型必须是字符串',
            'dict_type.max' => '关联字典类型长度不能超过24位',
            
            'config_keys.required' => '配置键名列表不能为空',
            'config_keys.array' => '配置键名列表必须是数组',
        ];
    }

    /**
     * @Description("获取系统配置列表")
     * ZGW接口名: system.config.getConfigList
     */
    public function getConfigList(AuthedRequest $request)
    {
        $result = $this->service->list();
        return $this->success(['configs' => $result]);
    }

    /**
     * @Description("添加系统配置")
     * ZGW接口名: system.config.createConfig
     */
    public function createConfig(AuthedRequest $request)
    {
        $this->validate([
            'config_key' => 'required|string|max:100|unique:sys_config,config_key',
            'config_value' => 'required|string',
            'config_name' => 'required|string|max:100',
            'remark' => 'string|max:500',
            'is_system' => 'in:0,1',
            'config_type' => 'string|in:text,image,select,file',
            'dict_type' => 'string|max:24'
        ]);
        
        $data = [
            'config_key' => $request->param('config_key'),
            'config_value' => $request->param('config_value'),
            'config_name' => $request->param('config_name'),
            'remark' => $request->param('remark', ''),
            'is_system' => $request->param('is_system', 0),
            'config_type' => $request->param('config_type', 'text'),
            'dict_type' => $request->param('dict_type', '')
        ];

        $configId = $this->service->add($data);
        return $this->success(['config_id' => $configId]);
    }

    /**
     * @Description("更新系统配置")
     * ZGW接口名: system.config.updateConfig
     */
    public function updateConfig(AuthedRequest $request)
    {
        $configId = $request->param('config_id');
        $this->validate([
            'config_id' => 'required|integer|exists:sys_config,id',
            'config_key' => "string|max:100|unique:sys_config,config_key,{$configId},id",
            'config_value' => 'string',
            'config_name' => 'string|max:100',
            'remark' => 'string|max:500',
            'is_system' => 'in:0,1',
            'config_type' => 'string|in:text,image,select,file'
        ]);
        
        $data = array_filter([
            'config_key' => $request->param('config_key'),
            'config_value' => $request->param('config_value'),
            'config_name' => $request->param('config_name'),
            'remark' => $request->param('remark'),
            'is_system' => $request->param('is_system'),
            'config_type' => $request->param('config_type')
        ], function($value) {
            return $value !== null;
        });

        $this->service->update($configId, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除系统配置")
     * ZGW接口名: system.config.deleteConfig
     */
    public function deleteConfig(AuthedRequest $request)
    {
        $this->validate([
            'config_id' => 'required|integer|exists:sys_config,id'
        ]);
        
        $configId = $request->param('config_id');
        $this->service->delete($configId);
        
        return $this->success([]);
    }

    /**
     * @Description("根据键名获取配置值")
     * ZGW接口名: system.config.getConfigByKey
     */
    public function getConfigByKey(AuthedRequest $request)
    {
        $this->validate([
            'config_key' => 'required|string|max:100'
        ]);
        
        $configKey = $request->param('config_key');
        $value = $this->service->getConfigByKey($configKey);
        
        return $this->success(['value' => $value]);
    }

    /**
     * @Description("批量获取配置值")
     * ZGW接口名: system.config.getConfigByKeys
     */
    public function getConfigByKeys(AuthedRequest $request)
    {
        $this->validate([
            'config_keys' => 'required|array'
        ]);
        
        $configKeys = $request->param('config_keys');
        $values = $this->service->getConfigByKeys($configKeys);
        
        return $this->success(['values' => $values]);
    }
} 