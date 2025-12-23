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

use App\Model\SysDictData;
use App\Model\SysDictType;
use App\Service\Admin\BaseService;
use App\Utils\DictValueFormatter;
use App\Validator\DictDataValidator;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use ZYProSoft\Constants\Constants;

class DictDataService extends BaseService
{
    /**
     * @Inject
     * @var SysDictData
     */
    protected $model;

    /**
     * @Inject
     * @var Redis
     */
    protected $redis;

    /**
     * 获取字典数据列表
     */
    public function getList(array $params = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($params['dict_type'])) {
            $query->where('dict_type', $params['dict_type']);
        }

        if (!empty($params['dict_label'])) {
            $query->where('dict_label', 'like', '%' . $params['dict_label'] . '%');
        }

        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        $total = $query->count();
        $pageSize = $params['size'] ?? 20;
        $list = $query->orderBy('dict_sort', 'asc')
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
     * 获取字典数据详情
     */
    public function getDetail(int $id): array
    {
        $dictData = $this->model->find($id);
        if (!$dictData instanceof SysDictData) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典数据不存在');
        }
        
        $result = $dictData->toArray();
        
        // 应用值类型格式化
        return $this->applyValueTypeFormatting([$result], $dictData->dict_type)[0] ?? $result;
    }

    /**
     * 根据字典类型获取字典数据
     */
    public function getDataByType(string $dictType): array
    {
        // 先从缓存获取
        $cacheKey = 'dict:' . $dictType;
        $cacheData = $this->redis->get($cacheKey);
        
        if ($cacheData) {
            // 缓存中存储的已经是格式化后的数据，直接返回
            return json_decode($cacheData, true);
        }

        // 从数据库获取
        $data = $this->model->where('dict_type', $dictType)
            ->where('status', 1)
            ->orderBy('dict_sort', 'asc')
            ->get();

        if ($data->isEmpty()) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典类型不存在或无数据');
        }

        $result = $data->toArray();
        
        // 应用值类型格式化
        $formattedResult = $this->applyValueTypeFormatting($result, $dictType);

        // 写入缓存（存储格式化后的数据）
        $this->redis->setex($cacheKey, 86400, json_encode($formattedResult));

        return $formattedResult;
    }

    /**
     * 创建字典数据
     */
    public function create(array $data): bool
    {
        // 验证字典值类型
        if (isset($data['dict_value']) && isset($data['dict_type'])) {
            try {
                $data['dict_value'] = DictDataValidator::validateAndFormatValue($data['dict_value'], $data['dict_type']);
            } catch (\InvalidArgumentException $e) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, $e->getMessage());
            }
        }

        $result = $this->model->create($data);
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典数据创建失败');
        }
        
        $this->clearDictCache($data['dict_type']);

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 更新字典数据
     */
    public function update(int $id, array $data): bool
    {
        $dictData = $this->model->find($id);
        if (!$dictData instanceof SysDictData) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典数据不存在');
        }

        // 验证字典值类型
        if (isset($data['dict_value'])) {
            $dictType = $data['dict_type'] ?? $dictData->dict_type;
            try {
                $data['dict_value'] = DictDataValidator::validateAndFormatValue($data['dict_value'], $dictType);
            } catch (\InvalidArgumentException $e) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, $e->getMessage());
            }
        }

        $result = $dictData->update($data);
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典数据更新失败');
        }

        $this->clearDictCache($dictData->dict_type);
        if (isset($data['dict_type']) && $data['dict_type'] !== $dictData->dict_type) {
            $this->clearDictCache($data['dict_type']);
        }

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 删除字典数据
     */
    public function delete(array $ids): bool
    {
        // 获取要删除的字典数据类型
        $dictTypes = $this->model->whereIn('id', $ids)->pluck('dict_type')->unique()->toArray();
        
        $count = $this->model->whereIn('id', $ids)->count();
        if ($count !== count($ids)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '存在无效的字典数据ID');
        }

        $result = $this->model->whereIn('id', $ids)->delete();
        if (!$result) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '字典数据删除失败');
        }

        // 清除相关缓存
        foreach ($dictTypes as $dictType) {
            $this->clearDictCache($dictType);
        }

        //记录操作日志
        $this->addOperationLog();

        return true;
    }

    /**
     * 应用值类型格式化
     */
    protected function applyValueTypeFormatting(array $dictDataList, string $dictType): array
    {
        // 获取字典类型的值类型
        $dictTypeModel = SysDictType::where('dict_type', $dictType)->first();
        if (!$dictTypeModel) {
            return $dictDataList;
        }
        
        $valueType = $dictTypeModel->value_type ?? 1; // 默认为字符串类型
        
        // 如果值类型是字符串，直接返回
        if ($valueType === 1) {
            return $dictDataList;
        }
        
        // 应用值类型格式化
        return DictValueFormatter::formatDictDataList($dictDataList, $valueType);
    }

    /**
     * 清除字典缓存
     */
    protected function clearDictCache(string $dictType): void
    {
        // 同时删除Redis缓存
        $this->redis->del('dict:' . $dictType);
    }
} 