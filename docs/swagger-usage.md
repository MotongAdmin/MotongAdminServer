# Swagger 文档使用指南

## 1. 快速开始

### 生成文档

```bash
php bin/hyperf.php swagger:generate
```

### 访问文档

启动服务后访问: `http://127.0.0.1:9506/swagger`

## 2. 注解使用

### 2.1 控制器分组注解 `@ApiGroup`

用于标注控制器类的分组信息：

```php
<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Annotation\ApiGroup;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * @AutoController(prefix="/admin/user")
 * @ApiGroup(name="用户管理", description="用户相关接口", order=1)
 */
class UserController extends AbstractController
{
    // ...
}
```

### 2.2 接口文档注解 `@ApiDoc`

用于标注接口方法的文档信息：

```php
use App\Annotation\ApiDoc;
use App\Annotation\Description;

/**
 * @Description("获取用户列表")
 * @ApiDoc(
 *     summary="获取用户列表",
 *     description="分页获取系统用户列表，支持关键词搜索",
 *     tags={"用户管理"},
 *     auth=true,
 *     deprecated=false
 * )
 */
final public function getUserList(AuthedRequest $request)
{
    // ...
}
```

**属性说明：**

| 属性        | 类型   | 说明              |
| ----------- | ------ | ----------------- |
| summary     | string | 接口摘要/简短描述 |
| description | string | 接口详细描述      |
| tags        | array  | 接口标签/分组     |
| auth        | bool   | 是否需要认证      |
| deprecated  | bool   | 是否废弃          |
| version     | string | 接口版本          |

### 2.3 参数注解 `@ApiParam`

用于标注接口方法的参数信息（可多个）：

```php
use App\Annotation\ApiParam;

/**
 * @Description("获取用户列表")
 * @ApiParam(name="page", type="integer", required=false, description="页码", example=1, minimum=1)
 * @ApiParam(name="size", type="integer", required=false, description="每页数量", example=20, minimum=1, maximum=100)
 * @ApiParam(name="keyword", type="string", required=false, description="搜索关键词", maxLength=50)
 * @ApiParam(name="status", type="integer", required=false, description="用户状态", enum={0, 1})
 * @ApiParam(name="role_ids", type="array", required=false, description="角色ID列表", items="integer")
 */
final public function getUserList(AuthedRequest $request)
{
    // ...
}
```

**属性说明：**

| 属性        | 类型      | 说明                                                  |
| ----------- | --------- | ----------------------------------------------------- |
| name        | string    | 参数名称                                              |
| type        | string    | 参数类型 (string/integer/number/boolean/array/object) |
| required    | bool      | 是否必填                                              |
| description | string    | 参数描述                                              |
| example     | mixed     | 示例值                                                |
| default     | mixed     | 默认值                                                |
| enum        | array     | 枚举值列表                                            |
| minimum     | int/float | 最小值 (用于 integer/number)                          |
| maximum     | int/float | 最大值 (用于 integer/number)                          |
| minLength   | int       | 最小长度 (用于 string)                                |
| maxLength   | int       | 最大长度 (用于 string)                                |
| items       | string    | 数组元素类型 (当 type 为 array 时使用)                |

### 2.4 响应注解 `@ApiResponse`

用于标注接口方法的响应信息（可多个）：

```php
use App\Annotation\ApiResponse;

/**
 * @Description("获取用户详情")
 * @ApiResponse(
 *     code=200,
 *     description="成功",
 *     schema={"id": "integer", "username": "string", "nickname": "string"},
 *     example={"id": 1, "username": "admin", "nickname": "管理员"}
 * )
 * @ApiResponse(
 *     code=404,
 *     description="用户不存在"
 * )
 */
final public function getUserInfo(AuthedRequest $request)
{
    // ...
}
```

## 3. 完整示例

```php
<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Annotation\ApiDoc;
use App\Annotation\ApiGroup;
use App\Annotation\ApiParam;
use App\Annotation\ApiResponse;
use App\Annotation\Description;
use App\Service\Admin\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use ZYProSoft\Controller\AbstractController;
use ZYProSoft\Http\AuthedRequest;

/**
 * @AutoController(prefix="/admin/user")
 * @ApiGroup(name="用户管理", description="系统用户管理相关接口")
 */
class UserController extends AbstractController
{
    /**
     * @Inject
     * @var UserService
     */
    protected UserService $service;

    /**
     * @Description("获取用户列表")
     * @ApiDoc(
     *     summary="获取用户列表",
     *     description="分页获取系统用户列表，支持关键词搜索和状态筛选",
     *     tags={"用户管理"},
     *     auth=true
     * )
     * @ApiParam(name="page", type="integer", required=false, description="页码", example=1, minimum=1)
     * @ApiParam(name="size", type="integer", required=false, description="每页数量", example=20, minimum=1, maximum=100)
     * @ApiParam(name="keyword", type="string", required=false, description="搜索关键词")
     * @ApiParam(name="status", type="integer", required=false, description="用户状态 0-禁用 1-启用", enum={0, 1})
     * @ApiResponse(code=200, description="成功")
     */
    final public function getUserList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'keyword' => 'string|max:50',
            'status' => 'integer|in:0,1'
        ]);

        $params = $this->request->getParams();
        $result = $this->service->getUserList($params);
        return $this->success($result);
    }

    /**
     * @Description("创建用户")
     * @ApiDoc(
     *     summary="创建用户",
     *     tags={"用户管理"},
     *     auth=true
     * )
     * @ApiParam(name="username", type="string", required=true, description="用户名", minLength=3, maxLength=50)
     * @ApiParam(name="password", type="string", required=true, description="密码", minLength=6, maxLength=20)
     * @ApiParam(name="nickname", type="string", required=false, description="昵称", maxLength=50)
     * @ApiParam(name="email", type="string", required=false, description="邮箱")
     * @ApiParam(name="role_ids", type="array", required=false, description="角色ID列表", items="integer")
     * @ApiResponse(code=200, description="成功", example={"id": 1})
     */
    final public function create(AuthedRequest $request)
    {
        $this->validate([
            'username' => 'required|string|min:3|max:50|unique:users,username',
            'password' => 'required|string|min:6|max:20',
            'nickname' => 'string|max:50',
            'email' => 'email',
            'role_ids' => 'array'
        ]);

        $params = $this->request->getParams();
        $id = $this->service->create($params);
        return $this->success(['id' => $id]);
    }
}
```

## 4. 配置说明

配置文件位置: `config/autoload/swagger.php`

```php
return [
    // 是否启用 Swagger 文档
    'enable' => env('SWAGGER_ENABLE', true),

    // 文档输出目录
    'output' => BASE_PATH . '/public/swagger',

    // API 文档基本信息
    'info' => [
        'title' => env('SWAGGER_TITLE', 'Motong Server API'),
        'version' => env('SWAGGER_VERSION', '1.0.0'),
        'description' => 'Motong Server 接口文档 (ZGW协议)',
    ],

    // 服务器配置
    'servers' => [
        ['url' => env('APP_URL', 'http://127.0.0.1:9506'), 'description' => '开发环境'],
    ],

    // 扫描路径配置
    'scan' => [
        'paths' => [
            BASE_PATH . '/app/Controller',
            BASE_PATH . '/extensions',
        ],
    ],
];
```

## 5. 环境变量

可在 `.env` 文件中配置：

```env
# Swagger 配置
SWAGGER_ENABLE=true
SWAGGER_TITLE="Motong Server API"
SWAGGER_VERSION="1.0.0"
```

## 6. 注意事项

1. **ZGW 协议适配**: 所有接口都会自动适配 ZGW 协议格式，请求体会包含完整的 ZGW 协议结构
2. **认证检测**: 如果方法参数包含 `AuthedRequest`，会自动标记为需要认证
3. **@Description 兼容**: 如果没有 `@ApiDoc`，会自动读取 `@Description` 注解作为接口摘要
4. **生产环境**: 建议在生产环境禁用 Swagger (`SWAGGER_ENABLE=false`)
