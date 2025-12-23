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

use App\Model\SysStorageConfig;
use App\Model\Model;
use Hyperf\Database\Model\Builder;

/**
 * 对象存储配置服务类
 */
class StorageConfigService extends BaseConfigService
{
    /**
     * 获取模型类
     * @return string
     */
    protected function getModelClass(): string
    {
        return SysStorageConfig::class;
    }
    
    /**
     * 获取配置类型标识
     * @return string
     */
    protected function getConfigType(): string
    {
        return 'storage';
    }
    
    /**
     * 构建查询
     * @param array $params
     * @return Builder
     */
    protected function buildQuery(array $params): Builder
    {
        $query = parent::buildQuery($params);
        
        // 根据服务商查询
        if (!empty($params['provider'])) {
            $query->where('provider', $params['provider']);
        }
        
        // 根据访问类型查询
        if (!empty($params['access_type'])) {
            $query->where('access_type', $params['access_type']);
        }
        
        return $query;
    }
    
    /**
     * 处理敏感数据
     * @param array $data
     * @param Model|null $model
     * @return array
     */
    protected function handleSensitiveData(array $data, ?Model $model = null): array
    {
        // 如果是更新操作且密钥为空，则保留原密钥
        if ($model && isset($data['secret_key']) && empty($data['secret_key'])) {
            unset($data['secret_key']);
        }
        
        // 这里可以添加加密处理逻辑
        if (isset($data['secret_key']) && !empty($data['secret_key'])) {
            // $data['secret_key'] = encrypt($data['secret_key']);
        }
        
        return $data;
    }
    
    /**
     * 获取第一个配置
     * @return array|null
     */
    public function getFirstConfig(): ?array
    {
        $model = SysStorageConfig::query()
            ->first();
            
        return $model ? $this->formatDetail($model) : null;
    }
    
    /**
     * 根据服务商获取配置
     * @param string $provider
     * @param string $accessType
     * @return array|null
     */
    public function getByProvider(string $provider, string $accessType = 'public'): ?array
    {
        $model = SysStorageConfig::query()
            ->where('provider', $provider)
            ->where('access_type', $accessType)
            ->first();
            
        return $model ? $this->formatDetail($model) : null;
    }
} 