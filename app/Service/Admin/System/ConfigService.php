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

use App\Model\SysConfig;
use App\Service\Admin\BaseService;
use App\Service\Base\ConfigService as BaseConfigService;
use Hyperf\Contract\ContainerInterface;

class ConfigService extends BaseService
{
    /**
     * @var SysConfig
     */
    protected $model;

    /**
     * @var BaseConfigService
     */
    protected BaseConfigService $baseConfigService;

    /**
     * 构造函数
     * @param ContainerInterface $container
     * @param BaseConfigService $baseConfigService
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->baseConfigService = $container->get(BaseConfigService::class);
        $this->model = $container->get(SysConfig::class);
    }

    /**
     * 获取所有配置
     *
     * @return array
     */
    public function list(): array
    {
        return $this->model->get()->toArray();
    }

    /**
     * 添加配置
     *
     * @param array $data
     * @return int
     */
    public function add(array $data): int
    {
        // 检查配置键是否已存在
        $exists = $this->model->where('config_key', $data['config_key'])->exists();
        if ($exists) {
            throw new \Exception("配置键 {$data['config_key']} 已存在");
        }

        // 设置默认值
        if (!isset($data['is_system'])) {
            $data['is_system'] = 0;
        }
        if (!isset($data['config_type'])) {
            $data['config_type'] = 'text';
        }

        $model = $this->model->create($data);
        
        // 清理相关配置缓存
        $this->baseConfigService->clearConfigCache($data['config_key']);
        
        return $model->id;
    }

    /**
     * 更新配置
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $config = $this->model->find($id);
        if (!$config instanceof SysConfig) {
            throw new \Exception("配置不存在");
        }

        // 如果更改了配置键，需要检查是否与其他配置冲突
        if (isset($data['config_key']) && $data['config_key'] !== $config->config_key) {
            $exists = $this->model->where('config_key', $data['config_key'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                throw new \Exception("配置键 {$data['config_key']} 已存在");
            }
        }

        // 检查是否尝试将系统配置降级为普通配置
        if ($config->is_system == 1 && isset($data['is_system']) && $data['is_system'] == 0) {
            throw new \Exception("系统配置不能降级为普通配置");
        }

        $result = $config->update($data);
        
        if ($result) {
            // 清理相关配置缓存
            $this->baseConfigService->clearConfigCache($config->config_key);
            // 如果配置键发生变化，也要清理新的缓存
            if (isset($data['config_key']) && $data['config_key'] !== $config->config_key) {
                $this->baseConfigService->clearConfigCache($data['config_key']);
            }
        }
        
        return $result;
    }

    /**
     * 删除配置
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $config = $this->model->find($id);
        if (!$config instanceof SysConfig) {
            throw new \Exception("配置不存在");
        }

        // 检查是否为系统配置
        if ($config->is_system == 1) {
            throw new \Exception("系统配置不允许删除");
        }

        $result = $config->delete();
        
        if ($result) {
            // 清理相关配置缓存
            $this->baseConfigService->clearConfigCache($config->config_key);
        }
        
        return $result;
    }

    /**
     * 批量删除配置
     *
     * @param array $ids
     * @return bool
     */
    public function batchDelete(array $ids): bool
    {
        $configs = $this->model->whereIn('id', $ids)->get();
        
        // 检查是否包含系统配置
        foreach ($configs as $config) {
            if ($config->is_system == 1) {
                throw new \Exception("系统配置不允许删除");
            }
        }
        
        $result = $this->model->whereIn('id', $ids)->delete();
        
        if ($result) {
            // 清理相关配置缓存
            foreach ($configs as $config) {
                $this->baseConfigService->clearConfigCache($config->config_key);
            }
        }
        
        return true;
    }

    /**
     * 根据键名获取配置值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfigByKey(string $key, $default = null)
    {
        $config = $this->model->where('config_key', $key)->first();
        return $config ? $config->config_value : $default;
    }

    /**
     * 根据键名获取多个配置值
     *
     * @param array $keys
     * @return array
     */
    public function getConfigByKeys(array $keys): array
    {
        $configs = $this->model->whereIn('config_key', $keys)->get();
        $result = [];
        foreach ($configs as $config) {
            $result[$config->config_key] = $config->config_value;
        }
        return $result;
    }
} 