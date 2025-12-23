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

use App\Model\SysMiniappConfig;
use App\Model\Model;
use Hyperf\Database\Model\Builder;

/**
 * 小程序配置服务类
 */
class MiniappConfigService extends BaseConfigService
{
    /**
     * 获取模型类
     * @return string
     */
    protected function getModelClass(): string
    {
        return SysMiniappConfig::class;
    }
    
    /**
     * 获取配置类型标识
     * @return string
     */
    protected function getConfigType(): string
    {
        return 'miniapp';
    }
    
    /**
     * 构建查询
     * @param array $params
     * @return Builder
     */
    protected function buildQuery(array $params): Builder
    {
        $query = parent::buildQuery($params);
        
        // 根据平台查询
        if (!empty($params['platform'])) {
            $query->where('platform', $params['platform']);
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
        if ($model && isset($data['app_secret']) && empty($data['app_secret'])) {
            unset($data['app_secret']);
        }
        
        // 这里可以添加加密处理逻辑
        if (isset($data['app_secret']) && !empty($data['app_secret'])) {
            // $data['app_secret'] = encrypt($data['app_secret']);
        }
        
        return $data;
    }
    
    /**
     * 获取第一个配置
     * @return array|null
     */
    public function getFirstConfig(): ?array
    {
        $model = SysMiniappConfig::query()
            ->first();
            
        return $model ? $this->formatDetail($model) : null;
    }
    
    /**
     * 根据平台获取配置
     * @param string $platform
     * @return array|null
     */
    public function getByPlatform(string $platform): ?array
    {
        $model = SysMiniappConfig::query()
            ->where('platform', $platform)
            ->first();
            
        return $model ? $this->formatDetail($model) : null;
    }
} 