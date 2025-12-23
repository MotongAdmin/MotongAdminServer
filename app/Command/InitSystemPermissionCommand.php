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

use App\Model\SysRole;
use App\Model\SysMenu;
use App\Model\SysRoleMenu;
use App\Model\SysApi;
use App\Model\SysMenuApi;
use App\Model\SysPermission;
use App\Constants\Constants;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use ZYProSoft\Log\Log;

/**
 * @Command
 */
class InitSystemPermissionCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('init:system-permission');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('初始化系统权限数据');
    }

    public function handle()
    {
        $this->line('开始初始化系统权限数据...', 'info');
        
        try {
            Db::beginTransaction();
            
            // 初始化角色数据
            $roles = $this->initRoles();
            $this->line('角色初始化完成，共创建 ' . count($roles) . ' 个角色', 'info');
            
            // 初始化API数据
            $apis = $this->initApis();
            $this->line('API初始化完成，共创建 ' . count($apis) . ' 个API', 'info');
            
            // 初始化菜单和API的绑定关系
            $this->call('init:system-menu-api');

            Db::commit();
            $this->line('系统权限数据初始化成功！', 'info');
            
        } catch (\Throwable $e) {
            Db::rollBack();
            $this->error('系统权限数据初始化失败：' . $e->getMessage());
            Log::error('系统权限数据初始化失败'.json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
        }
    }
    
    /**
     * 初始化角色数据
     */
    private function initRoles(): array
    {
        $roles = [
            [
                'role_id' => 1,
                'role_name' => '超级管理员',
                'role_key' => 'super_admin',
                'role_sort' => 1,
                'status' => 1,
                'del_flag' => 0,
                'remark' => '超级管理员，拥有所有权限'
            ]
        ];

        $created = [];
        foreach ($roles as $roleData) {
            $role = SysRole::firstOrCreate(
                ['role_id' => $roleData['role_id']],
                $roleData
            );
            $created[] = $role->role_id;
        }

        return $created;
    }
    
    /**
     * 初始化API数据
     */
    private function initApis(): array
    {
        // 清除现有的API数据
        SysApi::query()->delete();
        
        // 基于数据库现有数据的API初始化数据
        $apis = [
            // API管理分组
            ['api_name' => 'system.api.getApiList', 'api_path' => '/system/api/getApiList', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '获取接口列表', 'status' => 1],
            ['api_name' => 'system.api.createApi', 'api_path' => '/system/api/createApi', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '创建新接口', 'status' => 1],
            ['api_name' => 'system.api.updateApi', 'api_path' => '/system/api/updateApi', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '更新接口信息', 'status' => 1],
            ['api_name' => 'system.api.deleteApi', 'api_path' => '/system/api/deleteApi', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '删除接口', 'status' => 1],
            ['api_name' => 'system.api.getApiDetail', 'api_path' => '/system/api/getApiDetail', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '获取接口详情', 'status' => 1],
            ['api_name' => 'system.api.getApiGroups', 'api_path' => '/system/api/getApiGroups', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '获取接口分组', 'status' => 1],
            ['api_name' => 'system.api.toggleStatus', 'api_path' => '/system/api/toggleStatus', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '切换接口状态', 'status' => 1],
            ['api_name' => 'system.api.batchImport', 'api_path' => '/system/api/batchImport', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '批量导入接口', 'status' => 1],
            ['api_name' => 'system.api.syncRoutes', 'api_path' => '/system/api/syncRoutes', 'api_method' => 'POST', 'api_group' => 'api', 'description' => '同步路由接口', 'status' => 1],
            
            // 认证授权分组
            ['api_name' => 'system.auth.getUserInfo', 'api_path' => '/system/auth/getUserInfo', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '获取用户信息', 'status' => 1],
            ['api_name' => 'system.auth.getUserMenus', 'api_path' => '/system/auth/getUserMenus', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '获取用户菜单', 'status' => 1],
            ['api_name' => 'system.auth.getUserPermissions', 'api_path' => '/system/auth/getUserPermissions', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '获取用户权限', 'status' => 1],
            ['api_name' => 'system.auth.checkPermission', 'api_path' => '/system/auth/checkPermission', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '检查权限状态', 'status' => 1],
            ['api_name' => 'system.auth.assignRole', 'api_path' => '/system/auth/assignRole', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '分配用户角色', 'status' => 1],
            ['api_name' => 'system.auth.refreshPermissions', 'api_path' => '/system/auth/refreshPermissions', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '刷新权限缓存', 'status' => 1],
            ['api_name' => 'system.auth.syncUserPermissions', 'api_path' => '/system/auth/syncUserPermissions', 'api_method' => 'POST', 'api_group' => 'auth', 'description' => '同步权限缓存', 'status' => 1],
            
            // 用户管理分组
            ['api_name' => 'system.user.login', 'api_path' => '/system/user/login', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '用户登录认证', 'status' => 1],
            ['api_name' => 'system.user.logout', 'api_path' => '/system/user/logout', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '用户退出登录', 'status' => 1],
            ['api_name' => 'system.user.getUserList', 'api_path' => '/system/user/getUserList', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '获取用户列表', 'status' => 1],
            ['api_name' => 'system.user.createUser', 'api_path' => '/system/user/createUser', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '创建新用户', 'status' => 1],
            ['api_name' => 'system.user.updateUser', 'api_path' => '/system/user/updateUser', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '更新用户', 'status' => 1],
            ['api_name' => 'system.user.updatePassword', 'api_path' => '/system/user/updatePassword', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '修改用户密码', 'status' => 1],
            ['api_name' => 'system.user.updateProfile', 'api_path' => '/system/user/updateProfile', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '更新用户资料', 'status' => 1],
            ['api_name' => 'system.user.toggleStatus', 'api_path' => '/system/user/toggleStatus', 'api_method' => 'POST', 'api_group' => 'user', 'description' => '切换用户状态', 'status' => 1],
            
            // 角色管理分组
            ['api_name' => 'system.role.getRoleList', 'api_path' => '/system/role/getRoleList', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '获取角色列表', 'status' => 1],
            ['api_name' => 'system.role.createRole', 'api_path' => '/system/role/createRole', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '创建新角色', 'status' => 1],
            ['api_name' => 'system.role.updateRole', 'api_path' => '/system/role/updateRole', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '更新角色信息', 'status' => 1],
            ['api_name' => 'system.role.deleteRole', 'api_path' => '/system/role/deleteRole', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '删除角色', 'status' => 1],
            ['api_name' => 'system.role.getRoleDetail', 'api_path' => '/system/role/getRoleDetail', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '获取角色详情', 'status' => 1],
            ['api_name' => 'system.role.getRoleMenus', 'api_path' => '/system/role/getRoleMenus', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '获取角色菜单权限', 'status' => 1],
            ['api_name' => 'system.role.setRoleMenus', 'api_path' => '/system/role/setRoleMenus', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '设置角色菜单权限', 'status' => 1],
            ['api_name' => 'system.role.getAllRoles', 'api_path' => '/system/role/getAllRoles', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '获取所有角色', 'status' => 1],
            ['api_name' => 'system.role.getAssignableRoles', 'api_path' => '/system/role/getAssignableRoles', 'api_method' => 'POST', 'api_group' => 'role', 'description' => '获取可分配角色', 'status' => 1],

            // 菜单管理分组
            ['api_name' => 'system.menu.getMenuTree', 'api_path' => '/system/menu/getMenuTree', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取菜单树', 'status' => 1],
            ['api_name' => 'system.menu.getMenuList', 'api_path' => '/system/menu/getMenuList', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取菜单列表', 'status' => 1],
            ['api_name' => 'system.menu.createMenu', 'api_path' => '/system/menu/createMenu', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '创建新菜单', 'status' => 1],
            ['api_name' => 'system.menu.updateMenu', 'api_path' => '/system/menu/updateMenu', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '更新菜单信息', 'status' => 1],
            ['api_name' => 'system.menu.deleteMenu', 'api_path' => '/system/menu/deleteMenu', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '删除菜单项', 'status' => 1],
            ['api_name' => 'system.menu.getMenuDetail', 'api_path' => '/system/menu/getMenuDetail', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取菜单详情', 'status' => 1],
            ['api_name' => 'system.menu.getParentMenuOptions', 'api_path' => '/system/menu/getParentMenuOptions', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取父级菜单', 'status' => 1],
            ['api_name' => 'system.menu.assignApis', 'api_path' => '/system/menu/assignApis', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '分配菜单API', 'status' => 1],
            ['api_name' => 'system.menu.toggleStatus', 'api_path' => '/system/menu/toggleStatus', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '切换菜单状态', 'status' => 1],
            ['api_name' => 'system.menu.getMenuPermissions', 'api_path' => '/system/menu/getMenuPermissions', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取菜单权限', 'status' => 1],
            ['api_name' => 'system.menu.updateMenuSort', 'api_path' => '/system/menu/updateMenuSort', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '更新菜单排序', 'status' => 1],
            ['api_name' => 'system.menu.getMenuApis', 'api_path' => '/system/menu/getMenuApis', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取菜单已绑定和未绑定的API', 'status' => 1],
            ['api_name' => 'system.menu.getAssignableMenuTree', 'api_path' => '/system/menu/getAssignableMenuTree', 'api_method' => 'POST', 'api_group' => 'menu', 'description' => '获取可分配菜单树', 'status' => 1],

            
            // 操作日志分组
            ['api_name' => 'system.operationLog.list', 'api_path' => '/system/operationLog/list', 'api_method' => 'POST', 'api_group' => 'operationLog', 'description' => '日志列表', 'status' => 1],
            ['api_name' => 'system.operationLog.detail', 'api_path' => '/system/operationLog/detail', 'api_method' => 'POST', 'api_group' => 'operationLog', 'description' => '日志详情', 'status' => 1],
            ['api_name' => 'system.operationLog.delete', 'api_path' => '/system/operationLog/delete', 'api_method' => 'POST', 'api_group' => 'operationLog', 'description' => '删除日志', 'status' => 1],
            ['api_name' => 'system.operationLog.clear', 'api_path' => '/system/operationLog/clear', 'api_method' => 'POST', 'api_group' => 'operationLog', 'description' => '清空日志', 'status' => 1],
            
            // 系统配置分组
            ['api_name' => 'system.config.getConfigList', 'api_path' => '/system/config/getConfigList', 'api_method' => 'POST', 'api_group' => 'config', 'description' => '获取系统配置列表', 'status' => 1],
            ['api_name' => 'system.config.createConfig', 'api_path' => '/system/config/createConfig', 'api_method' => 'POST', 'api_group' => 'config', 'description' => '添加系统配置', 'status' => 1],
            ['api_name' => 'system.config.updateConfig', 'api_path' => '/system/config/updateConfig', 'api_method' => 'POST', 'api_group' => 'config', 'description' => '更新系统配置', 'status' => 1],
            ['api_name' => 'system.config.deleteConfig', 'api_path' => '/system/config/deleteConfig', 'api_method' => 'POST', 'api_group' => 'config', 'description' => '删除系统配置', 'status' => 1],
            ['api_name' => 'system.config.getConfigByKey', 'api_path' => '/system/config/getConfigByKey', 'api_method' => 'POST', 'api_group' => 'config', 'description' => '根据键名获取配置值', 'status' => 1],
            ['api_name' => 'system.config.getConfigByKeys', 'api_path' => '/system/config/getConfigByKeys', 'api_method' => 'POST', 'api_group' => 'config', 'description' => '批量获取配置值', 'status' => 1],
            
            // 字典数据分组
            ['api_name' => 'system.dictData.getDictDataList', 'api_path' => '/system/dictData/getDictDataList', 'api_method' => 'POST', 'api_group' => 'dictData', 'description' => '获取字典数据列表', 'status' => 1],
            ['api_name' => 'system.dictData.getDictDataDetail', 'api_path' => '/system/dictData/getDictDataDetail', 'api_method' => 'POST', 'api_group' => 'dictData', 'description' => '获取字典数据详情', 'status' => 1],
            ['api_name' => 'system.dictData.getDictDataByType', 'api_path' => '/system/dictData/getDictDataByType', 'api_method' => 'POST', 'api_group' => 'dictData', 'description' => '根据字典类型获取字典数据', 'status' => 1],
            ['api_name' => 'system.dictData.createDictData', 'api_path' => '/system/dictData/createDictData', 'api_method' => 'POST', 'api_group' => 'dictData', 'description' => '创建字典数据', 'status' => 1],
            ['api_name' => 'system.dictData.updateDictData', 'api_path' => '/system/dictData/updateDictData', 'api_method' => 'POST', 'api_group' => 'dictData', 'description' => '更新字典数据', 'status' => 1],
            ['api_name' => 'system.dictData.deleteDictData', 'api_path' => '/system/dictData/deleteDictData', 'api_method' => 'POST', 'api_group' => 'dictData', 'description' => '删除字典数据', 'status' => 1],
            
            // 字典类型分组
            ['api_name' => 'system.dictType.getDictTypeList', 'api_path' => '/system/dictType/getDictTypeList', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '获取字典类型列表', 'status' => 1],
            ['api_name' => 'system.dictType.getDictTypeDetail', 'api_path' => '/system/dictType/getDictTypeDetail', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '获取字典类型详情', 'status' => 1],
            ['api_name' => 'system.dictType.createDictType', 'api_path' => '/system/dictType/createDictType', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '创建字典类型', 'status' => 1],
            ['api_name' => 'system.dictType.updateDictType', 'api_path' => '/system/dictType/updateDictType', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '更新字典类型', 'status' => 1],
            ['api_name' => 'system.dictType.deleteDictType', 'api_path' => '/system/dictType/deleteDictType', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '删除字典类型', 'status' => 1],
            ['api_name' => 'system.dictType.getAllTables', 'api_path' => '/system/dictType/getAllTables', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '获取数据库所有表', 'status' => 1],
            ['api_name' => 'system.dictType.getTableFields', 'api_path' => '/system/dictType/getTableFields', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '获取表的所有字段', 'status' => 1],
            ['api_name' => 'system.dictType.getBindFields', 'api_path' => '/system/dictType/getBindFields', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '获取字典类型绑定的字段列表', 'status' => 1],
            ['api_name' => 'system.dictType.bindField', 'api_path' => '/system/dictType/bindField', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '绑定字段到字典类型', 'status' => 1],
            ['api_name' => 'system.dictType.unbindField', 'api_path' => '/system/dictType/unbindField', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '解绑字段与字典类型', 'status' => 1],
            ['api_name' => 'system.dictType.getFieldDict', 'api_path' => '/system/dictType/getFieldDict', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '根据表名和字段名获取字典类型', 'status' => 1],
            ['api_name' => 'system.dictType.getAllDictTypes', 'api_path' => '/system/dictType/getAllDictTypes', 'api_method' => 'POST', 'api_group' => 'dictType', 'description' => '获取所有字典类型', 'status' => 1],
            
            // 定时任务分组
            ['api_name' => 'system.crontab.getAllCrontabs', 'api_path' => '/system/crontab/getAllCrontabs', 'api_method' => 'POST', 'api_group' => 'crontab', 'description' => '获取所有定时任务列表', 'status' => 1],
            ['api_name' => 'system.crontab.executeTask', 'api_path' => '/system/crontab/executeTask', 'api_method' => 'POST', 'api_group' => 'crontab', 'description' => '执行一次定时任务', 'status' => 1],
            ['api_name' => 'system.crontab.executeTaskByName', 'api_path' => '/system/crontab/executeTaskByName', 'api_method' => 'POST', 'api_group' => 'crontab', 'description' => '根据任务名称执行任务', 'status' => 1],
            
            // 平台管理分组
            ['api_name' => 'system.platform.getList', 'api_path' => '/system/platform/getList', 'api_method' => 'POST', 'api_group' => 'platform', 'description' => '获取平台配置总览', 'status' => 1],
            ['api_name' => 'system.platform.getDetail', 'api_path' => '/system/platform/getDetail', 'api_method' => 'POST', 'api_group' => 'platform', 'description' => '获取特定类型配置详情', 'status' => 1],
            
            // 存储管理分组
            ['api_name' => 'system.storage.getImageUploadToken', 'api_path' => '/system/storage/getImageUploadToken', 'api_method' => 'POST', 'api_group' => 'storage', 'description' => '获取图片上传凭证', 'status' => 1],
            ['api_name' => 'system.storage.getVideoUploadToken', 'api_path' => '/system/storage/getVideoUploadToken', 'api_method' => 'POST', 'api_group' => 'storage', 'description' => '获取视频上传凭证', 'status' => 1],
            ['api_name' => 'system.storage.getAudioUploadToken', 'api_path' => '/system/storage/getAudioUploadToken', 'api_method' => 'POST', 'api_group' => 'storage', 'description' => '获取音频上传凭证', 'status' => 1],
            ['api_name' => 'system.storage.getFileUploadToken', 'api_path' => '/system/storage/getFileUploadToken', 'api_method' => 'POST', 'api_group' => 'storage', 'description' => '获取文件上传凭证', 'status' => 1],
            ['api_name' => 'system.storage.uploadToLocal', 'api_path' => '/system/storage/uploadToLocal', 'api_method' => 'POST', 'api_group' => 'storage', 'description' => '上传文件到本地目录', 'status' => 1],
            
            // 对象存储配置分组
            ['api_name' => 'system.storageConfig.getList', 'api_path' => '/system/storageConfig/getList', 'api_method' => 'POST', 'api_group' => 'storageConfig', 'description' => '获取对象存储配置列表', 'status' => 1],
            ['api_name' => 'system.storageConfig.getDetail', 'api_path' => '/system/storageConfig/getDetail', 'api_method' => 'POST', 'api_group' => 'storageConfig', 'description' => '获取对象存储配置详情', 'status' => 1],
            ['api_name' => 'system.storageConfig.create', 'api_path' => '/system/storageConfig/create', 'api_method' => 'POST', 'api_group' => 'storageConfig', 'description' => '创建对象存储配置', 'status' => 1],
            ['api_name' => 'system.storageConfig.update', 'api_path' => '/system/storageConfig/update', 'api_method' => 'POST', 'api_group' => 'storageConfig', 'description' => '更新对象存储配置', 'status' => 1],
            ['api_name' => 'system.storageConfig.delete', 'api_path' => '/system/storageConfig/delete', 'api_method' => 'POST', 'api_group' => 'storageConfig', 'description' => '删除对象存储配置', 'status' => 1],
            
            // 短信配置分组
            ['api_name' => 'system.smsConfig.getList', 'api_path' => '/system/smsConfig/getList', 'api_method' => 'POST', 'api_group' => 'smsConfig', 'description' => '获取短信配置列表', 'status' => 1],
            ['api_name' => 'system.smsConfig.getDetail', 'api_path' => '/system/smsConfig/getDetail', 'api_method' => 'POST', 'api_group' => 'smsConfig', 'description' => '获取短信配置详情', 'status' => 1],
            ['api_name' => 'system.smsConfig.create', 'api_path' => '/system/smsConfig/create', 'api_method' => 'POST', 'api_group' => 'smsConfig', 'description' => '创建短信配置', 'status' => 1],
            ['api_name' => 'system.smsConfig.update', 'api_path' => '/system/smsConfig/update', 'api_method' => 'POST', 'api_group' => 'smsConfig', 'description' => '更新短信配置', 'status' => 1],
            ['api_name' => 'system.smsConfig.delete', 'api_path' => '/system/smsConfig/delete', 'api_method' => 'POST', 'api_group' => 'smsConfig', 'description' => '删除短信配置', 'status' => 1],
            
            // 支付配置分组
            ['api_name' => 'system.paymentConfig.getList', 'api_path' => '/system/paymentConfig/getList', 'api_method' => 'POST', 'api_group' => 'paymentConfig', 'description' => '获取支付配置列表', 'status' => 1],
            ['api_name' => 'system.paymentConfig.getDetail', 'api_path' => '/system/paymentConfig/getDetail', 'api_method' => 'POST', 'api_group' => 'paymentConfig', 'description' => '获取支付配置详情', 'status' => 1],
            ['api_name' => 'system.paymentConfig.create', 'api_path' => '/system/paymentConfig/create', 'api_method' => 'POST', 'api_group' => 'paymentConfig', 'description' => '创建支付配置', 'status' => 1],
            ['api_name' => 'system.paymentConfig.update', 'api_path' => '/system/paymentConfig/update', 'api_method' => 'POST', 'api_group' => 'paymentConfig', 'description' => '更新支付配置', 'status' => 1],
            ['api_name' => 'system.paymentConfig.delete', 'api_path' => '/system/paymentConfig/delete', 'api_method' => 'POST', 'api_group' => 'paymentConfig', 'description' => '删除支付配置', 'status' => 1],
            
            // 小程序配置分组
            ['api_name' => 'system.miniappConfig.getList', 'api_path' => '/system/miniappConfig/getList', 'api_method' => 'POST', 'api_group' => 'miniappConfig', 'description' => '获取小程序配置列表', 'status' => 1],
            ['api_name' => 'system.miniappConfig.getDetail', 'api_path' => '/system/miniappConfig/getDetail', 'api_method' => 'POST', 'api_group' => 'miniappConfig', 'description' => '获取小程序配置详情', 'status' => 1],
            ['api_name' => 'system.miniappConfig.create', 'api_path' => '/system/miniappConfig/create', 'api_method' => 'POST', 'api_group' => 'miniappConfig', 'description' => '创建小程序配置', 'status' => 1],
            ['api_name' => 'system.miniappConfig.update', 'api_path' => '/system/miniappConfig/update', 'api_method' => 'POST', 'api_group' => 'miniappConfig', 'description' => '更新小程序配置', 'status' => 1],
            ['api_name' => 'system.miniappConfig.delete', 'api_path' => '/system/miniappConfig/delete', 'api_method' => 'POST', 'api_group' => 'miniappConfig', 'description' => '删除小程序配置', 'status' => 1],
            
            // 端点配置分组
            ['api_name' => 'system.endpointConfig.getList', 'api_path' => '/system/endpointConfig/getList', 'api_method' => 'POST', 'api_group' => 'endpointConfig', 'description' => '获取端点配置列表', 'status' => 1],
            ['api_name' => 'system.endpointConfig.getDetail', 'api_path' => '/system/endpointConfig/getDetail', 'api_method' => 'POST', 'api_group' => 'endpointConfig', 'description' => '获取端点配置详情', 'status' => 1],
            ['api_name' => 'system.endpointConfig.create', 'api_path' => '/system/endpointConfig/create', 'api_method' => 'POST', 'api_group' => 'endpointConfig', 'description' => '创建端点配置', 'status' => 1],
            ['api_name' => 'system.endpointConfig.update', 'api_path' => '/system/endpointConfig/update', 'api_method' => 'POST', 'api_group' => 'endpointConfig', 'description' => '更新端点配置', 'status' => 1],
            ['api_name' => 'system.endpointConfig.delete', 'api_path' => '/system/endpointConfig/delete', 'api_method' => 'POST', 'api_group' => 'endpointConfig', 'description' => '删除端点配置', 'status' => 1],
        
              // 部门管理API
              ['api_name' => 'system.dept.getDeptList', 'api_path' => '/system/dept/getDeptList', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '获取部门列表', 'status' => 1],
              ['api_name' => 'system.dept.getDeptTree', 'api_path' => '/system/dept/getDeptTree', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '获取部门树选择器数据', 'status' => 1],
              ['api_name' => 'system.dept.createDept', 'api_path' => '/system/dept/createDept', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '创建部门', 'status' => 1],
              ['api_name' => 'system.dept.updateDept', 'api_path' => '/system/dept/updateDept', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '更新部门', 'status' => 1],
              ['api_name' => 'system.dept.deleteDept', 'api_path' => '/system/dept/deleteDept', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '删除部门', 'status' => 1],
              ['api_name' => 'system.dept.toggleStatus', 'api_path' => '/system/dept/toggleStatus', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '切换部门状态', 'status' => 1],
              ['api_name' => 'system.dept.getDeptListByLoginUserRole', 'api_path' => '/system/dept/getDeptListByLoginUserRole', 'api_method' => 'POST', 'api_group' => 'dept', 'description' => '获取当前登录用户所拥有的部门数据权限', 'status' => 1],

              // 岗位管理API
              ['api_name' => 'system.post.getPostList', 'api_path' => '/system/post/getPostList', 'api_method' => 'POST', 'api_group' => 'post', 'description' => '获取职位列表', 'status' => 1],
              ['api_name' => 'system.post.getAllPosts', 'api_path' => '/system/post/getAllPosts', 'api_method' => 'POST', 'api_group' => 'post', 'description' => '获取所有职位选择器数据', 'status' => 1],
              ['api_name' => 'system.post.createPost', 'api_path' => '/system/post/createPost', 'api_method' => 'POST', 'api_group' => 'post', 'description' => '创建职位', 'status' => 1],
              ['api_name' => 'system.post.updatePost', 'api_path' => '/system/post/updatePost', 'api_method' => 'POST', 'api_group' => 'post', 'description' => '更新职位', 'status' => 1],
              ['api_name' => 'system.post.deletePost', 'api_path' => '/system/post/deletePost', 'api_method' => 'POST', 'api_group' => 'post', 'description' => '删除职位', 'status' => 1],
              ['api_name' => 'system.post.toggleStatus', 'api_path' => '/system/post/toggleStatus', 'api_method' => 'POST', 'api_group' => 'post', 'description' => '切换职位状态', 'status' => 1],
            
        ];
        
        $created = [];
        foreach ($apis as $apiData) {
            $api = SysApi::create($apiData);
            $created[] = $api->api_id;
        }
        
        return $created;
    }
} 