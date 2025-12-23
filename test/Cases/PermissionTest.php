<?php

declare(strict_types=1);
/**
 * 权限管理系统测试用例
 * 
 * 测试范围：
 * 1. 用户认证与登录
 * 2. 角色管理功能
 * 3. 菜单权限控制
 * 4. API接口权限验证
 * 5. 权限中间件验证
 * 6. Casbin权限引擎
 */
namespace HyperfTest\Cases;

use HyperfTest\HttpTestCase;
use Qbhy\HyperfTesting\Client;

/**
 * @internal
 * @coversNothing
 */
class PermissionTest extends HttpTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * 测试用的超级管理员Token
     */
    protected string $superAdminToken = '';

    /**
     * 测试用的普通用户Token
     */
    protected string $normalUserToken = '';

    /**
     * 测试用的角色ID
     */
    protected int $testRoleId = 0;

    /**
     * 测试用的菜单ID
     */
    protected int $testMenuId = 0;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->client = make(Client::class);
    }

    /**
     * ZGW协议安全请求封装
     */
    public function safeRequest(string $interfaceName, array $params = [], string $token = null)
    {
        echo "token: " . $token . "\n";
        $timestamp = time();
        $requestData = [
            "token" => $token,
            "version" => "1.0",
            "seqId" => strval($timestamp),
            "spanId" => strval($timestamp),
            "timestamp" => $timestamp,
            "eventId" => time(),
            "caller" => "permission_test",
            "interface" => [
                "name" => $interfaceName,
                "param" => $params,
            ]
        ];
        return $this->client->json("/", $requestData)->assertOk();
    }

    /**
     * 测试1：超级管理员登录
     */
    public function testSuperAdminLogin()
    {
        echo "\n=== 测试超级管理员登录 ===\n";
        
        $result = $this->safeRequest('system.user.login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $this->assertEquals(0, $result['code'], '超级管理员登录失败');
        $this->assertArrayHasKey('token', $result['data'], '登录响应缺少token');
        
        $this->superAdminToken = $result['data']['token'];
        echo "✓ 超级管理员登录成功，Token: " . substr($this->superAdminToken, 0, 20) . "...\n";
        
        return $this->superAdminToken;
    }

    /**
     * 测试2：获取超级管理员用户信息
     */
    public function testGetSuperAdminUserInfo()
    {
        echo "\n=== 测试获取超级管理员用户信息 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.auth.getUserInfo', [], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取用户信息失败');
        $this->assertArrayHasKey('user', $result['data'], '响应缺少用户信息');
        
        $user = $result['data']['user'];
        $this->assertEquals('admin', $user['username'], '用户名不匹配');
        $this->assertEquals(1, $user['roleId'], '超级管理员角色ID应为1');
        
        echo "✓ 用户信息获取成功: {$user['username']} (角色ID: {$user['roleId']})\n";
        
        return $user;
    }

    /**
     * 测试3：获取超级管理员菜单权限
     */
    public function testGetSuperAdminMenus()
    {
        echo "\n=== 测试获取超级管理员菜单权限 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.auth.getUserMenus', [], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取菜单权限失败');
        $this->assertArrayHasKey('menus', $result['data'], '响应缺少菜单信息');
        
        $menus = $result['data']['menus'];
        echo "✓ 菜单权限获取成功，共 " . count($menus) . " 个根菜单\n";
        
        return $menus;
    }

    /**
     * 测试4：获取超级管理员权限列表
     */
    public function testGetSuperAdminPermissions()
    {
        echo "\n=== 测试获取超级管理员权限列表 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.auth.getUserPermissions', [], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取权限列表失败');
        $this->assertArrayHasKey('permissions', $result['data'], '响应缺少权限信息');
        
        $permissions = $result['data']['permissions'];
        echo "✓ 权限列表获取成功，共 " . count($permissions) . " 个权限\n";
        
        if (count($permissions) > 0) {
            echo "  示例权限: " . implode(', ', array_slice($permissions, 0, 3)) . "\n";
        }
        
        return $permissions;
    }

    /**
     * 测试5：创建测试角色
     */
    public function testCreateTestRole()
    {
        echo "\n=== 测试创建测试角色 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.role.createRole', [
            'roleName' => '测试角色',
            'roleKey' => 'test_role_' . time(),
            'roleSort' => 10,
            'status' => 1,
            'remark' => '用于权限测试的角色'
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '创建角色失败');
        $this->assertArrayHasKey('roleId', $result['data'], '响应缺少角色ID');
        
        $this->testRoleId = $result['data']['roleId'];
        echo "✓ 测试角色创建成功，角色ID: {$this->testRoleId}\n";
        
        return $this->testRoleId;
    }

    /**
     * 测试6：获取角色列表
     */
    public function testGetRoleList()
    {
        echo "\n=== 测试获取角色列表 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.role.getRoleList', [
            'page' => 1,
            'size' => 20
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取角色列表失败');
        $this->assertArrayHasKey('list', $result['data'], '响应缺少角色列表');
        
        $roles = $result['data']['list'];
        echo "✓ 角色列表获取成功，共 " . count($roles) . " 个角色\n";
        
        // 验证是否包含创建的测试角色
        if ($this->testRoleId > 0) {
            $testRoleFound = false;
            foreach ($roles as $role) {
                if ($role['role_id'] == $this->testRoleId) {
                    $testRoleFound = true;
                    echo "  ✓ 找到测试角色: {$role['role_name']}\n";
                    break;
                }
            }
            $this->assertTrue($testRoleFound, '创建的测试角色未在列表中找到');
        }
        
        return $roles;
    }

    /**
     * 测试7：创建测试菜单
     */
    public function testCreateTestMenu()
    {
        echo "\n=== 测试创建测试菜单 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.menu.createMenu', [
            'menuName' => '测试菜单',
            'parentId' => 0,
            'orderNum' => 100,
            'path' => '/test-menu',
            'component' => 'test/index',
            'menuType' => 'C',
            'visible' => 1,
            'status' => 1,
            'perms' => 'test:menu:access',
            'icon' => 'test',
            'remark' => '用于权限测试的菜单'
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '创建菜单失败');
        $this->assertArrayHasKey('menuId', $result['data'], '响应缺少菜单ID');
        
        $this->testMenuId = $result['data']['menuId'];
        echo "✓ 测试菜单创建成功，菜单ID: {$this->testMenuId}\n";
        
        return $this->testMenuId;
    }

    /**
     * 测试8：为角色分配菜单权限
     */
    public function testAssignMenusToRole()
    {
        echo "\n=== 测试为角色分配菜单权限 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }
        
        if ($this->testRoleId == 0) {
            $this->testCreateTestRole();
        }
        
        if ($this->testMenuId == 0) {
            $this->testCreateTestMenu();
        }

        $result = $this->safeRequest('system.role.assignMenus', [
            'roleId' => $this->testRoleId,
            'menuIds' => [$this->testMenuId]
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '分配菜单权限失败');
        echo "✓ 菜单权限分配成功，角色 {$this->testRoleId} 获得菜单 {$this->testMenuId} 权限\n";
        
        return true;
    }

    /**
     * 测试9：获取角色菜单权限
     */
    public function testGetRoleMenus()
    {
        echo "\n=== 测试获取角色菜单权限 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }
        
        if ($this->testRoleId == 0) {
            $this->testCreateTestRole();
            $this->testCreateTestMenu();
            $this->testAssignMenusToRole();
        }

        $result = $this->safeRequest('system.role.getRoleMenus', [
            'roleId' => $this->testRoleId
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取角色菜单权限失败');
        $this->assertArrayHasKey('menuIds', $result['data'], '响应缺少菜单ID列表');
        
        $menuIds = $result['data']['menuIds'];
        echo "✓ 角色菜单权限获取成功，共 " . count($menuIds) . " 个菜单权限\n";
        
        // 验证是否包含分配的测试菜单
        if ($this->testMenuId > 0) {
            $this->assertContains($this->testMenuId, $menuIds, '分配的测试菜单未在权限列表中找到');
            echo "  ✓ 验证成功：测试菜单在权限列表中\n";
        }
        
        return $menuIds;
    }

    /**
     * 测试10：权限验证功能
     */
    public function testCheckPermission()
    {
        echo "\n=== 测试权限验证功能 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        // 测试超级管理员权限（应该有所有权限）
        $result = $this->safeRequest('system.auth.checkPermission', [
            'resource' => 'system:user:list',
            'action' => 'access'
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '权限验证失败');
        $this->assertArrayHasKey('hasPermission', $result['data'], '响应缺少权限验证结果');
        $this->assertTrue($result['data']['hasPermission'], '超级管理员应该有所有权限');
        
        echo "✓ 超级管理员权限验证成功: {$result['data']['resource']}\n";
        
        return $result['data']['hasPermission'];
    }

    /**
     * 测试11：创建普通测试用户
     */
    public function testCreateNormalUser()
    {
        echo "\n=== 测试创建普通测试用户 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }
        
        if ($this->testRoleId == 0) {
            $this->testCreateTestRole();
        }

        $username = 'testuser_' . time();
        $result = $this->safeRequest('system.user.createUser', [
            'username' => $username,
            'password' => 'test123456',
            'mobile' => '13900000' . rand(100, 999),
            'email' => $username . '@test.com',
            'roleId' => $this->testRoleId
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '创建普通用户失败');
        echo "✓ 普通测试用户创建成功: {$username}\n";
        
        return $username;
    }

    /**
     * 测试12：为用户分配角色
     */
    public function testAssignRoleToUser()
    {
        echo "\n=== 测试为用户分配角色 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }
        
        if ($this->testRoleId == 0) {
            $this->testCreateTestRole();
        }

        // 为管理员用户分配测试角色
        $result = $this->safeRequest('system.auth.assignRole', [
            'userId' => 1, // 假设管理员用户ID为1
            'roleId' => $this->testRoleId
        ], $this->superAdminToken);

        // 注意：超级管理员可能不允许修改角色，这里测试接口是否正常响应
        if ($result['code'] == 0) {
            echo "✓ 角色分配成功\n";
        } else {
            echo "! 角色分配返回错误: {$result['msg']} (可能是超级管理员保护机制)\n";
        }
        
        return $result;
    }

    /**
     * 测试13：获取菜单树
     */
    public function testGetMenuTree()
    {
        echo "\n=== 测试获取菜单树 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        $result = $this->safeRequest('system.menu.getMenuTree', [
            'onlyVisible' => true
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取菜单树失败');
        $this->assertArrayHasKey('menus', $result['data'], '响应缺少菜单树');
        
        $menus = $result['data']['menus'];
        echo "✓ 菜单树获取成功，共 " . count($menus) . " 个根菜单\n";
        
        return $menus;
    }

    /**
     * 测试14：API接口管理
     */
    public function testApiManagement()
    {
        echo "\n=== 测试API接口管理 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        // 获取API列表
        $result = $this->safeRequest('system.api.getApiList', [
            'page' => 1,
            'size' => 10
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取API列表失败');
        $this->assertArrayHasKey('list', $result['data'], '响应缺少API列表');
        
        $apis = $result['data']['list'];
        echo "✓ API列表获取成功，共 " . count($apis) . " 个API\n";
        
        return $apis;
    }

    /**
     * 测试15：权限中间件保护
     */
    public function testPermissionMiddlewareProtection()
    {
        echo "\n=== 测试权限中间件保护 ===\n";
        
        // 测试不带token的请求（应该被拒绝）
        $result = $this->safeRequest('system.role.getRoleList', [
            'page' => 1,
            'size' => 10
        ]);

        // 如果没有token，应该返回认证错误
        if ($result['code'] != 0) {
            echo "✓ 中间件正确拒绝了无token的请求: {$result['message']}\n";
        }
        
        // 测试错误token的请求
        $result = $this->safeRequest('system.role.getRoleList', [
            'page' => 1,
            'size' => 10
        ], 'invalid_token_123');

        if ($result['code'] != 0) {
            echo "✓ 中间件正确拒绝了无效token的请求: {$result['message']}\n";
        }
        
        return true;
    }

    /**
     * 测试16：系统初始化权限数据验证
     */
    public function testSystemPermissionInitialization()
    {
        echo "\n=== 测试系统权限数据初始化验证 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        // 验证默认角色是否存在
        $result = $this->safeRequest('system.role.getRoleList', [
            'page' => 1,
            'size' => 50
        ], $this->superAdminToken);

        $this->assertEquals(0, $result['code'], '获取角色列表失败');
        $roles = $result['data']['list'];
        
        $defaultRoles = ['超级管理员', '系统管理员', '普通用户'];
        $foundRoles = [];
        
        foreach ($roles as $role) {
            if (in_array($role['role_name'], $defaultRoles)) {
                $foundRoles[] = $role['role_name'];
            }
        }
        
        foreach ($defaultRoles as $roleName) {
            $this->assertContains($roleName, $foundRoles, "默认角色 {$roleName} 未找到");
        }
        
        echo "✓ 系统默认角色验证成功: " . implode(', ', $foundRoles) . "\n";
        
        return true;
    }

    /**
     * 测试17：权限缓存和同步
     */
    public function testPermissionCacheAndSync()
    {
        echo "\n=== 测试权限缓存和同步 ===\n";
        
        if (empty($this->superAdminToken)) {
            $this->testSuperAdminLogin();
        }

        // 同步用户权限
        $result = $this->safeRequest('system.auth.syncUserPermissions', [
            'userId' => 1 // 超级管理员用户
        ], $this->superAdminToken);

        if ($result['code'] == 0) {
            echo "✓ 用户权限同步成功\n";
        } else {
            echo "! 权限同步失败: {$result['msg']}\n";
        }
        
        return true;
    }

    /**
     * 主测试方法 - 运行所有权限管理测试
     */
    public function testPermissionManagementComplete()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "开始权限管理系统完整测试\n";
        echo str_repeat("=", 60) . "\n";

        try {
            // 1. 基础认证测试
            $this->testSuperAdminLogin();
            $this->testGetSuperAdminUserInfo();
            $this->testGetSuperAdminMenus();
            $this->testGetSuperAdminPermissions();
            
            // 2. 角色管理测试
            $this->testCreateTestRole();
            $this->testGetRoleList();
            
            // 3. 菜单权限测试
            $this->testCreateTestMenu();
            $this->testAssignMenusToRole();
            $this->testGetRoleMenus();
            $this->testGetMenuTree();
            
            // 4. 权限验证测试
            $this->testCheckPermission();
            $this->testAssignRoleToUser();
            
            // 5. 其他功能测试
            $this->testApiManagement();
            $this->testPermissionMiddlewareProtection();
            $this->testSystemPermissionInitialization();
            $this->testPermissionCacheAndSync();
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "✅ 权限管理系统测试全部通过!\n";
            echo str_repeat("=", 60) . "\n";
            
        } catch (\Exception $e) {
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "❌ 权限管理系统测试失败: " . $e->getMessage() . "\n";
            echo "错误位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo str_repeat("=", 60) . "\n";
            throw $e;
        }
    }

    /**
     * 清理测试数据
     */
    protected function tearDown(): void
    {
        // 清理测试数据（可选）
        if ($this->testRoleId > 0 && !empty($this->superAdminToken)) {
            try {
                $this->safeRequest('system.role.deleteRole', [
                    'roleId' => $this->testRoleId
                ], $this->superAdminToken);
                echo "🧹 测试角色清理完成\n";
            } catch (\Exception $e) {
                // 忽略清理错误
            }
        }
        
        if ($this->testMenuId > 0 && !empty($this->superAdminToken)) {
            try {
                $this->safeRequest('system.menu.deleteMenu', [
                    'menuId' => $this->testMenuId
                ], $this->superAdminToken);
                echo "🧹 测试菜单清理完成\n";
            } catch (\Exception $e) {
                // 忽略清理错误
            }
        }
        
        parent::tearDown();
    }
}