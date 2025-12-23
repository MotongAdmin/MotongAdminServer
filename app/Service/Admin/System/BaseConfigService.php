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

use App\Constants\ErrorCode;
use App\Model\Model;
use App\Service\Base\ConfigService;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 基础配置服务类
 */
abstract class BaseConfigService
{
    /**
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * 构造函数
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * 获取模型类
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * 获取配置类型标识
     * 用于缓存清理时的标识
     * @return string
     */
    abstract protected function getConfigType(): string;

    /**
     * 获取列表
     * @param array $params
     * @return array
     */
    public function getList(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $size = (int)($params['size'] ?? 20);
        $name = $params['name'] ?? '';

        $query = $this->buildQuery($params);

        if (!empty($name)) {
            $query->where('name', 'like', "%{$name}%");
        }

        $total = $query->count();
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();

        return [
            'list' => $this->formatList($list),
            'total' => $total,
            'page' => $page,
            'size' => $size
        ];
    }

    /**
     * 构建查询
     * @param array $params
     * @return Builder
     */
    protected function buildQuery(array $params): Builder
    {
        $modelClass = $this->getModelClass();
        return $modelClass::query();
    }

    /**
     * 格式化列表数据
     * @param Collection $list
     * @return array
     */
    protected function formatList(Collection $list): array
    {
        return $list->toArray();
    }

    /**
     * 获取详情
     * @param int $id
     * @return array
     */
    public function getDetail(int $id): array
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::query()->find($id);
        if (!$model) {
            throw new HyperfCommonException(ErrorCode::CONFIG_NOT_EXISTS);
        }

        return $this->formatDetail($model);
    }

    /**
     * 格式化详情数据
     * @param Model $model
     * @return array
     */
    protected function formatDetail(Model $model): array
    {
        return $model->toArray();
    }

    /**
     * 创建配置
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $modelClass = $this->getModelClass();
        
        // 处理敏感数据
        $data = $this->handleSensitiveData($data);
        
        $model = new $modelClass();
        $model->fill($data);
        $model->save();
        
        // 清理相关配置缓存
        $this->clearConfigCache($model);
        
        return $model->id;
    }

    /**
     * 更新配置
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::query()->find($id);
        if (!$model) {
            throw new HyperfCommonException(ErrorCode::CONFIG_NOT_EXISTS);
        }
        
        // 处理敏感数据
        $data = $this->handleSensitiveData($data, $model);
        
        $result = $model->update($data);
        
        if ($result) {
            // 清理相关配置缓存
            $this->clearConfigCache($model);
        }
        
        return $result;
    }

    /**
     * 删除配置
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::query()->find($id);
        if (!$model) {
            throw new HyperfCommonException(ErrorCode::CONFIG_NOT_EXISTS);
        }
        
        $result = $model->delete();
        
        if ($result) {
            // 清理相关配置缓存
            $this->clearConfigCache($model);
        }
        
        return $result;
    }

    /**
     * 批量删除配置
     * @param array $ids
     * @return bool
     */
    public function batchDelete(array $ids): bool
    {
        $modelClass = $this->getModelClass();
        $models = $modelClass::query()->whereIn('id', $ids)->get();
        
        $result = $modelClass::query()->whereIn('id', $ids)->delete();
        
        if ($result) {
            // 清理相关配置缓存
            foreach ($models as $model) {
                $this->clearConfigCache($model);
            }
        }
        
        return $result;
    }

    /**
     * 处理敏感数据
     * @param array $data
     * @param Model|null $model
     * @return array
     */
    protected function handleSensitiveData(array $data, ?Model $model = null): array
    {
        // 子类根据需要重写此方法
        return $data;
    }

    /**
     * 清理配置缓存
     * @param Model $model
     * @return void
     */
    protected function clearConfigCache(Model $model): void
    {
        try {
            $configType = $this->getConfigType();
            switch ($configType) {
                case 'sms':
                    $provider = $model->provider ?? null;
                    $this->configService->clearSmsConfigCache($provider);
                    break;
                case 'miniapp':
                    $platform = $model->platform ?? null;
                    $this->configService->clearMiniappConfigCache($platform);
                    break;
                case 'endpoint':
                    $businessKey = $model->business_key ?? null;
                    $this->configService->clearEndpointConfigCache($businessKey);
                    break;
                case 'payment':
                    $platform = $model->platform ?? null;
                    $this->configService->clearPaymentConfigCache($platform);
                    break;
                case 'storage':
                    $provider = $model->provider ?? null;
                    $this->configService->clearStorageConfigCache($provider);
                    break;
                default:
                    // 默认清理所有配置缓存
                    $this->configService->clearAllConfigCache();
                    break;
            }
        } catch (\Throwable $e) {
            // 缓存清理失败不影响主业务，记录日志即可
            // 这里可以添加日志记录
        }
    }

    /**
     * 清理所有配置缓存
     * @return bool
     */
    public function clearAllCache(): bool
    {
        try {
            $configType = $this->getConfigType();
            switch ($configType) {
                case 'sms':
                    return $this->configService->clearSmsConfigCache();
                case 'miniapp':
                    return $this->configService->clearMiniappConfigCache();
                case 'endpoint':
                    return $this->configService->clearEndpointConfigCache();
                case 'payment':
                    return $this->configService->clearPaymentConfigCache();
                case 'storage':
                    return $this->configService->clearStorageConfigCache();
                default:
                    return $this->configService->clearAllConfigCache();
            }
        } catch (\Throwable $e) {
            return false;
        }
    }
} 