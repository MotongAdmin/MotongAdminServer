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
use App\Service\Admin\System\ApiService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * API接口管理控制器
 * @AutoController(prefix="/system/api")
 */
class ApiController extends AbstractController
{
    /**
     * @Inject
     * @var ApiService
     */
    protected ApiService $apiService;

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
            'unique' => ':attribute已存在',
            'exists' => ':attribute不存在',
            'in' => ':attribute的值不在允许范围内',
            'array' => ':attribute必须是数组',
            
            // 字段特定消息
            'api_id.required' => '接口ID不能为空',
            'api_id.integer' => '接口ID必须是整数',
            'api_id.exists' => '接口不存在',
            
            'api_name.required' => '接口名称不能为空',
            'api_name.string' => '接口名称必须是字符串',
            'api_name.max' => '接口名称长度不能超过50位',
            
            'api_path.required' => '接口路径不能为空',
            'api_path.string' => '接口路径必须是字符串',
            'api_path.max' => '接口路径长度不能超过200位',
            'api_path.unique' => '接口路径已存在',
            
            'api_method.required' => '请求方法不能为空',
            'api_method.string' => '请求方法必须是字符串',
            'api_method.max' => '请求方法长度不能超过10位',
            
            'api_group.string' => '接口分组必须是字符串',
            'api_group.max' => '接口分组长度不能超过50位',
            
            'description.string' => '接口描述必须是字符串',
            'description.max' => '接口描述长度不能超过500位',
            
            'status.in' => '接口状态只能是0或1',
            
            'apis.required' => '接口列表不能为空',
            'apis.array' => '接口列表必须是数组',
            'apis.*.api_name.required' => '接口名称不能为空',
            'apis.*.api_name.string' => '接口名称必须是字符串',
            'apis.*.api_name.max' => '接口名称长度不能超过50位',
            'apis.*.api_path.required' => '接口路径不能为空',
            'apis.*.api_path.string' => '接口路径必须是字符串',
            'apis.*.api_path.max' => '接口路径长度不能超过200位',
            'apis.*.api_method.required' => '请求方法不能为空',
            'apis.*.api_method.string' => '请求方法必须是字符串',
            'apis.*.api_method.max' => '请求方法长度不能超过10位',
        ];
    }

    /**
     * @Description("获取接口列表")
     * ZGW接口名: system.api.getApiList
     */
    public function getApiList(AuthedRequest $request)
    {
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'keyword' => 'string|max:50',
            'group' => 'string|max:50'
        ]);
        
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $keyword = $request->param('keyword', '');
        $group = $request->param('group', '');
        
        $result = $this->apiService->getApiList($page, $size, $keyword, $group);
        
        return $this->success($result);
    }

    /**
     * @Description("创建新接口")
     * ZGW接口名: system.api.createApi
     */
    public function createApi(AuthedRequest $request)
    {
        $this->validate([
            'api_name' => 'required|string|max:50',
            'api_path' => 'required|string|max:200|unique:sys_api,api_path',
            'api_method' => 'required|string|max:10',
            'api_group' => 'string|max:50',
            'description' => 'string|max:500',
            'status' => 'in:0,1'
        ]);
        
        $data = [
            'api_name' => $request->param('api_name'),
            'api_path' => $request->param('api_path'),
            'api_method' => strtoupper($request->param('api_method')),
            'api_group' => $request->param('api_group', ''),
            'description' => $request->param('description', ''),
            'status' => $request->param('status', 1)
        ];
        
        $apiId = $this->apiService->createApi($data);
        
        return $this->success(['api_id' => $apiId]);
    }

    /**
     * @Description("更新接口信息")
     * ZGW接口名: system.api.updateApi
     */
    public function updateApi(AuthedRequest $request)
    {
        $apiId = $request->param('api_id');
        $this->validate([
            'api_id' => 'required|integer|exists:sys_api,api_id',
            'api_name' => 'string|max:50',
            'api_path' => "string|max:200|unique:sys_api,api_path,{$apiId},api_id",
            'api_method' => 'string|max:10',
            'api_group' => 'string|max:50',
            'description' => 'string|max:500',
            'status' => 'in:0,1'
        ]);
        
        $data = array_filter([
            'api_name' => $request->param('api_name'),
            'api_path' => $request->param('api_path'),
            'api_method' => $request->param('api_method') ? strtoupper($request->param('api_method')) : null,
            'api_group' => $request->param('api_group'),
            'description' => $request->param('description'),
            'status' => $request->param('status')
        ], function($value) {
            return $value !== null;
        });
        
        $this->apiService->updateApi($apiId, $data);
        
        return $this->success([]);
    }

    /**
     * @Description("删除接口")
     * ZGW接口名: system.api.deleteApi
     */
    public function deleteApi(AuthedRequest $request)
    {
        $this->validate([
            'api_id' => 'required|integer|exists:sys_api,api_id'
        ]);
        
        $apiId = $request->param('api_id');
        $this->apiService->deleteApi($apiId);
        
        return $this->success([]);
    }

    /**
     * @Description("获取接口详情")
     * ZGW接口名: system.api.getApiDetail
     */
    public function getApiDetail(AuthedRequest $request)
    {
        $this->validate([
            'api_id' => 'required|integer|exists:sys_api,api_id'
        ]);
        
        $apiId = $request->param('api_id');
        $api = $this->apiService->getApiDetail($apiId);
        
        return $this->success(['api' => $api]);
    }

    /**
     * @Description("获取接口分组")
     * ZGW接口名: system.api.getApiGroups
     */
    public function getApiGroups(AuthedRequest $request)
    {
        $groups = $this->apiService->getApiGroups();
        
        return $this->success(['groups' => $groups]);
    }

    /**
     * @Description("切换接口状态")
     * ZGW接口名: system.api.toggleStatus
     */
    public function toggleStatus(AuthedRequest $request)
    {
        $this->validate([
            'api_id' => 'required|integer|exists:sys_api,api_id'
        ]);
        
        $apiId = $request->param('api_id');
        $this->apiService->toggleApiStatus($apiId);
        
        return $this->success([]);
    }

    /**
     * @Description("批量导入接口")
     * ZGW接口名: system.api.batchImport
     */
    public function batchImport(AuthedRequest $request)
    {
        $this->validate([
            'apis' => 'required|array',
            'apis.*.api_name' => 'required|string|max:50',
            'apis.*.api_path' => 'required|string|max:200',
            'apis.*.api_method' => 'required|string|max:10'
        ]);
        
        $apis = $request->param('apis');
        
        // 格式化数据
        $formattedApis = [];
        foreach ($apis as $api) {
            $formattedApis[] = [
                'api_name' => $api['api_name'],
                'api_path' => $api['api_path'],
                'api_method' => strtoupper($api['api_method']),
                'api_group' => $api['api_group'] ?? '',
                'description' => $api['description'] ?? '',
                'status' => $api['status'] ?? 1
            ];
        }
        
        $result = $this->apiService->batchImportApis($formattedApis);
        
        return $this->success($result);
    }

    /**
     * @Description("同步路由接口")
     * ZGW接口名: system.api.syncRoutes
     */
    public function syncRoutes(AuthedRequest $request)
    {
        $count = $this->apiService->syncRoutesToApis();
        
        return $this->success(['syncCount' => $count]);
    }
}
