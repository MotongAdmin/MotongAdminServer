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

namespace App\Command;

use App\Model\SysMenu;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use ZYProSoft\Log\Log;

/**
 * @Command
 */
class InitSystemMenuCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('init:system-menu');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('初始化系统菜单数据');
    }

    public function handle()
    {
        $this->line('开始初始化系统菜单数据...', 'info');
        
        try {
            Db::beginTransaction();
            
            // 初始化菜单数据
            $menus = $this->initMenus();
            $this->line('菜单初始化完成，共创建 ' . count($menus) . ' 个菜单', 'info');

            Db::commit();
            $this->line('系统菜单数据初始化成功！', 'info');
            
        } catch (\Throwable $e) {
            Db::rollBack();
            $this->error('系统菜单数据初始化失败：' . $e->getMessage());
            Log::error('系统菜单数据初始化失败'.json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
        }
    }
    
    /**
     * 初始化菜单数据（基于数据库实际数据）
     */
    private function initMenus(): array
    {
        // 基于数据库现有数据的菜单初始化数据
        $menus = [
            // 系统管理 (目录)
            [
                'menu_id' => 1,
                'menu_name' => '系统管理',
                'parent_id' => 0,
                'order_num' => 1,
                'path' => '/system',
                'component' => 'Layout',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'M',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:main',
                'icon' => 'el-icon-setting',
                'remark' => '系统管理目录'
            ],
            
            // 用户管理 (菜单)
            [
                'menu_id' => 2,
                'menu_name' => '用户管理',
                'parent_id' => 1,
                'order_num' => 1,
                'path' => 'user',
                'component' => 'system/user/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:user:list',
                'icon' => 'user',
                'remark' => '用户管理菜单'
            ],
            
            // 用户管理按钮权限
            [
                'menu_id' => 3,
                'menu_name' => '用户查询',
                'parent_id' => 2,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:user:query',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 4,
                'menu_name' => '用户新增',
                'parent_id' => 2,
                'order_num' => 2,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:user:add',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 5,
                'menu_name' => '用户修改',
                'parent_id' => 2,
                'order_num' => 3,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:user:edit',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 6,
                'menu_name' => '用户删除',
                'parent_id' => 2,
                'order_num' => 4,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:user:remove',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 7,
                'menu_name' => '重置密码',
                'parent_id' => 2,
                'order_num' => 5,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:user:resetPwd',
                'icon' => '#',
                'remark' => ''
            ],
            
            // 角色管理 (菜单)
            [
                'menu_id' => 8,
                'menu_name' => '角色管理',
                'parent_id' => 1,
                'order_num' => 2,
                'path' => 'role',
                'component' => 'system/role/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:role:list',
                'icon' => 'el-icon-s-custom',
                'remark' => '角色管理菜单'
            ],
            
            // 角色管理按钮权限
            [
                'menu_id' => 9,
                'menu_name' => '角色查询',
                'parent_id' => 8,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:role:query',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 10,
                'menu_name' => '角色新增',
                'parent_id' => 8,
                'order_num' => 2,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:role:add',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 11,
                'menu_name' => '角色修改',
                'parent_id' => 8,
                'order_num' => 3,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:role:edit',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 12,
                'menu_name' => '角色删除',
                'parent_id' => 8,
                'order_num' => 4,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:role:remove',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 13,
                'menu_name' => '菜单权限',
                'parent_id' => 8,
                'order_num' => 5,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:role:menu',
                'icon' => '#',
                'remark' => ''
            ],
            
            // 菜单管理 (菜单)
            [
                'menu_id' => 14,
                'menu_name' => '菜单管理',
                'parent_id' => 1,
                'order_num' => 5,
                'path' => 'menu',
                'component' => 'system/menu/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:menu:list',
                'icon' => 'el-icon-s-order',
                'remark' => '菜单管理菜单'
            ],
            
            // 菜单管理按钮权限
            [
                'menu_id' => 15,
                'menu_name' => '菜单查询',
                'parent_id' => 14,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:menu:query',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 16,
                'menu_name' => '菜单新增',
                'parent_id' => 14,
                'order_num' => 2,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:menu:add',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 17,
                'menu_name' => '菜单修改',
                'parent_id' => 14,
                'order_num' => 3,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:menu:edit',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 18,
                'menu_name' => '菜单删除',
                'parent_id' => 14,
                'order_num' => 4,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:menu:remove',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 19,
                'menu_name' => '接口分配',
                'parent_id' => 14,
                'order_num' => 5,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:menu:assignApis',
                'icon' => '#',
                'remark' => ''
            ],
            
            // API管理 (菜单)
            [
                'menu_id' => 20,
                'menu_name' => 'API管理',
                'parent_id' => 1,
                'order_num' => 6,
                'path' => 'api',
                'component' => 'system/api/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:api:list',
                'icon' => 'el-icon-s-promotion',
                'remark' => 'API管理菜单'
            ],
            
            // API管理按钮权限
            [
                'menu_id' => 21,
                'menu_name' => 'API查询',
                'parent_id' => 20,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:api:query',
                'icon' => '#',
                'remark' => ''
            ],
            
            // 操作日志 (菜单)
            [
                'menu_id' => 22,
                'menu_name' => '操作日志',
                'parent_id' => 1,
                'order_num' => 11,
                'path' => 'log',
                'component' => 'system/log/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:log:list',
                'icon' => 'el-icon-s-claim',
                'remark' => '操作日志菜单'
            ],
            
            // 操作日志按钮权限
            [
                'menu_id' => 23,
                'menu_name' => '日志查询',
                'parent_id' => 22,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:log:query',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 24,
                'menu_name' => '日志删除',
                'parent_id' => 22,
                'order_num' => 2,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:log:delete',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 25,
                'menu_name' => '日志清空',
                'parent_id' => 22,
                'order_num' => 3,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:log:clear',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 26,
                'menu_name' => '日志详情',
                'parent_id' => 22,
                'order_num' => 4,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:log:detail',
                'icon' => '#',
                'remark' => ''
            ],
            
            // 字典管理 (菜单)
            [
                'menu_id' => 27,
                'menu_name' => '字典管理',
                'parent_id' => 1,
                'order_num' => 7,
                'path' => 'dict',
                'component' => 'system/dict/index',
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:dict:list',
                'icon' => 'el-icon-s-operation',
                'remark' => '字典管理'
            ],
            
            // 系统配置 (菜单)
            [
                'menu_id' => 28,
                'menu_name' => '系统配置',
                'parent_id' => 1,
                'order_num' => 8,
                'path' => 'config',
                'component' => 'system/config/index',
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:config',
                'icon' => 'el-icon-s-tools',
                'remark' => null
            ],
            
            // 字典管理按钮权限
            [
                'menu_id' => 29,
                'menu_name' => '添加字典',
                'parent_id' => 27,
                'order_num' => 1,
                'path' => null,
                'component' => null,
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 0,
                'status' => 1,
                'perms' => 'system:dict:add',
                'icon' => null,
                'remark' => null
            ],
            [
                'menu_id' => 30,
                'menu_name' => '编辑字典',
                'parent_id' => 27,
                'order_num' => 2,
                'path' => null,
                'component' => null,
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 0,
                'status' => 1,
                'perms' => 'system:dict:edit',
                'icon' => null,
                'remark' => null
            ],
            [
                'menu_id' => 31,
                'menu_name' => '删除字典',
                'parent_id' => 27,
                'order_num' => 3,
                'path' => null,
                'component' => null,
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 0,
                'status' => 1,
                'perms' => 'system:dict:remove',
                'icon' => null,
                'remark' => null
            ],
            
            // 系统配置按钮权限
            [
                'menu_id' => 32,
                'menu_name' => '添加配置',
                'parent_id' => 28,
                'order_num' => 1,
                'path' => null,
                'component' => null,
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 0,
                'status' => 1,
                'perms' => 'system:config:add',
                'icon' => null,
                'remark' => null
            ],
            [
                'menu_id' => 33,
                'menu_name' => '编辑配置',
                'parent_id' => 28,
                'order_num' => 2,
                'path' => null,
                'component' => null,
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 0,
                'status' => 1,
                'perms' => 'system:config:edit',
                'icon' => null,
                'remark' => null
            ],
            [
                'menu_id' => 34,
                'menu_name' => '删除配置',
                'parent_id' => 28,
                'order_num' => 3,
                'path' => null,
                'component' => null,
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 0,
                'status' => 1,
                'perms' => 'system:config:remove',
                'icon' => null,
                'remark' => null
            ],
            
            // 定时任务 (菜单)
            [
                'menu_id' => 35,
                'menu_name' => '定时任务',
                'parent_id' => 1,
                'order_num' => 10,
                'path' => 'crontab',
                'component' => 'system/crontab/index',
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:crontab',
                'icon' => 'el-icon-warning',
                'remark' => null
            ],
            
            // 平台配置 (菜单)
            [
                'menu_id' => 36,
                'menu_name' => '平台配置',
                'parent_id' => 1,
                'order_num' => 9,
                'path' => 'platform',
                'component' => 'system/platform/index',
                'query' => '',
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:platform',
                'icon' => 'el-icon-rank',
                'remark' => null
            ],

            // 部门管理 (菜单)
            [
                'menu_id' => 37,
                'menu_name' => '部门管理',
                'parent_id' => 1, // 系统管理下
                'order_num' => 3,
                'path' => 'dept',
                'component' => 'system/dept/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:dept:list',
                'icon' => 'tree',
                'remark' => '部门管理菜单'
            ],
            
            // 部门管理按钮权限
            [
                'menu_id' => 38,
                'menu_name' => '部门查询',
                'parent_id' => 37,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:dept:query',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 39,
                'menu_name' => '部门新增',
                'parent_id' => 37,
                'order_num' => 2,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:dept:add',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 40,
                'menu_name' => '部门修改',
                'parent_id' => 37,
                'order_num' => 3,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:dept:edit',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 41,
                'menu_name' => '部门删除',
                'parent_id' => 37,
                'order_num' => 4,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:dept:delete',
                'icon' => '#',
                'remark' => ''
            ],
            
            // 岗位管理 (菜单)
            [
                'menu_id' => 42,
                'menu_name' => '岗位管理',
                'parent_id' => 1, // 系统管理下
                'order_num' => 4,
                'path' => 'post',
                'component' => 'system/post/index',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 1,
                'menu_type' => 'C',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:post:list',
                'icon' => 'example',
                'remark' => '岗位管理菜单'
            ],
            
            // 岗位管理按钮权限
            [
                'menu_id' => 43,
                'menu_name' => '岗位查询',
                'parent_id' => 42,
                'order_num' => 1,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:post:query',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 44,
                'menu_name' => '岗位新增',
                'parent_id' => 42,
                'order_num' => 2,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:post:add',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 45,
                'menu_name' => '岗位修改',
                'parent_id' => 42,
                'order_num' => 3,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:post:edit',
                'icon' => '#',
                'remark' => ''
            ],
            [
                'menu_id' => 46,
                'menu_name' => '岗位删除',
                'parent_id' => 42,
                'order_num' => 4,
                'path' => '',
                'component' => '',
                'query' => null,
                'is_frame' => 1,
                'is_cache' => 0,
                'menu_type' => 'F',
                'visible' => 1,
                'status' => 1,
                'perms' => 'system:post:delete',
                'icon' => '#',
                'remark' => ''
            ]
        ];
        
        $created = [];
        foreach ($menus as $menuData) {
            $menu = SysMenu::create($menuData);
            $created[] = $menu->menu_id;
        }

        return $created;
    }
}
