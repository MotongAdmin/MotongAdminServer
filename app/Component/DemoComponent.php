<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  1003081775@qq.com
 * @author   zyvincent 
 * @Company  Icodefuture Information Technology Co., Ltd.
 * @license  GPL
 */
declare(strict_types=1);

namespace App\Component;

use ZYProSoft\Component\ModuleCallResult;
use App\Model\SysEndpointConfig;

/**
 * 演示组件 - 展示如何使用BaseAppComponent
 * 这个组件调用一个演示API端点
 */
class DemoComponent extends BaseAppComponent
{
    /**
     * 必须定义业务配置键
     * 这个键对应sys_endpoint_config表中的business_key字段
     * @var string
     */
    protected string $businessKey = 'demo_api';

    /**
     * 处理端点配置，自定义配置处理逻辑
     * @param SysEndpointConfig $config 端点配置对象
     * @param array $baseOptions 基础配置选项
     * @return array 最终的客户端配置选项
     */
    protected function processEndpointConfig(SysEndpointConfig $config, array $baseOptions): array
    {
        $options = $baseOptions;

        // 处理认证配置 - 根据实际需要解析
        if (!empty($config->auth_config)) {
            $authConfig = is_array($config->auth_config) ? $config->auth_config : json_decode($config->auth_config, true);

            if (isset($authConfig['type'])) {
                switch ($authConfig['type']) {
                    case 'bearer':
                        if (isset($authConfig['token'])) {
                            $options['headers']['Authorization'] = 'Bearer ' . $authConfig['token'];
                        }
                        break;
                    case 'api_key':
                        if (isset($authConfig['key'], $authConfig['value'])) {
                            $options['headers'][$authConfig['key']] = $authConfig['value'];
                        }
                        break;
                }
            }
        }

        // 处理额外配置 - 只处理已知的安全选项
        if (!empty($config->extra_config)) {
            $extraConfig = is_array($config->extra_config) ? $config->extra_config : json_decode($config->extra_config, true);

            // 只允许特定的配置选项，避免安全风险
            $allowedOptions = ['verify', 'connect_timeout', 'read_timeout', 'proxy'];
            foreach ($allowedOptions as $option) {
                if (isset($extraConfig[$option])) {
                    $options[$option] = $extraConfig[$option];
                }
            }
        }

        return $options;
    }

    /**
     * 获取用户信息
     * @param int $userId
     * @return ModuleCallResult
     * @throws \Exception
     */
    public function getUserInfo(int $userId): ModuleCallResult
    {
        try {
            // 使用安全的GET请求，会自动验证配置的请求方法
            $response = $this->safeGet("/user/{$userId}");

            $response->getBody()->rewind();
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->fail(500, 'Failed to get user info: ' . $e->getMessage());
        }
    }

    /**
     * 创建用户
     * @param array $userData
     * @return ModuleCallResult
     * @throws \Exception
     */
    public function createUser(array $userData): ModuleCallResult
    {
        try {
            // 使用安全的POST请求
            $response = $this->safePost('/user', [
                'json' => $userData
            ]);

            $response->getBody()->rewind();
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->fail(500, 'Failed to create user: ' . $e->getMessage());
        }
    }


    /**
     * 通用请求示例
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ModuleCallResult
     */
    public function makeRequest(string $method, string $uri, array $options = []): ModuleCallResult
    {
        try {
            // 使用通用的安全请求方法
            $response = $this->safeRequest($method, $uri, $options);

            $response->getBody()->rewind();
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->fail(500, 'Request failed: ' . $e->getMessage());
        }
    }

    /**
     * 检查服务健康状态
     * @return ModuleCallResult
     */
    public function checkHealth(): ModuleCallResult
    {
        try {
            $config = $this->getEndpointConfig();
            if (!empty($config->endpoint_url)) {
                return $this->success(['status' => 'healthy', 'business_key' => $this->businessKey]);
            } else {
                return $this->fail(500, 'Service unhealthy: invalid endpoint config');
            }
        } catch (\Exception $e) {
            return $this->fail(500, 'Service unhealthy: ' . $e->getMessage());
        }
    }
}