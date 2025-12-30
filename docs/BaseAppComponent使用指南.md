# BaseAppComponent 使用指南

## 概述

`BaseAppComponent` 是为了解决 Hyperf 常驻内存框架中第三方服务配置无法实时变更的问题而设计的基础组件类。它基于 `ZYProSoft\Component\BaseComponent` 构建，提供了动态配置获取和防错机制。

## 核心特性

### 1. 动态配置获取

- **延迟初始化**：不在构造函数中创建 HTTP 客户端，而是在每次请求时动态创建
- **配置缓存**：支持请求级配置缓存，提高性能
- **强制刷新**：支持强制刷新配置，确保获取最新配置

### 2. 防错机制

- **业务键验证**：强制子类定义 `businessKey`，防止配置丢失
- **配置验证**：自动验证端点配置的完整性和有效性
- **请求方法验证**：确保请求方法与配置匹配，防止错误调用

### 3. 安全请求方法

- `safeGet()` - 安全的 GET 请求
- `safePost()` - 安全的 POST 请求
- `safeRequest()` - 通用安全请求方法

## 使用方法

### 1. 创建组件类

```php
<?php

namespace App\Component;

use ZYProSoft\Component\ModuleCallResult;
use App\Model\SysEndpointConfig;

class YourApiComponent extends BaseAppComponent
{
    /**
     * 必须定义业务配置键
     * 对应 sys_endpoint_config 表中的 business_key 字段
     */
    protected string $businessKey = 'your_api_key';

    /**
     * 自定义配置处理逻辑
     * 基类只提供 base_uri 和 timeout，其他配置由子类处理
     */
    protected function processEndpointConfig(SysEndpointConfig $config, array $baseOptions): array
    {
        $options = $baseOptions;

        // 根据实际需要处理认证配置
        if (!empty($config->auth_config)) {
            $authConfig = is_array($config->auth_config) ? $config->auth_config : json_decode($config->auth_config, true);

            if (isset($authConfig['type']) && $authConfig['type'] === 'bearer') {
                if (isset($authConfig['token'])) {
                    $options['headers']['Authorization'] = 'Bearer ' . $authConfig['token'];
                }
            }
        }

        // 处理额外配置 - 只允许安全的选项
        if (!empty($config->extra_config)) {
            $extraConfig = is_array($config->extra_config) ? $config->extra_config : json_decode($config->extra_config, true);

            // 白名单方式处理配置，确保安全
            $allowedOptions = ['verify', 'connect_timeout', 'read_timeout'];
            foreach ($allowedOptions as $option) {
                if (isset($extraConfig[$option])) {
                    $options[$option] = $extraConfig[$option];
                }
            }
        }

        return $options;
    }

    /**
     * 调用第三方API
     */
    public function callApi(array $params): ModuleCallResult
    {
        try {
            $response = $this->safePost('/api/endpoint', [
                'json' => $params
            ]);

            $response->getBody()->rewind();
            $result = json_decode($response->getBody()->getContents(), true);

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->fail(500, 'API调用失败: ' . $e->getMessage());
        }
    }
}
```

### 2. 配置数据库记录

在 `sys_endpoint_config` 表中添加配置记录：

```sql
INSERT INTO sys_endpoint_config (
    business_key,
    name,
    description,
    endpoint_url,
    request_method,
    timeout,
    headers,
    auth_config,
    extra_config
) VALUES (
    'your_api_key',
    'Your API Service',
    'Description of your API service',
    'https://api.example.com',
    'POST',
    30,
    '{"Content-Type": "application/json"}',
    '{"type": "bearer", "token": "your_token"}',
    '{"verify": false}'
);
```

### 3. 使用组件

```php
// 在控制器或服务中使用
$apiComponent = $container->get(YourApiComponent::class);

// 普通请求
$result = $apiComponent->callApi(['param1' => 'value1']);

// 强制刷新配置后请求（当你知道配置可能已更新）
$result = $apiComponent->callApiWithRefresh(['param1' => 'value1']);

// 健康检查
$health = $apiComponent->checkHealth();

// 获取配置摘要（用于调试）
$summary = $apiComponent->getConfigSummary();
```

## 配置结构说明

### 认证配置 (auth_config)

支持多种认证方式：

```json
// Bearer Token
{
    "type": "bearer",
    "token": "your_bearer_token"
}

// Basic Auth
{
    "type": "basic",
    "username": "your_username",
    "password": "your_password"
}

// API Key
{
    "type": "api_key",
    "key": "X-API-Key",
    "value": "your_api_key"
}
```

### 额外配置 (extra_config)

支持 Guzzle 的所有配置选项：

```json
{
  "verify": false,
  "connect_timeout": 10,
  "read_timeout": 30,
  "proxy": "http://proxy.example.com:8080"
}
```

## 配置处理设计原则

### 基类职责分离

`BaseAppComponent` 遵循最小职责原则：

1. **基类只处理核心配置**：

   - `base_uri`：端点 URL
   - `timeout`：请求超时时间

2. **子类处理具体配置**：
   - `headers`：请求头配置
   - `auth_config`：认证配置
   - `extra_config`：额外配置选项

### 为什么这样设计？

1. **灵活性**：不同的第三方服务有不同的配置需求，基类无法预知所有情况
2. **安全性**：子类可以实现白名单机制，只允许安全的配置选项
3. **可维护性**：配置逻辑分散到具体的业务组件中，更容易维护
4. **扩展性**：新的配置需求不需要修改基类

### 配置处理示例

```php
protected function processEndpointConfig(SysEndpointConfig $config, array $baseOptions): array
{
    $options = $baseOptions;

    // 示例1：简单的Bearer Token认证
    if (!empty($config->auth_config)) {
        $authConfig = json_decode($config->auth_config, true);
        if ($authConfig['type'] === 'bearer') {
            $options['headers']['Authorization'] = 'Bearer ' . $authConfig['token'];
        }
    }

    // 示例2：白名单方式处理额外配置
    if (!empty($config->extra_config)) {
        $extraConfig = json_decode($config->extra_config, true);
        $allowedOptions = ['verify', 'connect_timeout', 'proxy'];

        foreach ($allowedOptions as $option) {
            if (isset($extraConfig[$option])) {
                $options[$option] = $extraConfig[$option];
            }
        }
    }

    return $options;
}
```

## 最佳实践

### 1. 错误处理

```php
public function callApi(array $params): ModuleCallResult
{
    try {
        $response = $this->safePost('/api/endpoint', ['json' => $params]);
        // 处理响应...
        return $this->success($result);
    } catch (\Exception $e) {
        // 记录详细错误日志
        Log::error('API调用失败', [
            'business_key' => $this->businessKey,
            'params' => $params,
            'error' => $e->getMessage(),
            'config' => $this->getConfigSummary()
        ]);

        return $this->fail(500, 'API调用失败', ['error' => $e->getMessage()]);
    }
}
```

### 2. 配置更新处理

```php
// 当配置可能已更新时，使用强制刷新
public function callApiWithRefresh(array $params): ModuleCallResult
{
    try {
        // 第三个参数为 true 表示强制刷新配置
        $response = $this->safePost('/api/endpoint', ['json' => $params], true);
        // 处理响应...
        return $this->success($result);
    } catch (\Exception $e) {
        return $this->fail(500, 'API调用失败: ' . $e->getMessage());
    }
}
```

### 3. 健康检查集成

```php
// 在应用健康检查中使用
public function healthCheck(): array
{
    $components = [
        'user_api' => $container->get(UserApiComponent::class),
        'payment_api' => $container->get(PaymentApiComponent::class),
    ];

    $results = [];
    foreach ($components as $name => $component) {
        $health = $component->checkHealth();
        $results[$name] = $health->isSuccess();
    }

    return $results;
}
```

## 防错机制详解

### 1. 业务键验证

- 构造函数会检查 `$businessKey` 是否已定义
- 未定义会抛出 `InvalidArgumentException`

### 2. 配置验证

- 自动验证 `endpoint_url` 是否为有效 URL
- 验证 `request_method` 是否支持
- 验证 `timeout` 是否在合理范围内

### 3. 请求方法验证

- `safeGet()` 只允许配置为 GET 的端点
- `safePost()` 只允许配置为 POST 的端点
- 不匹配会抛出异常，防止错误调用

### 4. 配置缓存管理

- 请求级缓存避免重复查询数据库
- 支持手动刷新缓存
- 支持禁用缓存（调试时使用）

## 性能优化建议

1. **启用配置缓存**：默认已启用，避免频繁数据库查询
2. **合理设置超时**：根据第三方服务响应时间设置合适的超时值
3. **使用连接池**：Hyperf 的协程客户端自动支持连接复用
4. **监控配置变更**：配置更新后及时清理相关缓存

## 故障排查

### 1. 配置未找到

```
Endpoint config not found for business key: xxx
```

- 检查 `sys_endpoint_config` 表中是否存在对应记录
- 确认 `business_key` 拼写正确

### 2. 请求方法不匹配

```
Endpoint xxx does not support GET method
```

- 检查配置中的 `request_method` 字段
- 确保使用正确的请求方法

### 3. URL 无效

```
Invalid endpoint config: endpoint_url must be a valid URL
```

- 检查 `endpoint_url` 格式是否正确
- 确保包含协议（http:// 或 https://）

## 扩展功能

### 1. 自定义验证规则

```php
class YourApiComponent extends BaseAppComponent
{
    protected array $configValidationRules = [
        'endpoint_url' => 'required|url',
        'request_method' => 'required|in:GET,POST',
        'timeout' => 'integer|min:5|max:60', // 自定义超时范围
    ];
}
```

### 2. 自定义中间件

```php
protected function createDynamicClient(bool $forceRefresh = false): Client
{
    $client = parent::createDynamicClient($forceRefresh);

    // 添加自定义中间件
    $stack = $client->getConfig('handler');
    $stack->push(YourCustomMiddleware::create());

    return $client;
}
```

这个组件设计确保了在 Hyperf 常驻内存环境下，第三方服务配置能够实时更新，同时提供了完善的防错机制，帮助开发者避免常见的配置和调用错误。
