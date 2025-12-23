# Motong-Server 项目开发规范

## 1. 项目背景与技术栈

- **框架**：Hyperf 2.2（文档：[https://www.hyperf.wiki/2.2/#/](https://www.hyperf.wiki/2.2/#/))
- **技术要求**：
  - PHP >= 7.4
  - Swoole >= 4.5
  - 核心组件：`zyprosoft/hyperf-common`

---

## 2. 项目目录结构

生成代码或文档时，严格遵循以下目录结构：

- **app**: 核心应用功能
  - **Annotation**: 自定义注解
  - **Command**: 自定义命令行工具
  - **Component**: 第三方服务调用组件
  - **Constants**: 常量定义
  - **Controller**: 接口层
    - **Admin/System**: 系统级管理端接口
    - **Callback**: 外部第三方回调接口
    - **Common**: 面向普通用户的前台接口
  - **Job**: 异步任务，处理耗时或弱相关逻辑
  - **Listener**: 事件监听与分发
  - **Middleware**: 请求前置/后置处理
  - **Model**: 数据表模型
  - **Process**: 边路进程，处理特殊任务
  - **Service**: 服务层，承载接口业务逻辑
  - **Task**: 定时任务
  - **Traits**: 公共方法抽象
  - **Util**: 工具类
- **assets**: 静态资源
- **bin**: 启动文件
- **config**: 配置文件
- **extensions**: 独立功能模块（例如 OAuth、Pay）
- **public**: 可访问静态资源
- **test**: 测试用例

**指令**：生成代码时，确保文件放置在正确目录，遵循模块化设计。

---

## 3. 框架级开发规范

- **单例对象**：
  - 容器获取的对象均为单例。
  - **禁止**在单例对象属性中存储有状态值，避免请求上下文污染。
- **表模型**：
  - 所有表模型必须继承 `App\Model\Model`。
  - 遵循 Hyperf ORM 规范，确保模型与数据库交互一致。
- **严格类型声明**：
  - 所有 PHP 文件使用 `declare(strict_types=1);` 开启严格类型声明。
- **指令**：生成代码时，确保单例对象无状态存储，表模型继承正确基类并遵循 ORM 规范，文件头部添加严格类型声明。

---

## 4. 数据库设计规范

- **迁移文件**：
  - 每个表必须提供数据库迁移文件。
- **字段设计**：
  - 设置合理的字段存储长度。
  - 每个字段必须包含清晰注释，说明用途和约束。
  - 自增主键默认使用 bigint(20)
- **索引设计**：
  - 合理设计唯一约束、复合索引。
  - 优先考虑查询性能，可适度使用空间冗余换取性能。
- **软删除与状态字段**：
  - 谨慎使用软删除和状态字段，避免滥用。
- **表模型规范**：
  - 模型类遵循 Hyperf ORM 规范，使用注解（如 `@property`）定义字段类型。
  - 确保模型与迁移文件一致，支持软删除或查询作用域。
- **指令**：生成数据库相关代码或迁移文件时，包含字段注释、合理索引，模型遵循 ORM 规范，优化性能。

---

## 5. 接口开发规范

- **协议**：
  - 遵循 ZGW 协议，三段式接口命名：`大模块名.控制器.方法`。
  - ZGW 协议固定就是基于 POST 设计的，不存在其他方式请求。
  - 使用 `@AutoController(prefix="大模块名/控制器")` 注解自动映射请求。
  - 示例：`@AutoController(prefix="/common/zgw")` 注解后，请求 `common.zgw.sayHello`：
    ```php
    curl -d'{
        "version":"1.0",
        "seqId":"xxxxx",
        "timestamp":1601017327,
        "eventId":1601017327,
        "caller":"test",
        "interface":{
            "name":"common.zgw.sayHello",
            "param":{}
        }
    }' http://127.0.0.1:9506
    ```
- **注解规范**

- **Controller 注解规范**:

  ```php
  /**
   * @AutoController (prefix="/system/user")
   * Class UserController
   * @package App\Controller\Admin
  */
  class UserController extends AbstractController
  {
      /**
      * @Inject
      * @var UserService
      */
      protected UserService $service;

      /**
      * @Inject
      * @var RolePermissionService
      */
      protected RolePermissionService $rolePermissionService;
  }
  ```

- **方法注解规范**:

  ```php
  /**
   * @Description(value="获取用户列表")
   * 获取用户列表
   * @param AuthedRequest $request
   * @return \Psr\Http\Message\ResponseInterface
   */
  final public function getUserList(AuthedRequest $request)
  {
      $this->validate([
          'page' => 'integer|min:1',
          'size' => 'integer|min:1|max:100'
      ]);

      $params = $this->request->getParams();
      $result = $this->service->getUserList($params);
      return $this->success($result);
  }
  ```

- **模块命名**：
  - 非项目框架系统级接口禁止使用 `system` 作为大模块名，需根据模块提供方归属命名，例如项目基础框架提供方，可以使用 motong 为大模块名，示例: `motong.payment.getConfig`。
  - 任意功能模块的管理端功能，只允许使用 `admin` 作为大模块名，例如，配置管理模块，`admin.config.getList` 。
  - 任意非管理端功能，禁止使用 `admin` 与 `system` 作为大模块名。
  - 注意，三段式命名中的第二段，控制器模块命名原则，如果遇到需要两个及以上单词才能表示功能的时候，不使用符号“-”进行连接，应该采用驼峰命名的方式将两个单词连接，形成中间段的命名，例如 "admin.paymentConfig.getList" 这样是正确的命名规范，而不应该使用 payment-config。
- **注解**：
  - 每个接口方法必须添加 `@Description` 注解，描述方法功能。
- **参数校验**：
  - 必须实现参数合法性校验，提供友好错误提示。
  - 对于涉及表主键和唯一性约束的字段，必须在参数合法性校验中设定规则
  - 针对唯一性约束字段，如果是涉及了更新操作，需要使用更新唯一字段的排除写法，避免错误校验。例如，config 表更新唯一字段 config_key, 规则应该是 `config_key|unique:config,config_key,${config_id},config_id` 其中 `${config_id}` 为当次更新记录的主键值。通过这种约束，避免更新的时候错误校验唯一性。
  - 参数校验的详细规则，你可以参考Hyperf2.2文档中的验证器部分，下面是一个继承自 `ZYProSoft\Controller\AbstractController` 的Controller在接口方法做参数校验的样例。
  ```php
  /**
     * @Description("创建小程序配置")
     * ZGW接口名: system.miniappConfig.create
     */
    public function create(AuthedRequest $request)
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'platform' => 'required|string|max:20',
            'app_id' => 'required|string|max:100',
            'app_secret' => 'required|string|max:100',
            'auth_redirect' => 'nullable|string|max:255',
            'message_token' => 'nullable|string|max:100',
            'message_aeskey' => 'nullable|string|max:100',
            'extra_config' => 'nullable|array'
        ]);
        
        $data = $request->getParams();
        $id = $this->service->create($data);
        return $this->success(['id' => $id]);
    }
  ```
  - 根据Hyperf2.2文档中的验证器部分，根据该字段对应存储的模型表结构，尽可能的设置较为完善的验证规则，在参数阶段实现较完善的过滤限制。
  - 参数命名使用 `snake_case` 规范。
  - 示例错误消息配置：
    ```php
    public function messages()
    {
        return [
            'required' => ':attribute不能为空',
            'string' => ':attribute必须是字符串',
            'integer' => ':attribute必须是整数',
            'max' => ':attribute长度不能超过:max位',
            'unique' => ':attribute已存在',
            'exists' => ':attribute不存在',
            'in' => ':attribute的值不在允许范围内',
            'array' => ':attribute必须是数组',
            'api_id.required' => '接口ID不能为空',
            'api_id.integer' => '接口ID必须是整数',
            'api_id.exists' => '接口不存在',
            // 其他字段特定消息...
        ];
    }
    ```
- **业务逻辑**：
  - **禁止**在 Controller 层编写业务逻辑，业务逻辑必须在 Service 层实现。
- **基类**：
  - 接口控制器默认继承 `ZYProSoft\Controller\AbstractController`，除非有特殊 `BaseController`。
- **目录要求**
  - app主应用中，新增的面向管理端站点的接口对象统一必须放置在app/Controller/Admin之下或该目录的子目录之下
- **依赖注入**：
  - Service 对象通过 `@Inject` 注解注入到 Controller 属性。
- **指令**：生成接口代码时，遵循 ZGW 协议，添加 `@Description` 注解，使用 `snake_case` 参数命名，严格分离 Controller 和 Service 层逻辑，使用 `@Inject` 注入依赖。
- **参数读取**:
  - 全部参数读取 $this->request->getParams();
  - 单个参数读取 $this->request->param('key');

---

## 6. 服务层开发规范

- **基类**：
  - 管理端服务继承 `App\Service\Admin\BaseService`。
  - 其他服务默认继承 `ZYProSoft\Service\AbstractService`。
- **公共服务**：
  - 公共能力（如短信发送、存储、数据范围等）在 `app\Service\Base` 目录实现。
- **目录要求**
  - app主应用中，新增的面向管理端站点的服务对象统一必须放置在app/Service/Admin目录或该目录的子目录之下
- **异常处理**：
  - 抛出 `ZYProSoft\Exception\HyperfCommonException` 异常。
  - 错误响应通过抛出异常实现，由框架统一捕获处理。
- **常量**：
  - 禁止使用魔数，所有数字需定义为常量（如 `app/Constants` 中）。
- **指令**：生成服务层代码时，继承正确基类，使用常量代替魔数，抛出指定异常类型。

---

## 7. 异步任务开发规范

- **参数存储**：
  - 任务对象避免存储复杂参数，仅保存必要参数。
  - 复杂参数在异步任务 Handler 中从数据库或其他存储读取。
- **日志记录**：
  - 使用 `ZYProSoft\Log\Log::task` 记录任务开始和结束日志，便于追踪执行状态。
- **使用原则**
  - 如果需要在服务层处理接口逻辑时触发不影响主流程的任务，则应该采用异步任务机制进行实现。
  - 任意过度耗时任务都应该采用异步化处理，后续通过状态更新机制去完成长时间的任务处理逻辑。
- **实现规范**
  - 
- **指令**：生成异步任务代码时，最小化参数存储，添加任务执行日志。

---

## 8. 事件机制开发规范

- **定义事件**：

  - 一个事件其实就是一个用于管理状态数据的普通类，触发时将应用数据传递到事件里，然后监听器对事件类进行操作，一个事件可被多个监听器监听。
  - 示例:

  ```php
      <?php
        namespace App\Event;

        class UserRegistered
        {
          // 建议这里定义成 public 属性，以便监听器对该属性的直接使用，或者你提供该属性的 Getter
          public $user;

          public function __construct($user)
          {
            $this->user = $user;
          }
        }
  ```

- **定义监听器**：
  - 监听器都需要实现一下 `Hyperf\Event\Contract\ListenerInterface` 接口的约束方法，示例如下。
  ```php
     <?php
      namespace App\Listener;

      use App\Event\UserRegistered;
      use Hyperf\Event\Contract\ListenerInterface;

      class UserRegisteredListener implements ListenerInterface
      {
        public function listen(): array
        {
          // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
          return [
            UserRegistered::class,
          ];
        }

      /**
       * @param UserRegistered $event
       */
       public function process(object $event)
       {
          // 事件触发后该监听器要执行的代码写在这里，比如该示例下的发送用户注册成功短信等
          // 直接访问 $event 的 user 属性获得事件触发时传递的参数值
          // $event->user;

       }

      }
  ```
  - **事件分发规则**
  - 如果要求监听方与本次服务逻辑处理具有强一致性，请直接使用服务层的 `$this->dispatch` 方法进行分发，该分发逻辑会因为任意方调用报错而中断。
  - 如果要求监听方与本次服务逻辑处理互不影响，优先保证服务层主流程正常完成，则应该使用异步Job的形式，在异步任务处理中将事件通过全局的`Psr\EventDispatcher\EventDispatcherInterface`对象进行分发。
- **指令**： 合理的定义事件和监听器，使得流程处理架构更合理，但是需要考虑事件分发的同步性，避免耗时操作或者异常抛出，导致接口层最终异常报错。

---

## 9. 项目基础能力提供
为扩展模块提供以下基础能力：
- **字典功能**：
- 提供系统级字典管理，支持扩展模块快速创建自定义字典数据。
- **菜单配置**：
- 提供菜单配置功能，允许扩展模块注入后台管理菜单，支持管理端站点使用。
- **端点配置存储**：
- 提供第三方端点配置存储能力，便于模块初始化外部服务配置。
- **身份认证**：
- 提供 `ZYProSoft\Http\AuthedRequest` 对象，支持接口层快速注入用户身份认证校验。
- 示例：注入 `AuthedRequest` 验证用户身份：
  ```php
  use ZYProSoft\Http\AuthedRequest;

  /**
   * @Description("获取用户信息")
   */
  public function getUserInfo(AuthedRequest $request)
  {
      $userId = $request->userId; // 获取认证用户ID
      // 业务逻辑
  }
  ```
- **指令**：生成扩展模块代码时，利用字典、菜单、端点配置和 `AuthedRequest` 实现快速集成。

---

## 10. 扩展模块开发
- **模块安装与卸载**
  - 通过实现安装和卸载的Command命令行工具，实现模块的安装卸载
  - 模块的安装命令内容：创建本模块表结构、注入菜单数据到系统菜单表、注入本模块的接口信息到系统接口表，注入本模块的名称到字典配置中的system_module类型中。
  - 注入本模块的初始化配置数据和所需要的初始化功能数据
  - 模块的卸载命令内容：将安装模块中的初始化内容进行反向清理，清理前可以根据参数，实现是否执行备份。
- **事件分发设计**
  - 需要考虑提供合适的触发事件，并且需要考虑如何在适当的的服务层或者异步任务逻辑处理中触发，以便外部模块可以通过监听器实现合理的回调或者副作用处理。通常都是使用`ZYProSoft\Service\AbstractService`对象中的`$this->dispatch`方法来实现对事件的分发。
- **错误码定制**
  - 扩展模块错误码常量定义必须从大于40000开始，否则将会面临和系统框架底层错误码重叠的问题。
- **接口设计原则**
  - 扩展模块中的接口设计实现，均需遵守与主应用一样的规范，即遵循当前文档中前述1-9部分的所有接口和服务层的设计规范。
- **默认权限菜单生成**
  - 当前模块的任意管理端页面菜单，其下面必须默认包含对应的增删改操作按钮权限，分别对应为，add、remove、edit。
  - 这样就可以构成页面默认携带的三个权限，例如，支付配置管理页面，将默认插入三个从属的按钮权限为 admin.paymentConfig.add, admin.paymentConfig.remove, admin.paymentConfig.edit。这些按钮权限需要在安装模块命令中实现初始化插入。
  - 由于扩展模块是动态集成进项目框架的，所以，扩展模块的目录、页面、按钮之间的menu_id和parent_id关系需要动态确定。
- **目录限制**
  - 与主app应用目录限制一致，面向管理端接口的Controller或者Service放置在该模块对应层级的Admin子目录内。
  - 如果是通用的服务逻辑Service，则放置在Service\Base目录下或者该目录的子目录下，需要严格遵守这个规则。
- **菜单与接口绑定**
  - 必须在安装命令中，实现菜单与接口的默认绑定。
  - 如果是增删改的操作按钮，需要对应实现所需的接口绑定。
- **指令**: 扩展模块开发必须遵循项目规范，必须提供合理的安装与卸载指令，提供合理的事件机制对外通知，规范错误码取值。


## 11. 通用指令
- **代码风格**：
- 遵循 PSR-1, PSR-2, PSR-4 规范，确保代码一致性。
- 所有 PHP 文件使用 `declare(strict_types=1);` 开启严格类型声明.
- **性能优化**：
- 优先考虑高并发场景，使用协程特性优化性能。
- **可维护性**：
- 代码模块化，注释清晰，逻辑分层明确。
- **错误处理**：
- 所有异常需明确定义，提供用户友好的错误提示。
- **输出要求**：
- 生成代码、文档或验证结果时，严格遵循上述规范。
- 提供具体示例代码（如 PHP 代码片段）以说明实现方式。
- 检测不符合规范的地方，明确指出并建议改进方案。
- **日志函数使用**:
- 记住日志函数的正确记录方法，记录日志的静态函数，接收的是一个日志内容字符串，而不是可变参数，以下为正确示例:
```php
// 普通日志，写入daily_开头的日志文件
Log::info("开始异步执行定时任务".json_encode([
              'class' => $this->className,
              'method' => $this->method,
              'taskName' => $this->taskName
          ]));
// 定时任务日志，写入task_开头的日志文件
Log::task("开始异步执行定时任务".json_encode([
              'class' => $this->className,
              'method' => $this->method,
              'taskName' => $this->taskName
          ]));
````
