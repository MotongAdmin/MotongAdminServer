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

namespace App\Facade;

use App\Model\SysSmsConfig;
use App\Model\SysMiniappConfig;
use App\Model\SysEndpointConfig;
use App\Model\SysPaymentConfig;
use App\Model\SysStorageConfig;
use Hyperf\Utils\ApplicationContext;
use App\Service\Base\ConfigService;

/**
 * 系统配置工具类
 */
class ConfigUtil
{
    /**
     * 获取 ConfigService 实例
     *
     * @return ConfigService
     */
    private static function getService(): ConfigService
    {
        return ApplicationContext::getContainer()->get(ConfigService::class);
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::getService()->get($key, $default);
    }

    /**
     * 获取多个配置值
     *
     * @param array $keys 配置键数组
     * @return array
     */
    public static function getMultiple(array $keys): array
    {
        return self::getService()->getMultiple($keys);
    }
    
    /**
     * 获取短信配置
     *
     * @param string|null $provider 供应商名称，如 aliyun, tencent 等
     * @return \App\Model\SysSmsConfig|null
     */
    public static function getSmsConfig(?string $provider = null): ?SysSmsConfig
    {
        return self::getService()->getSmsConfig($provider);
    }

    /**
     * 获取小程序配置
     *
     * @param string|null $platform 平台名称，如 wechat, alipay 等
     * @return \App\Model\SysMiniappConfig|null
     */
    public static function getMiniappConfig(?string $platform = null): ?SysMiniappConfig
    {
        return self::getService()->getMiniappConfig($platform);
    }

    /**
     * 获取端点配置
     *
     * @param string $businessKey 业务键
     * @return \App\Model\SysEndpointConfig|null
     */
    public static function getEndpointConfig(string $businessKey): ?SysEndpointConfig
    {
        return self::getService()->getEndpointConfig($businessKey);
    }

    /**
     * 获取支付配置
     *
     * @param string|null $platform 平台名称，如 wechat, alipay 等
     * @return \App\Model\SysPaymentConfig|null
     */
    public static function getPaymentConfig(?string $platform = null): ?SysPaymentConfig
    {
        return self::getService()->getPaymentConfig($platform);
    }

    /**
     * 获取存储配置
     *
     * @param string|null $provider 供应商名称，如 qiniu, aliyun, tencent 等
     * @return \App\Model\SysStorageConfig|null
     */
    public static function getStorageConfig(?string $provider = null): ?SysStorageConfig
    {
        return self::getService()->getStorageConfig($provider);
    }
} 