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

namespace App\Service\Admin\System;
use App\Service\Admin\BaseService;

use App\Model\SysApi;
use App\Model\SysMenuApi;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 接口管理服务类
 */
class ApiService extends BaseService
{
    /**
     * 获取接口列表
     */
    public function getApiList(int $page = 1, int $size = 20, string $keyword = '', string $group = ''): array
    {
        $query = SysApi::query();
        
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('api_name', 'like', "%{$keyword}%")
                  ->orWhere('api_path', 'like', "%{$keyword}%");
            });
        }
        
        if (!empty($group)) {
            $query->where('api_group', $group);
        }
        
        $total = $query->count();
        $list = $query->orderBy('api_group')
            ->orderBy('api_id')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get()
            ->toArray();
            
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'pages' => ceil($total / $size)
        ];
    }

    /**
     * 创建接口
     */
    public function createApi(array $data): int
    {
        // 检查接口路径和方法唯一性
        if (SysApi::where('api_path', $data['api_path'])
            ->where('api_method', $data['api_method'])
            ->exists()) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '接口路径和方法组合已存在');
        }
        
        $api = SysApi::create($data);
        
        return $api->api_id;
    }

    /**
     * 更新接口
     */
    public function updateApi(int $apiId, array $data): bool
    {
        $api = SysApi::find($apiId);
        if (!$api) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '接口不存在');
        }
        
        // 检查接口路径和方法唯一性
        if (isset($data['api_path']) && isset($data['api_method'])) {
            if (SysApi::where('api_path', $data['api_path'])
                ->where('api_method', $data['api_method'])
                ->where('api_id', '!=', $apiId)
                ->exists()) {
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '接口路径和方法组合已存在');
            }
        }
        
        $api->update($data);
        
        return true;
    }

    /**
     * 删除接口
     */
    public function deleteApi(int $apiId): bool
    {
        $api = SysApi::find($apiId);
        if (!$api) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '接口不存在');
        }
        
        // 删除菜单API关联
        SysMenuApi::where('api_id', $apiId)->delete();
        
        // 软删除接口
        $api->delete();
        
        return true;
    }

    /**
     * 获取接口详情
     */
    public function getApiDetail(int $apiId): array
    {
        $api = SysApi::find($apiId);
        if (!$api) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '接口不存在');
        }
        
        return $api->toArray();
    }

    /**
     * 获取接口分组列表
     */
    public function getApiGroups(): array
    {
        return SysApi::whereNotNull('api_group')
            ->where('api_group', '!=', '')
            ->distinct()
            ->pluck('api_group')
            ->toArray();
    }

    /**
     * 启用/禁用接口
     */
    public function toggleApiStatus(int $apiId): bool
    {
        $api = SysApi::find($apiId);
        if (!$api instanceof SysApi) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '接口不存在');
        }
        
        $api->status = $api->status == 1 ? 0 : 1;
        $api->save();
        
        return true;
    }

    /**
     * 批量导入接口
     */
    public function batchImportApis(array $apis): array
    {
        $successCount = 0;
        $failedList = [];
        
        foreach ($apis as $apiData) {
            try {
                $this->createApi($apiData);
                $successCount++;
            } catch (\Exception $e) {
                $failedList[] = [
                    'api' => $apiData,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'successCount' => $successCount,
            'failedCount' => count($failedList),
            'failedList' => $failedList
        ];
    }

    /**
     * 同步路由到接口表
     */
    public function syncRoutesToApis(): int
    {
        // 这里可以实现从路由配置同步到接口表的逻辑
        // 具体实现需要根据项目的路由配置方式来定制
        return 0;
    }
}
