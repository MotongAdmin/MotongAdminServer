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
use App\Service\Admin\System\DictDataService;
use App\Utils\DictValueFormatter;
use App\Validator\DictDataValidator;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 字典数据管理控制器
 * @AutoController(prefix="/system/dictData")
 */
class DictDataController extends AbstractController
{
    /**
     * @Inject
     * @var DictDataService
     */
    protected DictDataService $service;

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
            'numeric' => ':attribute必须是数字',
            'json' => ':attribute必须是有效的JSON格式',
            'max' => ':attribute长度不能超过:max位',
            'min' => ':attribute的值不能小于:min',
            'in' => ':attribute的值不在允许范围内',
            'nullable' => ':attribute可以为空',
            
            // 字段特定消息
            'dict_code.required' => '字典编码不能为空',
            'dict_code.integer' => '字典编码必须是整数',
            
            'dict_type.required' => '字典类型不能为空',
            'dict_type.string' => '字典类型必须是字符串',
            'dict_type.max' => '字典类型长度不能超过100位',
            
            'dict_label.required' => '字典标签不能为空',
            'dict_label.string' => '字典标签必须是字符串',
            'dict_label.max' => '字典标签长度不能超过100位',
            
            'dict_value.required' => '字典键值不能为空',
            'dict_value.string' => '字典键值必须是字符串',
            'dict_value.integer' => '字典键值必须是整数',
            'dict_value.numeric' => '字典键值必须是数字',
            'dict_value.json' => '字典键值必须是有效的JSON格式',
            'dict_value.max' => '字典键值长度不能超过100位',
            
            'dict_sort.integer' => '字典排序必须是整数',
            'dict_sort.min' => '字典排序不能小于0',
            
            'status.in' => '状态只能是0或1',
            
            'css_class.string' => '样式属性必须是字符串',
            'css_class.max' => '样式属性长度不能超过100位',
            
            'list_class.string' => '表格回显样式必须是字符串',
            'list_class.max' => '表格回显样式长度不能超过100位',
            
            'remark.string' => '备注必须是字符串',
            'remark.max' => '备注长度不能超过500位',
            
            'dict_codes.required' => '字典编码列表不能为空',
            'dict_codes.array' => '字典编码列表必须是数组',
            'dict_codes.*.integer' => '字典编码必须是整数',
            'dict_codes.*.exists' => '字典数据不存在',
        ];
    }

    /**
     * 获取动态验证规则
     * @param string $dictType
     * @return array
     */
    protected function getDynamicValidationRules(string $dictType): array
    {
        $baseRules = [
            'dict_type' => 'required|string|max:100|exists:sys_dict_type,dict_type',
            'dict_label' => 'required|string|max:100',
            'dict_sort' => 'integer|min:0',
            'status' => 'in:0,1',
            'css_class' => 'string|max:100|nullable',
            'list_class' => 'string|max:100|nullable',
            'remark' => 'string|max:500|nullable'
        ];

        // 根据字典类型的值类型设置dict_value的验证规则
        $valueRule = DictDataValidator::getValueTypeValidationRule($dictType);
        $baseRules['dict_value'] = 'required|' . $valueRule;

        return $baseRules;
    }

    /**
     * @Description("获取字典数据列表")
     * ZGW接口名: system.dict.data.getDictDataList
     */
    public function getDictDataList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'dict_type' => 'string|max:100',
            'dict_label' => 'string|max:100',
            'status' => 'in:0,1'
        ]);
        
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $dictType = $request->param('dict_type', '');
        $dictLabel = $request->param('dict_label', '');
        $status = $request->param('status');

        $params = [
            'page' => $page,
            'size' => $size,
            'dict_type' => $dictType,
            'dict_label' => $dictLabel,
            'status' => $status
        ];

        $result = $this->service->getList($params);
        return $this->success($result);
    }

    /**
     * @Description("获取字典数据详情")
     * ZGW接口名: system.dict.data.getDictDataDetail
     */
    public function getDictDataDetail(AuthedRequest $request)
    {
        $this->validate([
            'dict_code' => 'required|integer'
        ]);
        
        $dictCode = $request->param('dict_code');
        $result = $this->service->getDetail($dictCode);
        return $this->success($result);
    }

    /**
     * @Description("根据字典类型获取字典数据")
     * ZGW接口名: system.dict.data.getDictDataByType
     */
    public function getDictDataByType(AuthedRequest $request)
    {
        $this->validate([
            'dict_type' => 'required|string|max:100'
        ]);
        
        $dictType = $request->param('dict_type');
        $result = $this->service->getDataByType($dictType);
        return $this->success($result);
    }

    /**
     * @Description("创建字典数据")
     * ZGW接口名: system.dict.data.createDictData
     */
    public function createDictData(AuthedRequest $request)
    {
        $dictType = $request->param('dict_type');
        
        // 使用动态验证规则
        $this->validate($this->getDynamicValidationRules($dictType));
        
        $data = [
            'dict_type' => $request->param('dict_type'),
            'dict_label' => $request->param('dict_label'),
            'dict_value' => $request->param('dict_value'),
            'dict_sort' => $request->param('dict_sort', 0),
            'status' => $request->param('status', 0),
            'css_class' => $request->param('css_class', ''),
            'list_class' => $request->param('list_class', ''),
            'remark' => $request->param('remark', '')
        ];

        $this->service->create($data);
        return $this->success([]);
    }

    /**
     * @Description("更新字典数据")
     * ZGW接口名: system.dict.data.updateDictData
     */
    public function updateDictData(AuthedRequest $request)
    {
        $dictCode = $request->param('dict_code');
        $dictType = $request->param('dict_type');
        
        // 如果没有提供dict_type，从现有数据中获取
        if (!$dictType) {
            $existingData = $this->service->getDetail($dictCode);
            $dictType = $existingData['dict_type'] ?? '';
        }
        
        // 基础验证规则
        $baseRules = [
            'dict_code' => 'required|integer',
            'dict_type' => 'string|max:100|exists:sys_dict_type,dict_type',
            'dict_label' => 'string|max:100',
            'dict_sort' => 'integer|min:0',
            'status' => 'in:0,1',
            'css_class' => 'string|max:100|nullable',
            'list_class' => 'string|max:100|nullable',
            'remark' => 'string|max:500|nullable'
        ];
        
        // 如果提供了dict_value，添加值类型验证
        if ($request->has('dict_value')) {
            $valueRule = DictDataValidator::getValueTypeValidationRule($dictType);
            $baseRules['dict_value'] = 'string|' . $valueRule;
        }
        
        $this->validate($baseRules);
        
        $data = array_filter([
            'dict_type' => $request->param('dict_type'),
            'dict_label' => $request->param('dict_label'),
            'dict_value' => $request->param('dict_value'),
            'dict_sort' => $request->param('dict_sort'),
            'status' => $request->param('status'),
            'css_class' => $request->param('css_class'),
            'list_class' => $request->param('list_class'),
            'remark' => $request->param('remark')
        ], function($value) {
            return $value !== null;
        });

        $this->service->update($dictCode, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除字典数据")
     * ZGW接口名: system.dict.data.deleteDictData
     */
    public function deleteDictData(AuthedRequest $request)
    {
        $this->validate([
            'dict_codes' => 'required|array',
            'dict_codes.*' => 'integer|exists:sys_dict_data,id'
        ]);
        
        $dictCodes = $request->param('dict_codes');
        $this->service->delete($dictCodes);
        return $this->success([]);
    }
} 