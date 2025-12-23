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
use App\Model\SysApi;
use App\Model\SysMenuApi;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use ZYProSoft\Log\Log;

/**
 * @Command
 */
class InitSystemMenuApiCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('init:system-menu-api');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('初始化系统菜单与API的精确绑定关系');
    }

    public function handle()
    {
        $this->line('开始初始化菜单与API的精确绑定关系...', 'info');
        
        try {
            Db::beginTransaction();
            
            // 清除现有的菜单API绑定关系
            SysMenuApi::query()->delete();
            $this->line('已清除现有的菜单API绑定关系', 'info');
            
            // 初始化精确的菜单API绑定
            $bindings = $this->initMenuApiBindings();
            $this->line('菜单API绑定完成，共创建 ' . count($bindings) . ' 个精确绑定关系', 'info');

            Db::commit();
            $this->line('菜单与API精确绑定关系初始化成功！', 'info');
            
        } catch (\Throwable $e) {
            Db::rollBack();
            $this->error('菜单与API绑定关系初始化失败：' . $e->getMessage());
            Log::error('菜单与API绑定关系初始化失败'.json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
        }
    }
    
    /**
     * 初始化菜单与API的精确绑定关系
     */
    private function initMenuApiBindings(): array
    {
        // 权限标识到API名称的精确映射关系
        $permissionToApiMapping = [
            // ======================== 用户管理模块 ========================
            'system:user:query' => [
                'system.user.getUserList',
                'system.role.getAllRoles',
                'system.role.getAssignableRoles',
                'system.dept.getDeptListByLoginUserRole',
                'system.post.getAllPosts'
            ],
            'system:user:add' => [
                'system.user.createUser',
                'system.dept.getDeptListByLoginUserRole',
                'system.post.getAllPosts',
                'system.role.getAssignableRoles'
            ],
            'system:user:edit' => [
                'system.user.updateUser',
                'system.user.updatePassword', 
                'system.user.updateProfile',
                'system.user.toggleStatus',
                'system.dept.getDeptListByLoginUserRole',
                'system.post.getAllPosts',
                'system.role.getAssignableRoles'
            ],
            'system:user:remove' => [
                'system.user.toggleStatus' // 目前使用状态切换代替删除
            ],
            'system:user:resetPwd' => [
                'system.user.updatePassword'
            ],

            // ======================== 角色管理模块 ========================
            'system:role:query' => [
                'system.role.getRoleList',
                'system.role.getRoleDetail'
            ],
            'system:role:add' => [
                'system.role.createRole'
            ],
            'system:role:edit' => [
                'system.role.updateRole'
            ],
            'system:role:remove' => [
                'system.role.deleteRole'
            ],
            'system:role:menu' => [
                'system.role.getRoleMenus',
                'system.role.setRoleMenus',
                'system.menu.getAssignableMenuTree'
            ],

            // ======================== 菜单管理模块 ========================
            'system:menu:query' => [
                'system.menu.getMenuTree',
                'system.menu.getMenuList',
                'system.menu.getMenuDetail'
            ],
            'system:menu:add' => [
                'system.menu.createMenu',
                'system.menu.getParentMenuOptions'
            ],
            'system:menu:edit' => [
                'system.menu.updateMenu',
                'system.menu.toggleStatus',
                'system.menu.updateMenuSort',
                'system.menu.getParentMenuOptions'
            ],
            'system:menu:remove' => [
                'system.menu.deleteMenu'
            ],
            'system:menu:assignApis' => [
                'system.menu.getMenuApis',
                'system.menu.assignApis'
            ],

            // ======================== API管理模块 ========================
            'system:api:query' => [
                'system.api.getApiList',
                'system.api.getApiGroups',
                'system.api.getApiDetail'
            ],

            // ======================== 部门管理模块 ========================
            'system:dept:query' => [
                'system.dept.getDeptList',
                'system.dept.getDeptTree'
            ],
            'system:dept:add' => [
                'system.dept.createDept'
            ],
            'system:dept:edit' => [
                'system.dept.updateDept',
                'system.dept.toggleStatus'
            ],
            'system:dept:delete' => [
                'system.dept.deleteDept'
            ],

            // ======================== 岗位管理模块 ========================
            'system:post:query' => [
                'system.post.getPostList',
                'system.post.getAllPosts'
            ],
            'system:post:add' => [
                'system.post.createPost'
            ],
            'system:post:edit' => [
                'system.post.updatePost',
                'system.post.toggleStatus'
            ],
            'system:post:delete' => [
                'system.post.deletePost'
            ],

            // ======================== 字典管理模块 ========================
            'system:dict:add' => [
                'system.dictType.createDictType',
                'system.dictData.createDictData',
                'system.dictType.getAllTables',
                'system.dictType.getTableFields'
            ],
            'system:dict:edit' => [
                'system.dictType.updateDictType',
                'system.dictData.updateDictData',
                'system.dictType.bindField',
                'system.dictType.unbindField'
            ],
            'system:dict:remove' => [
                'system.dictType.deleteDictType',
                'system.dictData.deleteDictData'
            ],

            // ======================== 系统配置模块 ========================
            'system:config:add' => [
                'system.config.createConfig'
            ],
            'system:config:edit' => [
                'system.config.updateConfig'
            ],
            'system:config:remove' => [
                'system.config.deleteConfig'
            ],

            // ======================== 操作日志模块 ========================
            'system:log:query' => [
                'system.operationLog.list'
            ],
            'system:log:detail' => [
                'system.operationLog.detail'
            ],
            'system:log:delete' => [
                'system.operationLog.delete'
            ],
            'system:log:clear' => [
                'system.operationLog.clear'
            ]
        ];

        // 页面级权限的API绑定（这些权限通常包含查询和基础功能API）
        $pagePermissionToApiMapping = [
            'system:user:list' => [
                'system.user.getUserList',
                'system.auth.getUserInfo',
                'system.auth.getUserMenus',
                'system.auth.getUserPermissions'
            ],
            'system:role:list' => [
                'system.role.getRoleList'
            ],
            'system:menu:list' => [
                'system.menu.getMenuTree',
                'system.menu.getMenuList'
            ],
            'system:api:list' => [
                'system.api.getApiList',
                'system.api.getApiGroups'
            ],
            'system:dept:list' => [
                'system.dept.getDeptList',
                'system.dept.getDeptTree'
            ],
            'system:post:list' => [
                'system.post.getPostList',
                'system.post.getAllPosts'
            ],
            'system:dict:list' => [
                'system.dictType.getDictTypeList',
                'system.dictData.getDictDataList',
                'system.dictData.getDictDataByType',
                'system.dictType.getAllDictTypes',
                'system.dictType.getBindFields',
                'system.dictType.getFieldDict'
            ],
            'system:config' => [
                'system.config.getConfigList',
                'system.config.getConfigByKey',
                'system.config.getConfigByKeys'
            ],
            'system:log:list' => [
                'system.operationLog.list'
            ],
            'system:crontab' => [
                'system.crontab.getAllCrontabs',
                'system.crontab.executeTask',
                'system.crontab.executeTaskByName'
            ],
            'system:platform' => [
                'system.platform.getList',
                'system.platform.getDetail',
                // 存储相关
                'system.storage.getImageUploadToken',
                'system.storage.getVideoUploadToken', 
                'system.storage.getAudioUploadToken',
                'system.storage.getFileUploadToken',
                'system.storage.uploadToLocal',
                // 配置管理相关
                'system.storageConfig.getList',
                'system.storageConfig.getDetail',
                'system.storageConfig.create',
                'system.storageConfig.update',
                'system.storageConfig.delete',
                'system.smsConfig.getList',
                'system.smsConfig.getDetail',
                'system.smsConfig.create',
                'system.smsConfig.update',
                'system.smsConfig.delete',
                'system.paymentConfig.getList',
                'system.paymentConfig.getDetail',
                'system.paymentConfig.create',
                'system.paymentConfig.update',
                'system.paymentConfig.delete',
                'system.miniappConfig.getList',
                'system.miniappConfig.getDetail',
                'system.miniappConfig.create',
                'system.miniappConfig.update',
                'system.miniappConfig.delete',
                'system.endpointConfig.getList',
                'system.endpointConfig.getDetail',
                'system.endpointConfig.create',
                'system.endpointConfig.update',
                'system.endpointConfig.delete'
            ]
        ];

        // 合并所有权限映射
        $allPermissionMappings = array_merge($permissionToApiMapping, $pagePermissionToApiMapping);
        
        $created = [];
        
        // 获取所有菜单
        $menus = SysMenu::where('status', 1)->get();
        
        // 获取所有API
        $apis = SysApi::where('status', 1)->get();
        $apiNameToIdMap = [];
        foreach ($apis as $api) {
            $apiNameToIdMap[$api->api_name] = $api->api_id;
        }
        
        foreach ($menus as $menu) {
            if (empty($menu->perms)) {
                continue;
            }
            
            $permission = $menu->perms;
            
            // 检查是否有对应的API映射
            if (isset($allPermissionMappings[$permission])) {
                $apiNames = $allPermissionMappings[$permission];
                
                foreach ($apiNames as $apiName) {
                    if (isset($apiNameToIdMap[$apiName])) {
                        $apiId = $apiNameToIdMap[$apiName];
                        
                        // 创建菜单API绑定关系
                        SysMenuApi::create([
                            'menu_id' => $menu->menu_id,
                            'api_id' => $apiId,
                        ]);
                        
                        $created[] = [
                            'menu_id' => $menu->menu_id,
                            'menu_name' => $menu->menu_name,
                            'permission' => $permission,
                            'api_id' => $apiId,
                            'api_name' => $apiName,
                        ];
                    } else {
                        $this->warn("API不存在: {$apiName} (权限: {$permission})");
                    }
                }
            } else {
                $this->warn("未找到权限映射: {$permission} (菜单: {$menu->menu_name})");
            }
        }
        
        return $created;
    }
}
