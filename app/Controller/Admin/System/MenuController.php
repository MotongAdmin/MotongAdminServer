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

namespace App\Controller\Admin\System;

use ZYProSoft\Http\AuthedRequest;
use App\Service\Admin\System\MenuService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 菜单管理控制器
 * @AutoController(prefix="/system/menu")
 */
class MenuController extends AbstractController
{
    /**
     * @Inject
     * @var MenuService
     */
    protected MenuService $menuService;

    /**
     * 自定义验证错误消息
     * @return array
     */
    public function messages()
    {
        return [
            // 通用规则消息
            'required' => ':attribute不能为空',
            'string' => ':attribute必须是字符串',
            'integer' => ':attribute必须是整数',
            'max' => ':attribute长度不能超过:max位',
            'min' => ':attribute的值不能小于:min',
            'exists' => ':attribute不存在',
            'in' => ':attribute的值不在允许范围内',
            'array' => ':attribute必须是数组',
            'nullable' => ':attribute可以为空',
            
            // 字段特定消息
            'menu_id.required' => '菜单ID不能为空',
            'menu_id.integer' => '菜单ID必须是整数',
            'menu_id.exists' => '菜单不存在',
            
            'menu_name.required' => '菜单名称不能为空',
            'menu_name.string' => '菜单名称必须是字符串',
            'menu_name.max' => '菜单名称长度不能超过50位',
            
            'parent_id.integer' => '父菜单ID必须是整数',
            'parent_id.min' => '父菜单ID不能小于0',
            
            'order_num.integer' => '显示顺序必须是整数',
            'order_num.min' => '显示顺序不能小于0',
            
            'path.string' => '路由地址必须是字符串',
            'path.max' => '路由地址长度不能超过200位',
            
            'component.string' => '组件路径必须是字符串',
            'component.max' => '组件路径长度不能超过255位',
            
            'query.string' => '路由参数必须是字符串',
            'query.max' => '路由参数长度不能超过255位',
            
            'is_frame.in' => '外链标识只能是0或1',
            
            'is_cache.in' => '缓存标识只能是0或1',
            
            'menu_type.required' => '菜单类型不能为空',
            'menu_type.in' => '菜单类型只能是M、C、F',
            
            'visible.in' => '显示状态只能是0或1',
            
            'status.in' => '菜单状态只能是0或1',
            
            'perms.string' => '权限标识必须是字符串',
            'perms.max' => '权限标识长度不能超过100位',
            
            'icon.string' => '菜单图标必须是字符串',
            'icon.max' => '菜单图标长度不能超过100位',
            
            'remark.string' => '备注必须是字符串',
            'remark.max' => '备注长度不能超过500位',
            
            'api_ids.array' => 'API权限必须是数组',
            
            'menu_sorts.required' => '菜单排序数据不能为空',
            'menu_sorts.array' => '菜单排序数据必须是数组',
            'menu_sorts.*.menu_id.required' => '菜单ID不能为空',
            'menu_sorts.*.menu_id.integer' => '菜单ID必须是整数',
            'menu_sorts.*.menu_id.exists' => '菜单不存在',
            'menu_sorts.*.order_num.required' => '显示顺序不能为空',
            'menu_sorts.*.order_num.integer' => '显示顺序必须是整数',
            'menu_sorts.*.order_num.min' => '显示顺序不能小于0',
        ];
    }

    /**
     * @Description("获取菜单树")
     * ZGW接口名: system.menu.getMenuTree
     */
    public function getMenuTree(AuthedRequest $request)
    {
        $only_visible = $request->param('only_visible', false);
        $menus = $this->menuService->getMenuTree($only_visible);
        
        return $this->success(['menus' => $menus]);
    }

    /**
     * @Description("获取可分配的菜单树")
     * ZGW接口名: system.menu.getAssignableMenuTree
     */
    public function getAssignableMenuTree(AuthedRequest $request)
    {
        $menus = $this->menuService->getAssignableMenuTree();
        
        return $this->success(['menus' => $menus]);
    }

    /**
     * @Description("获取菜单列表")
     * ZGW接口名: system.menu.getMenuList
     */
    public function getMenuList(AuthedRequest $request)
    {
        $this->validate([
            'keyword' => 'string|max:50',
            'status' => 'string|in:0,1'
        ]);
        
        $keyword = $request->param('keyword', '');
        $status = $request->param('status', '');
        
        $menus = $this->menuService->getMenuList($keyword, $status);
        
        return $this->success(['menus' => $menus]);
    }

    /**
     * @Description("创建新菜单")
     * ZGW接口名: system.menu.createMenu
     */
    public function createMenu(AuthedRequest $request)
    {
        $this->validate([
            'menu_name' => 'required|string|max:50',
            'parent_id' => 'integer|min:0',
            'order_num' => 'integer|min:0',
            'path' => 'string|max:200',
            'component' => 'string|max:255',
            'query' => 'string|max:255',
            'is_frame' => 'in:0,1',
            'is_cache' => 'in:0,1',
            'menu_type' => 'required|in:M,C,F',
            'visible' => 'in:0,1',
            'status' => 'in:0,1',
            'perms' => 'string|max:100',
            'icon' => 'string|max:100|nullable',
            'remark' => 'string|max:500|nullable'
        ]);
        
        $data = [
            'menu_name' => $request->param('menu_name'),
            'parent_id' => $request->param('parent_id', 0),
            'order_num' => $request->param('order_num', 0),
            'path' => $request->param('path'),
            'component' => $request->param('component'),
            'query' => $request->param('query'),
            'is_frame' => $request->param('is_frame', 1),
            'is_cache' => $request->param('is_cache', 0),
            'menu_type' => $request->param('menu_type'),
            'visible' => $request->param('visible', 1),
            'status' => $request->param('status', 1),
            'perms' => $request->param('perms'),
            'icon' => $request->param('icon'),
            'remark' => $request->param('remark')
        ];
        
        $menuId = $this->menuService->createMenu($data);
        
        return $this->success(['menu_id' => $menuId]);
    }

    /**
     * @Description("更新菜单信息")
     * ZGW接口名: system.menu.updateMenu
     */
    public function updateMenu(AuthedRequest $request)
    {
        $this->validate([
            'menu_id' => 'required|integer|exists:sys_menu,menu_id',
            'menu_name' => 'string|max:50',
            'parent_id' => 'integer|min:0',
            'order_num' => 'integer|min:0',
            'path' => 'string|max:200',
            'component' => 'string|max:255',
            'query' => 'string|max:255|nullable',
            'is_frame' => 'in:0,1',
            'is_cache' => 'in:0,1',
            'menu_type' => 'in:M,C,F',
            'visible' => 'in:0,1',
            'status' => 'in:0,1',
            'perms' => 'string|max:100',
            'icon' => 'string|max:100|nullable',
            'remark' => 'string|max:500|nullable'
        ]);
        
        $menuId = $request->param('menu_id');
        $data = array_filter([
            'menu_name' => $request->param('menu_name'),
            'parent_id' => $request->param('parent_id'),
            'order_num' => $request->param('order_num'),
            'path' => $request->param('path'),
            'component' => $request->param('component'),
            'query' => $request->param('query'),
            'is_frame' => $request->param('is_frame'),
            'is_cache' => $request->param('is_cache'),
            'menu_type' => $request->param('menu_type'),
            'visible' => $request->param('visible'),
            'status' => $request->param('status'),
            'perms' => $request->param('perms'),
            'icon' => $request->param('icon'),
            'remark' => $request->param('remark')
        ], function($value) {
            return $value !== null;
        });
        
        $this->menuService->updateMenu($menuId, $data);
        
        return $this->success([]);
    }

    /**
     * @Description("删除菜单项")
     * ZGW接口名: system.menu.deleteMenu
     */
    public function deleteMenu(AuthedRequest $request)
    {
        $this->validate([
            'menu_id' => 'required|integer|exists:sys_menu,menu_id'
        ]);
        
        $menuId = $request->param('menu_id');
        $this->menuService->deleteMenu($menuId);
        
        return $this->success([]);
    }

    /**
     * @Description("获取菜单详情")
     * ZGW接口名: system.menu.getMenuDetail
     */
    public function getMenuDetail(AuthedRequest $request)
    {
        $this->validate([
            'menu_id' => 'required|integer|exists:sys_menu,menu_id'
        ]);
        
        $menuId = $request->param('menu_id');
        $menu = $this->menuService->getMenuDetail($menuId);
        
        return $this->success(['menu' => $menu]);
    }

    /**
     * @Description("获取父级菜单")
     * ZGW接口名: system.menu.getParentMenuOptions
     */
    public function getParentMenuOptions(AuthedRequest $request)
    {
        $options = $this->menuService->getParentMenuOptions();
        
        return $this->success(['options' => $options]);
    }

    /**
     * @Description("分配菜单API")
     * ZGW接口名: system.menu.assignApis
     */
    public function assignApis(AuthedRequest $request)
    {
        $this->validate([
            'menu_id' => 'required|integer|exists:sys_menu,menu_id',
            'api_ids' => 'array'
        ]);
        
        $menuId = $request->param('menu_id');
        $apiIds = $request->param('api_ids', []);
        
        $this->menuService->assignApisToMenu($menuId, $apiIds);
        
        return $this->success([]);
    }

    /**
     * @Description("切换菜单状态")
     * ZGW接口名: system.menu.toggleStatus
     */
    public function toggleStatus(AuthedRequest $request)
    {
        $this->validate([
            'menu_id' => 'required|integer|exists:sys_menu,menu_id'
        ]);
        
        $menuId = $request->param('menu_id');
        $this->menuService->toggleMenuStatus($menuId);
        
        return $this->success([]);
    }

    /**
     * @Description("获取菜单权限")
     * ZGW接口名: system.menu.getMenuPermissions
     */
    public function getMenuPermissions(AuthedRequest $request)
    {
        $permissions = $this->menuService->getMenuPermissions();
        
        return $this->success(['permissions' => $permissions]);
    }

    /**
     * @Description("更新菜单排序")
     * ZGW接口名: system.menu.updateMenuSort
     */
    public function updateMenuSort(AuthedRequest $request)
    {
        $this->validate([
            'menu_sorts' => 'required|array',
            'menu_sorts.*.menu_id' => 'required|integer|exists:sys_menu,menu_id',
            'menu_sorts.*.order_num' => 'required|integer|min:0'
        ]);
        
        $menuSorts = $request->param('menu_sorts');
        $this->menuService->updateMenuSort($menuSorts);
        
        return $this->success([]);
    }

    /**
     * @Description("获取菜单已绑定和未绑定的API")
     * ZGW接口名: system.menu.getMenuApis
     */
    public function getMenuApis(AuthedRequest $request)
    {
        $this->validate([
            'menu_id' => 'required|integer|exists:sys_menu,menu_id'
        ]);
        
        $menuId = $request->param('menu_id');
        $keyword = $request->param('keyword', '');
        $group = $request->param('group', '');
        
        $result = $this->menuService->getMenuApis($menuId, $keyword, $group);
        
        return $this->success($result);
    }
}
