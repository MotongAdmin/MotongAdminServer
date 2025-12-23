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

use App\Model\SysDictType;
use App\Model\SysFieldDict;
use App\Service\Admin\BaseService;
use Hyperf\Di\Annotation\Inject;
use App\Constants\ErrorCode;
use Hyperf\DbConnection\Db;
use ZYProSoft\Exception\HyperfCommonException;

class DictTypeService extends BaseService
{
    /**
     * @Inject
     * @var SysDictType
     */
    protected $model;

    /**
     * 获取字典类型列表
     */
    public function getList(array $params = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($params['dict_name'])) {
            $query->where('dict_name', 'like', '%' . $params['dict_name'] . '%');
        }

        if (!empty($params['dict_type'])) {
            $query->where('dict_type', 'like', '%' . $params['dict_type'] . '%');
        }

        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        $total = $query->count();
        $pageSize = $params['size'] ?? 20;
        $list = $query->orderBy('created_at', 'desc')
            ->offset(($params['page'] - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $params['page'],
            'size' => $pageSize
        ];
    }

    /**
     * 获取字典类型详情
     */
    public function getDetail(int $id): array
    {
        $dictType = $this->model->find($id);
        if (!$dictType) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型不存在');
        }
        return $dictType->toArray();
    }

    /**
     * 创建字典类型
     */
    public function create(array $data): bool
    {
        // 检查字典类型是否唯一
        if (!$this->checkDictTypeUnique($data['dict_type'])) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型已存在');
        }

        $result = $this->model->create($data);
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型创建失败');
        }

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 更新字典类型
     */
    public function update(int $id, array $data): bool
    {
        $dictType = $this->model->find($id);
        if (!$dictType) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型不存在');
        }

        // 检查字典类型是否唯一
        if (isset($data['dict_type']) && !$this->checkDictTypeUnique($data['dict_type'], $id)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型已存在');
        }

        $result = $dictType->update($data);
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型更新失败');
        }

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 删除字典类型
     */
    public function delete(array $ids): bool
    {
        $count = $this->model->whereIn('id', $ids)->count();
        if ($count !== count($ids)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '存在无效的字典类型ID');
        }

        // 检查是否包含系统内置字典类型
        $systemCount = $this->model->whereIn('id', $ids)->where('is_system', 1)->count();
        if ($systemCount > 0) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '无法删除系统内置字典类型');
        }

        $result = $this->model->whereIn('id', $ids)->delete();
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型删除失败');
        }

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 检查字典类型是否唯一
     */
    public function checkDictTypeUnique(string $dictType, ?int $id = null): bool
    {
        $query = $this->model->where('dict_type', $dictType);
        
        if ($id !== null) {
            $query->where('id', '!=', $id);
        }

        return !$query->exists();
    }
    
    /**
     * 获取数据库所有表信息（排除migrations表）
     */
    public function getAllTables(): array
    {
        $connection = Db::connection();
        
        // 获取所有表
        $tables = $connection->select('SHOW TABLES');
        
        $result = [];
        foreach ($tables as $table) {
            $tableName = reset($table); // 获取第一个元素的值
            
            // 排除migrations表
            if ($tableName !== 'migrations') {
                $result[] = $tableName;
            }
        }
        
        return $result;
    }

    /**
     * 获取指定表的所有字段信息
     */
    public function getTableFields(string $tableName): array
    {
        $connection = Db::connection();
        
        // 获取表的所有字段
        $columns = $connection->select("SHOW FULL COLUMNS FROM `{$tableName}`");
        
        $result = [];
        foreach ($columns as $column) {
            $result[] = [
                'name' => $column->Field,
                'type' => $column->Type,
                'comment' => $column->Comment
            ];
        }
        
        return $result;
    }
    
    /**
     * 绑定字段到字典类型
     */
    public function bindField(string $dictType, string $tableName, string $fieldName, ?string $description = null): bool
    {
        // 检查字典类型是否存在
        $dictTypeExists = $this->model->where('dict_type', $dictType)->exists();
        if (!$dictTypeExists) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型不存在');
        }
        
        // 检查是否已存在绑定关系
        $exists = SysFieldDict::query()
            ->where('dict_type', $dictType)
            ->where('table_name', $tableName)
            ->where('field_name', $fieldName)
            ->exists();
            
        if ($exists) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '该字段已绑定到此字典类型');
        }
        
        // 创建绑定关系
        $result = SysFieldDict::create([
            'table_name' => $tableName,
            'field_name' => $fieldName,
            'dict_type' => $dictType,
            'description' => $description
        ]);
        
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字段绑定失败');
        }
        
        // 记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 解绑字段与字典类型
     */
    public function unbindField(int $bindId): bool
    {
        $binding = SysFieldDict::find($bindId);
        if (!$binding) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '绑定关系不存在');
        }
        
        $result = $binding->delete();
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '解绑失败');
        }
        
        // 记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 获取字典类型的绑定字段列表
     */
    public function getBindFields(string $dictType): array
    {
        $bindings = SysFieldDict::query()
            ->where('dict_type', $dictType)
            ->get();
            
        return $bindings->toArray();
    }
    
    /**
     * 根据表名和字段名获取字典类型
     */
    public function getFieldDict(string $tableName, string $fieldName): ?array
    {
        $fieldDict = SysFieldDict::query()
            ->where('table_name', $tableName)
            ->where('field_name', $fieldName)
            ->first();
            
        return $fieldDict ? $fieldDict->toArray() : null;
    }

    /**
     * 获取所有字典类型
     * @return array
     */
    public function getAllDictTypes(): array
    {
        return $this->model->all()->toArray();
    }
} 