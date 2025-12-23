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

namespace App\Service\Base;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use ZYProSoft\Cache\ClearPrefixCacheJob;
use App\Model\SysConfig;
use App\Model\SysSmsConfig;
use App\Model\SysMiniappConfig;
use App\Model\SysEndpointConfig;
use App\Model\SysPaymentConfig;
use App\Model\SysStorageConfig;

/**
 * 配置服务类
 */
class ConfigService
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * @var DriverInterface
     */
    protected DriverInterface $driver;

    /**
     * 缓存前缀
     */
    private const CACHE_PREFIX = 'config:';
    
    /**
     * 缓存过期时间（秒）
     */
    private const CACHE_TTL = 3600; // 1小时

    /**
     * 构造函数
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cache = $container->get(CacheInterface::class);
        $driverFactory = $container->get(DriverFactory::class);
        $this->driver = $driverFactory->get('default');
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->generateCacheKey($key);
        
        try {
            // 尝试从缓存获取
            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
            
            // 缓存未命中，从数据库查询
            $configModel = SysConfig::query();
            $config = $configModel->where('config_key', $key)->first();
            $value = $config ? $config->config_value : $default;
            
            // 将结果存入缓存
            $this->cache->set($cacheKey, $value, self::CACHE_TTL);
            
            return $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * 获取多个配置值
     *
     * @param array $keys 配置键数组
     * @return array
     */
    public function getMultiple(array $keys): array
    {
        try {
            $result = [];
            $cacheKeys = [];
            $uncachedKeys = [];
            
            // 生成缓存键并尝试从缓存获取
            foreach ($keys as $key) {
                $cacheKey = $this->generateCacheKey($key);
                $cacheKeys[] = $cacheKey;
                $cachedValue = $this->cache->get($cacheKey);
                
                if ($cachedValue !== null) {
                    $result[$key] = $cachedValue;
                } else {
                    $uncachedKeys[] = $key;
                }
            }
            
            // 如果有未缓存的键，从数据库批量查询
            if (!empty($uncachedKeys)) {
                $configModel = SysConfig::query();
                $configs = $configModel->whereIn('config_key', $uncachedKeys)->get();
                
                foreach ($configs as $config) {
                    $result[$config->config_key] = $config->config_value;
                    // 将新查询的结果存入缓存
                    $cacheKey = $this->generateCacheKey($config->config_key);
                    $this->cache->set($cacheKey, $config->config_value, self::CACHE_TTL);
                }
            }
            
            // 为未找到的键设置null值
            foreach ($keys as $key) {
                if (!isset($result[$key])) {
                    $result[$key] = null;
                }
            }
            
            return $result;
        } catch (\Throwable $e) {
            return array_fill_keys($keys, null);
        }
    }
    
    /**
     * 获取短信配置
     *
     * @param string $provider 供应商名称，如 aliyun, tencent 等
     * @return \App\Model\SysSmsConfig|null
     */
    public function getSmsConfig(string $provider): ?SysSmsConfig
    {
        $cacheKey = $this->generateSmsCacheKey($provider);
        
        try {
            // 尝试从缓存获取
            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
            
            // 缓存未命中，从数据库查询
            $query = SysSmsConfig::query();
            $query->where('provider', $provider);
            $result = $query->first();
            
            // 将结果存入缓存
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 获取小程序配置
     *
     * @param string $platform 平台名称，如 wechat, alipay 等
     * @return \App\Model\SysMiniappConfig|null
     */
    public function getMiniappConfig(string $platform): ?SysMiniappConfig
    {
        $cacheKey = $this->generateMiniappCacheKey($platform);
        
        try {
            // 尝试从缓存获取
            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
            
            // 缓存未命中，从数据库查询
            $query = SysMiniappConfig::query();
            $query->where('platform', $platform);
            $result = $query->first();
            
            // 将结果存入缓存
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 获取端点配置
     *
     * @param string $businessKey 业务键
     * @return \App\Model\SysEndpointConfig|null
     */
    public function getEndpointConfig(string $businessKey): ?SysEndpointConfig
    {
        $cacheKey = $this->generateEndpointCacheKey($businessKey);
        
        try {
            // 尝试从缓存获取
            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
            
            // 缓存未命中，从数据库查询
            $result = SysEndpointConfig::query()
                ->where('business_key', $businessKey)
                ->first();
            
            // 将结果存入缓存
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 获取支付配置
     *
     * @param string $platform 平台名称，如 wechat, alipay 等
     * @return \App\Model\SysPaymentConfig|null
     */
    public function getPaymentConfig(string $platform): ?SysPaymentConfig
    {
        $cacheKey = $this->generatePaymentCacheKey($platform);
        
        try {
            // 尝试从缓存获取
            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
            
            // 缓存未命中，从数据库查询
            $query = SysPaymentConfig::query();
            $query->where('platform', $platform);
            $result = $query->first();
            
            // 将结果存入缓存
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 获取存储配置
     *
     * @param string $provider 供应商名称，如 qiniu, aliyun, tencent 等
     * @return \App\Model\SysStorageConfig|null
     */
    public function getStorageConfig(string $provider): ?SysStorageConfig
    {
        $cacheKey = $this->generateStorageCacheKey($provider);
        
        try {
            // 尝试从缓存获取
            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
            
            // 缓存未命中，从数据库查询
            $query = SysStorageConfig::query();
            $query->where('provider', $provider);
            $result = $query->first();
            
            // 将结果存入缓存
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);
            
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 清除指定配置的缓存
     *
     * @param string $key 配置键
     * @return bool
     */
    public function clearConfigCache(string $key): bool
    {
        try {
            $cacheKey = $this->generateCacheKey($key);
            return $this->cache->delete($cacheKey);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 批量清除配置缓存
     *
     * @param array $keys 配置键数组
     * @return bool
     */
    public function clearMultipleConfigCache(array $keys): bool
    {
        try {
            $success = true;
            foreach ($keys as $key) {
                $cacheKey = $this->generateCacheKey($key);
                if (!$this->cache->delete($cacheKey)) {
                    $success = false;
                }
            }
            return $success;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除短信配置缓存
     *
     * @param string|null $provider 供应商名称，为null时清除所有短信配置缓存
     * @return bool
     */
    public function clearSmsConfigCache(?string $provider = null): bool
    {
        try {
            if ($provider === null) {
                // 清除所有短信配置缓存
                $this->clearCachePrefix(self::CACHE_PREFIX . 'sms:');
                return true;
            } else {
                $cacheKey = $this->generateSmsCacheKey($provider);
                return $this->cache->delete($cacheKey);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除小程序配置缓存
     *
     * @param string|null $platform 平台名称，为null时清除所有小程序配置缓存
     * @return bool
     */
    public function clearMiniappConfigCache(?string $platform = null): bool
    {
        try {
            if ($platform === null) {
                // 清除所有小程序配置缓存
                $this->clearCachePrefix(self::CACHE_PREFIX . 'miniapp:');
                return true;
            } else {
                $cacheKey = $this->generateMiniappCacheKey($platform);
                return $this->cache->delete($cacheKey);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除端点配置缓存
     *
     * @param string|null $businessKey 业务键，为null时清除所有端点配置缓存
     * @return bool
     */
    public function clearEndpointConfigCache(?string $businessKey = null): bool
    {
        try {
            if ($businessKey === null) {
                // 清除所有端点配置缓存
                $this->clearCachePrefix(self::CACHE_PREFIX . 'endpoint:');
                return true;
            } else {
                $cacheKey = $this->generateEndpointCacheKey($businessKey);
                return $this->cache->delete($cacheKey);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除支付配置缓存
     *
     * @param string|null $platform 平台名称，为null时清除所有支付配置缓存
     * @return bool
     */
    public function clearPaymentConfigCache(?string $platform = null): bool
    {
        try {
            if ($platform === null) {
                // 清除所有支付配置缓存
                $this->clearCachePrefix(self::CACHE_PREFIX . 'payment:');
                return true;
            } else {
                $cacheKey = $this->generatePaymentCacheKey($platform);
                return $this->cache->delete($cacheKey);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除存储配置缓存
     *
     * @param string|null $provider 供应商名称，为null时清除所有存储配置缓存
     * @return bool
     */
    public function clearStorageConfigCache(?string $provider = null): bool
    {
        try {
            if ($provider === null) {
                // 清除所有存储配置缓存
                $this->clearCachePrefix(self::CACHE_PREFIX . 'storage:');
                return true;
            } else {
                $cacheKey = $this->generateStorageCacheKey($provider);
                return $this->cache->delete($cacheKey);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除所有配置缓存
     *
     * @return bool
     */
    public function clearAllConfigCache(): bool
    {
        try {
            // 使用 AbstractService 提供的清除缓存方法
            $this->clearCachePrefix(self::CACHE_PREFIX);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 预热配置缓存
     * 预先加载常用配置到缓存中
     *
     * @param array $commonKeys 常用配置键数组
     * @return bool
     */
    public function warmUpCache(array $commonKeys = []): bool
    {
        try {
            if (empty($commonKeys)) {
                return true;
            }

            foreach ($commonKeys as $key) {
                $this->get($key); // 这会自动将配置加载到缓存中
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 检查缓存是否可用
     *
     * @return bool
     */
    public function isCacheAvailable(): bool
    {
        try {
            $testKey = 'cache_test_' . uniqid();
            $testValue = 'test_value';
            
            $this->cache->set($testKey, $testValue, 10);
            $retrievedValue = $this->cache->get($testKey);
            $this->cache->delete($testKey);
            
            return $retrievedValue === $testValue;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 生成基础配置缓存键
     *
     * @param string $key 配置键
     * @return string
     */
    private function generateCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . 'basic:' . md5($key);
    }

    /**
     * 生成短信配置缓存键
     *
     * @param string|null $provider 供应商
     * @return string
     */
    private function generateSmsCacheKey(string $provider): string
    {
        return self::CACHE_PREFIX . 'sms:' . md5($provider);
    }

    /**
     * 生成小程序配置缓存键
     *
     * @param string|null $platform 平台
     * @return string
     */
    private function generateMiniappCacheKey(string $platform): string
    {
        return self::CACHE_PREFIX . 'miniapp:' . md5($platform);
    }

    /**
     * 生成端点配置缓存键
     *
     * @param string $businessKey 业务键
     * @return string
     */
    private function generateEndpointCacheKey(string $businessKey): string
    {
        return self::CACHE_PREFIX . 'endpoint:' . md5($businessKey);
    }

    /**
     * 生成支付配置缓存键
     *
     * @param string|null $platform 平台
     * @return string
     */
    private function generatePaymentCacheKey(string $platform): string
    {
        return self::CACHE_PREFIX . 'payment:' . md5($platform);
    }

    /**
     * 生成存储配置缓存键
     *
     * @param string|null $provider 供应商
     * @return string
     */
    private function generateStorageCacheKey(string $provider): string
    {
        return self::CACHE_PREFIX . 'storage:' . md5($provider);
    }

    /**
     * 通过异步任务清除缓存前缀
     * @param string $prefix
     */
    protected function clearCachePrefix(string $prefix)
    {
        $this->driver->push(new ClearPrefixCacheJob($prefix));
    }
}