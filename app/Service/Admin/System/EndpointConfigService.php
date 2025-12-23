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

use App\Model\SysEndpointConfig;
use App\Model\Model;
use Hyperf\Database\Model\Builder;

/**
 * 端点配置服务类
 */
class EndpointConfigService extends BaseConfigService
{
    /**
     * 获取模型类
     * @return string
     */
    protected function getModelClass(): string
    {
        return SysEndpointConfig::class;
    }
    
    /**
     * 获取配置类型标识
     * @return string
     */
    protected function getConfigType(): string
    {
        return 'endpoint';
    }
    
    /**
     * 构建查询
     * @param array $params
     * @return Builder
     */
    protected function buildQuery(array $params): Builder
    {
        $query = parent::buildQuery($params);
        
        // 根据业务标识查询
        if (!empty($params['business_key'])) {
            $query->where('business_key', $params['business_key']);
        }
        
        // 根据请求方式查询
        if (!empty($params['request_method'])) {
            $query->where('request_method', $params['request_method']);
        }
        
        return $query;
    }
    
    /**
     * 处理敏感数据和JSON数据
     * @param array $data
     * @param Model|null $model
     * @return array
     */
    protected function handleSensitiveData(array $data, ?Model $model = null): array
    {
        return $data;
    }
    
    /**
     * 根据业务标识获取配置
     * @param string $businessKey
     * @return array|null
     */
    public function getByBusinessKey(string $businessKey): ?array
    {
        $model = SysEndpointConfig::query()
            ->where('business_key', $businessKey)
            ->first();
            
        return $model ? $this->formatDetail($model) : null;
    }
} 