<?php
/**
 * 数据权限配置
 */
declare(strict_types=1);

return [
    // 是否启用数据权限过滤
    'enabled' => env('DATA_SCOPE_ENABLED', true),
    
    // 缓存配置
    'cache' => [
        'ttl' => 3600, // 缓存过期时间（秒）
        'prefix' => 'data_scope:', // 缓存键前缀
    ],
    
    // 排除的表（这些表不会应用数据权限过滤）
    'excluded_tables' => [
        'sys_api',
        'sys_menu',
        'sys_role',
        'sys_dept',
        'sys_config',
        'sys_dict',
        'sys_dict_data',
        'sys_field_dict',
        'sys_role_menu',
        'sys_role_dept',
        'sys_operate_log',
        'sys_login_log',
    ],
    
    // 数据权限类型映射
    'data_scope_types' => [
        1 => 'all',        // 全部数据权限
        2 => 'dept',       // 本部门数据权限
        3 => 'dept_sub',   // 本部门及子部门数据权限
        4 => 'custom',     // 自定义数据权限
    ],
    
    // 默认的数据权限字段名
    'default_column' => 'dept_id',
    
    // 特殊字段映射（某些表可能使用不同的字段名）
    'column_mapping' => [
        // 'table_name' => 'column_name',
        // 'user_profiles' => 'department_id',
    ],
];
