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
use App\Service\Admin\System\DictTypeService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 字典类型管理控制器
 * @AutoController(prefix="/system/dictType")
 */
class DictTypeController extends AbstractController
{
    /**
     * @Inject
     * @var DictTypeService
     */
    protected DictTypeService $service;

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
            'in' => ':attribute的值不在允许范围内',
            
            // 字段特定消息
            'dict_id.required' => '字典ID不能为空',
            'dict_id.integer' => '字典ID必须是整数',
            'dict_id.exists' => '字典类型不存在',
            
            'dict_name.required' => '字典名称不能为空',
            'dict_name.string' => '字典名称必须是字符串',
            'dict_name.max' => '字典名称长度不能超过100位',
            
            'dict_type.required' => '字典类型不能为空',
            'dict_type.string' => '字典类型必须是字符串',
            'dict_type.max' => '字典类型长度不能超过100位',
            'dict_type.unique' => '字典类型已存在',
            
            'value_type.integer' => '值类型必须是整数',
            'value_type.in' => '值类型只能是1-5之间的值',
            
            'status.in' => '状态值只能是0或1',
            
            'is_system.in' => '系统内置标识只能是0或1',
            
            'remark.string' => '备注必须是字符串',
            'remark.max' => '备注长度不能超过500位',
            
            'dict_ids.required' => '字典ID列表不能为空',
            'dict_ids.array' => '字典ID列表必须是数组',
            'dict_ids.*.integer' => '字典ID必须是整数',
            'dict_ids.*.exists' => '字典类型不存在',
            
            'table_name.required' => '表名不能为空',
            'table_name.string' => '表名必须是字符串',
            
            'field_name.required' => '字段名不能为空',
            'field_name.string' => '字段名必须是字符串',
            
            'description.string' => '描述必须是字符串',
            'description.max' => '描述长度不能超过255位',
            
            'bind_id.required' => '绑定ID不能为空',
            'bind_id.integer' => '绑定ID必须是整数',
            'bind_id.exists' => '绑定关系不存在',
        ];
    }

    /**
     * @Description("获取字典类型列表")
     * ZGW接口名: system.dict.type.getDictTypeList
     */
    public function getDictTypeList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'dict_name' => 'string|max:100',
            'dict_type' => 'string|max:100',
            'status' => 'in:0,1'
        ]);
        
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $dictName = $request->param('dict_name', '');
        $dictType = $request->param('dict_type', '');
        $status = $request->param('status');

        $params = [
            'page' => $page,
            'size' => $size,
            'dict_name' => $dictName,
            'dict_type' => $dictType,
            'status' => $status
        ];

        $result = $this->service->getList($params);
        return $this->success($result);
    }

    /**
     * @Description("获取字典类型详情")
     * ZGW接口名: system.dict.type.getDictTypeDetail
     */
    public function getDictTypeDetail(AuthedRequest $request)
    {
        $this->validate([
            'dict_id' => 'required|integer|exists:sys_dict_type,id'
        ]);
        
        $dictId = $request->param('dict_id');
        $result = $this->service->getDetail($dictId);
        return $this->success($result);
    }

    /**
     * @Description("创建字典类型")
     * ZGW接口名: system.dict.type.createDictType
     */
    public function createDictType(AuthedRequest $request)
    {
        $this->validate([
            'dict_name' => 'required|string|max:100',
            'dict_type' => 'required|string|max:100|unique:sys_dict_type,dict_type',
            'value_type' => 'integer|in:1,2,3,4,5',
            'status' => 'in:0,1',
            'is_system' => 'in:0,1',
            'remark' => 'string|max:500'
        ]);
        
        $data = [
            'dict_name' => $request->param('dict_name'),
            'dict_type' => $request->param('dict_type'),
            'value_type' => $request->param('value_type', 1),
            'status' => $request->param('status', 0),
            'is_system' => $request->param('is_system', 0),
            'remark' => $request->param('remark', '')
        ];

        $this->service->create($data);
        return $this->success([]);
    }

    /**
     * @Description("更新字典类型")
     * ZGW接口名: system.dict.type.updateDictType
     */
    public function updateDictType(AuthedRequest $request)
    {
        $dictId = $request->param('dict_id');
        $this->validate([
            'dict_id' => 'required|integer|exists:sys_dict_type,id',
            'dict_name' => 'string|max:100',
            'dict_type' => "string|max:100|unique:sys_dict_type,dict_type,{$dictId},id",
            'value_type' => 'integer|in:1,2,3,4,5',
            'status' => 'in:0,1',
            'remark' => 'string|max:500'
        ]);
        
        $data = array_filter([
            'dict_name' => $request->param('dict_name'),
            'dict_type' => $request->param('dict_type'),
            'value_type' => $request->param('value_type'),
            'status' => $request->param('status'),
            'remark' => $request->param('remark')
        ], function($value) {
            return $value !== null;
        });

        $this->service->update($dictId, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除字典类型")
     * ZGW接口名: system.dict.type.deleteDictType
     */
    public function deleteDictType(AuthedRequest $request)
    {
        $this->validate([
            'dict_ids' => 'required|array',
            'dict_ids.*' => 'integer|exists:sys_dict_type,id'
        ]);
        
        $dictIds = $request->param('dict_ids');
        $this->service->delete($dictIds);
        return $this->success([]);
    }

    /**
     * @Description("获取数据库所有表")
     * ZGW接口名: system.dict.type.getAllTables
     */
    public function getAllTables(AuthedRequest $request)
    {
        $tables = $this->service->getAllTables();
        return $this->success($tables);
    }

    /**
     * @Description("获取表的所有字段")
     * ZGW接口名: system.dict.type.getTableFields
     */
    public function getTableFields(AuthedRequest $request)
    {
        $this->validate([
            'table_name' => 'required|string'
        ]);
        
        $tableName = $request->param('table_name');
        $fields = $this->service->getTableFields($tableName);
        return $this->success($fields);
    }

    /**
     * @Description("获取字典类型绑定的字段列表")
     * ZGW接口名: system.dict.type.getBindFields
     */
    public function getBindFields(AuthedRequest $request)
    {
        $this->validate([
            'dict_type' => 'required|string'
        ]);
        
        $dictType = $request->param('dict_type');
        $bindings = $this->service->getBindFields($dictType);
        return $this->success($bindings);
    }

    /**
     * @Description("绑定字段到字典类型")
     * ZGW接口名: system.dict.type.bindField
     */
    public function bindField(AuthedRequest $request)
    {
        $this->validate([
            'dict_type' => 'required|string',
            'table_name' => 'required|string',
            'field_name' => 'required|string',
            'description' => 'string|max:255'
        ]);
        
        $dictType = $request->param('dict_type');
        $tableName = $request->param('table_name');
        $fieldName = $request->param('field_name');
        $description = $request->param('description', '');
        
        $this->service->bindField($dictType, $tableName, $fieldName, $description);
        return $this->success([]);
    }

    /**
     * @Description("解绑字段与字典类型")
     * ZGW接口名: system.dictType.unbindField
     */
    public function unbindField(AuthedRequest $request)
    {
        $this->validate([
            'bind_id' => 'required|integer|exists:sys_field_dict,id'
        ]);
        
        $bindId = $request->param('bind_id');
        $this->service->unbindField($bindId);
        return $this->success([]);
    }
    
    /**
     * @Description("根据表名和字段名获取字典类型")
     * ZGW接口名: system.dictType.getFieldDict
     */
    public function getFieldDict(AuthedRequest $request)
    {
        $this->validate([
            'table_name' => 'required|string',
            'field_name' => 'required|string'
        ]);
        
        $tableName = $request->param('table_name');
        $fieldName = $request->param('field_name');
        
        $result = $this->service->getFieldDict($tableName, $fieldName);
        return $this->success($result);
    }

    /**
     * @Description("获取所有字典类型")
     * ZGW接口名: system.dictType.getAllDictTypes
     */
    public function getAllDictTypes(AuthedRequest $request)
    {
        $result = $this->service->getAllDictTypes();
        return $this->success($result);
    }
} 