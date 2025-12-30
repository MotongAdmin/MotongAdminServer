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

use ZYProSoft\Component\BaseComponent;
use App\Facade\ConfigUtil;
use App\Model\SysEndpointConfig;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Guzzle\CoroutineHandler;
use ZYProSoft\Log\Log;
use Swoole\Coroutine;

/**
 * 应用端点组件基类
 * 解决Hyperf常驻内存导致的配置无法实时变更问题
 * 提供动态配置获取和防错机制
 */
abstract class BaseAppComponent extends BaseComponent
{
    /**
     * 业务配置键，子类必须定义
     * @var string
     */
    protected string $businessKey;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $appContainer;

    public function __construct(ContainerInterface $container)
    {
        $this->appContainer = $container;
        $this->logMsgFormatter = new MessageFormatter($this->logMsgTemplate);

        // 验证业务键是否已定义
        $this->validateBusinessKey();

        // 不在构造函数中创建client，改为延迟创建
    }

    /**
     * 验证业务键是否已定义
     * @throws \InvalidArgumentException
     */
    private function validateBusinessKey(): void
    {
        if (empty($this->businessKey)) {
            throw new \InvalidArgumentException(
                sprintf('Business key must be defined in %s', static::class)
            );
        }
    }

    /**
     * 获取当前端点配置
     * @param bool $forceRefresh 是否强制刷新配置（传递给ConfigService）
     * @return SysEndpointConfig|null
     * @throws \Exception
     */
    protected function getEndpointConfig(bool $forceRefresh = false): ?SysEndpointConfig
    {
        // 直接从ConfigUtil获取配置，依赖其Redis缓存机制
        $config = ConfigUtil::getEndpointConfig($this->businessKey);

        if (!$config) {
            throw new \Exception("Endpoint config not found for business key: {$this->businessKey}");
        }

        // 验证配置
        $this->validateEndpointConfig($config);

        return $config;
    }

    /**
     * 验证端点配置
     * @param SysEndpointConfig $config
     * @throws \Exception
     */
    private function validateEndpointConfig(SysEndpointConfig $config): void
    {
        if (empty($config->endpoint_url)) {
            throw new \Exception("Invalid endpoint config: endpoint_url is required");
        }
    }

    /**
     * 动态创建HTTP客户端
     * @param bool $forceRefresh 是否强制刷新配置
     * @return Client
     * @throws \Exception
     */
    protected function createDynamicClient(bool $forceRefresh = false): Client
    {
        $config = $this->getEndpointConfig($forceRefresh);

        // 构建动态options
        $dynamicOptions = $this->buildClientOptions($config);

        // 创建handler stack
        $stack = null;
        if (Coroutine::getCid() > 0) {
            $stack = HandlerStack::create(new CoroutineHandler());
        }

        // 添加中间件
        $stack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $stack->push(Middleware::log(Log::logger("request"), $this->logMsgFormatter));
        $stack->push(Middleware::log(Log::logger("default"), $this->logMsgFormatter));

        $clientConfig = array_replace(['handler' => $stack], $dynamicOptions);

        if (method_exists($this->appContainer, 'make')) {
            return $this->appContainer->make(Client::class, ['config' => $clientConfig]);
        }

        return new Client($clientConfig);
    }

    /**
     * 构建客户端配置选项
     * 基类只提供基础配置，具体配置处理由子类决定
     * @param SysEndpointConfig $config
     * @return array
     */
    protected function buildClientOptions(SysEndpointConfig $config): array
    {
        $options = [
            'base_uri' => $config->endpoint_url,
            'timeout' => $config->timeout ?? 30,
        ];

        // 调用子类的配置处理方法
        return $this->processEndpointConfig($config, $options);
    }

    /**
     * 处理端点配置，子类可重写此方法来自定义配置处理逻辑
     * @param SysEndpointConfig $config 端点配置对象
     * @param array $baseOptions 基础配置选项
     * @return array 最终的客户端配置选项
     */
    protected function processEndpointConfig(SysEndpointConfig $config, array $baseOptions): array
    {
        // 默认实现：直接返回基础配置
        // 子类可以重写此方法来处理 headers、auth_config、extra_config 等
        return $baseOptions;
    }

    /**
     * 安全的GET请求
     * @param string $uri
     * @param array $options
     * @param bool $forceRefreshConfig
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function safeGet(string $uri, array $options = [], bool $forceRefreshConfig = false)
    {
        $client = $this->createDynamicClient($forceRefreshConfig);
        $config = $this->getEndpointConfig($forceRefreshConfig);

        // 验证请求方法
        if (strtoupper($config->request_method) !== 'GET') {
            throw new \Exception("Endpoint {$this->businessKey} does not support GET method");
        }

        return $client->get($uri, $options);
    }

    /**
     * 安全的POST请求
     * @param string $uri
     * @param array $options
     * @param bool $forceRefreshConfig
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function safePost(string $uri, array $options = [], bool $forceRefreshConfig = false)
    {
        $client = $this->createDynamicClient($forceRefreshConfig);
        $config = $this->getEndpointConfig($forceRefreshConfig);

        // 验证请求方法
        if (strtoupper($config->request_method) !== 'POST') {
            throw new \Exception("Endpoint {$this->businessKey} does not support POST method");
        }

        return $client->post($uri, $options);
    }

    /**
     * 通用的安全请求方法
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param bool $forceRefreshConfig
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function safeRequest(string $method, string $uri, array $options = [], bool $forceRefreshConfig = false)
    {
        $client = $this->createDynamicClient($forceRefreshConfig);
        $config = $this->getEndpointConfig($forceRefreshConfig);

        // 验证请求方法
        if (strtoupper($config->request_method) !== strtoupper($method)) {
            throw new \Exception("Endpoint {$this->businessKey} does not support {$method} method");
        }

        return $client->request($method, $uri, $options);
    }

    /**
     * 获取当前业务键
     * @return string
     */
    public function getBusinessKey(): string
    {
        return $this->businessKey;
    }
}