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

namespace App\Service\Base;

use App\Model\SysRole;
use App\Model\SysRoleDept;
use App\Model\SysDept;
use App\Model\User;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use ZYProSoft\Facade\Auth;
use Hyperf\Database\Model\Builder;
use Hyperf\Contract\ConfigInterface;
use ZYProSoft\Service\AbstractService;
use ZYProSoft\Log\Log;

/**
 * 数据权限服务类
 * 基于dept_path字段实现高性能的数据权限过滤
 */
class DataScopeService extends AbstractService
{
    /**
     * @Inject
     * @var Redis
     */
    protected Redis $redis;

    /**
     * @Inject
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * 应用数据权限过滤到查询构建器
     */
    public function applyDataScopeFilter(Builder $builder): void
    {
        // 检查是否启用数据权限过滤
        if(!$this->shouldApplyDataScope($builder)) {
            return;
        }

        // 获取当前用户的数据权限信息
        $dataScopeInfo = $this->getCurrentUserDataScope();
        
        if (!$dataScopeInfo) {
            return;
        }

        $dataScope = $dataScopeInfo['data_scope'];
        $userDeptId = $dataScopeInfo['dept_id'];
        $userDeptPath = $dataScopeInfo['dept_path'];

        switch ($dataScope) {
            case 1: // 全部数据权限
                // 不添加任何过滤条件
                break;
                
            case 2: // 本部门数据权限
                $builder->where('dept_id', $userDeptId);
                break;
                
            case 3: // 本部门及子部门数据权限
                $this->applyDeptAndChildrenFilter($builder, $userDeptId, $userDeptPath);
                break;
                
            case 4: // 自定义数据权限
                $customDeptIds = $this->getCustomDeptIds($dataScopeInfo['role_id']);
                if (empty($customDeptIds)) {
                    // 如果没有自定义部门权限，则不能查询任何数据
                    $builder->whereRaw('1 = 0');
                } else {
                    $builder->whereIn('dept_id', $customDeptIds);
                }
                break;
        }
    }

    /**
     * 应用本部门及子部门过滤
     * 基于dept_path字段，避免递归查询
     */
    protected function applyDeptAndChildrenFilter(Builder $builder, int $userDeptId, string $userDeptPath): void
    {
        // 构造查询条件：本部门 OR 子部门
        $builder->where(function ($query) use ($userDeptId, $userDeptPath) {
            // 本部门
            $query->where('dept_id', $userDeptId);
            
            // 子部门：dept_path包含当前部门的路径
            if (!empty($userDeptPath)) {
                // 子部门的路径格式：userDeptPath,userDeptId,子部门ID...
                $pathPattern = $userDeptPath . ',' . $userDeptId . ',%';
                $query->orWhere('dept_path', 'like', $pathPattern);
            } else {
                // 如果当前部门是根部门，子部门的路径会以当前部门ID开头
                $pathPattern = $userDeptId . ',%';
                $query->orWhere('dept_path', 'like', $pathPattern)
                      ->orWhere('dept_path', $userDeptId); // 直接子部门
            }
        });
    }

    /**
     * 获取当前用户的数据权限信息
     * 使用缓存优化性能
     */
    protected function getCurrentUserDataScope(): ?array
    {
        if (!Auth::isLogin()) {
            return null;
        }

        $user = Auth::user();
        if (!$user instanceof User || $user->role_id <= 0) {
            return null;
        }

        $cacheKey = $this->getCachePrefix() . 'user:' . $user->user_id;
        
        // 尝试从缓存获取
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }

        // 从数据库查询
        $userRole = SysRole::find($user->role_id);
        if (!$userRole instanceof SysRole) {
            return null;
        }

        $userDept = null;
        $userDeptPath = '';
        
        if ($user->dept_id) {
            $userDept = SysDept::find($user->dept_id);
            if ($userDept) {
                $userDeptPath = $userDept->dept_path ?? '';
            }
        }

        $result = [
            'data_scope' => $userRole->data_scope,
            'role_id' => $userRole->role_id,
            'dept_id' => $user->dept_id,
            'dept_path' => $userDeptPath,
        ];

        // 缓存结果
        $this->redis->setex($cacheKey, $this->getCacheTtl(), json_encode($result));

        return $result;
    }

    /**
     * 获取角色的自定义部门权限
     * 使用缓存优化性能
     */
    protected function getCustomDeptIds(int $roleId): array
    {
        $cacheKey = $this->getCachePrefix() . 'role:' . $roleId;
        
        // 尝试从缓存获取
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }

        // 从数据库查询
        $deptIds = SysRoleDept::where('role_id', $roleId)
            ->pluck('dept_id')
            ->toArray();

        // 缓存结果
        $this->redis->setex($cacheKey, $this->getCacheTtl(), json_encode($deptIds));

        return $deptIds;
    }

    /**
     * 检查模型是否需要应用数据权限过滤
     */
    public function shouldApplyDataScope(Builder $builder): bool
    {
        // 检查功能是否启用
        if (!$this->config->get('data_scope.enabled', true)) {
            Log::info("数据范围校验：数据权限过滤功能未启用");
            return false;
        }

        // 检查是否有当前用户
        if (!Auth::isLogin()) {
            Log::info("数据范围校验：当前用户未登录");
            return false;
        }

        // 当前登录用户必须是有角色才检查权限
        $user = $this->user();
        if(!$user instanceof User) {
            Log::info("数据范围校验：当前用户不是管理相关身份");
            return false;
        }

        // 没有角色说明不是管理相关身份，不存在数据权限问题
        if($user->role_id <= 0) {
            Log::info("数据范围校验：当前用户没有角色");
            return false;
        }

        $model = $builder->getModel();
        $table = $model->getTable();
        
        // 检查表是否在排除列表中
        $excludedTables = $this->config->get('data_scope.excluded_tables', []);
        if (in_array($table, $excludedTables)) {
            Log::info("数据范围校验：当前表在排除列表中");
            return false;
        }
        
        // 获取数据权限字段名
        $column = $this->getDataScopeColumnForTable($table);
        
        // 检查模型是否有相应的字段
        if (!$this->hasColumn($model, $column)) {
            Log::info("数据范围校验：当前表没有数据权限字段");
            return false;
        }

        return true;
    }

    /**
     * 检查模型是否包含指定字段
     */
    protected function hasColumn($model, string $column): bool
    {
        try {
            $table = $model->getTable();
            $connection = $model->getConnection();
            $schema = $connection->getSchemaBuilder();
            
            return $schema->hasColumn($table, $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取缓存前缀
     */
    protected function getCachePrefix(): string
    {
        return $this->config->get('data_scope.cache.prefix', 'data_scope:');
    }

    /**
     * 获取缓存过期时间
     */
    protected function getCacheTtl(): int
    {
        return $this->config->get('data_scope.cache.ttl', 3600);
    }

    /**
     * 获取指定表的数据权限字段名
     */
    protected function getDataScopeColumnForTable(string $table): string
    {
        $columnMapping = $this->config->get('data_scope.column_mapping', []);
        
        return $columnMapping[$table] ?? $this->config->get('data_scope.default_column', 'dept_id');
    }

    /**
     * 清除用户数据权限缓存
     */
    public function clearUserDataScopeCache(int $userId): void
    {
        $cacheKey = $this->getCachePrefix() . 'user:' . $userId;
        $this->redis->del($cacheKey);
    }

    /**
     * 清除角色数据权限缓存
     */
    public function clearRoleDataScopeCache(int $roleId): void
    {
        $cacheKey = $this->getCachePrefix() . 'role:' . $roleId;
        $this->redis->del($cacheKey);

        //同时也要清除使用了这个角色的用户的权限缓存
        $users = User::where('role_id', $roleId)->get();
        foreach($users as $user) {
            $this->clearUserDataScopeCache($user->user_id);
        }
    }

    /**
     * 清除所有数据权限缓存
     */
    public function clearAllDataScopeCache(): void
    {
        $pattern = $this->getCachePrefix() . '*';
        $keys = $this->redis->keys($pattern);
        
        if (!empty($keys)) {
            $this->redis->del(...$keys);
        }
    }
}
