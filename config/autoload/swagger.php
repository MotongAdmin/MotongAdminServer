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

return [
    // 是否启用 Swagger 文档
    'enable' => env('SWAGGER_ENABLE', false),

    // 文档输出目录
    'output' => BASE_PATH . '/public/swagger',

    // API 文档基本信息
    'info' => [
        'title' => env('SWAGGER_TITLE', 'Motong Server API'),
        'version' => env('SWAGGER_VERSION', '1.0.0'),
        'description' => 'Motong Server 接口文档 (ZGW协议)',
        'contact' => [
            'name' => 'zyvincent',
            'email' => '1003081775@qq.com',
        ],
    ],

    // 服务器配置
    'servers' => [
        [
            'url' => env('SERVER_DOMAIN', 'http://127.0.0.1:9506'),
            'description' => '开发环境',
        ],
    ],

    // 扫描路径配置
    'scan' => [
        'paths' => [
            BASE_PATH . '/app/Controller'
        ],
        // 排除的目录
        'exclude' => [],
    ],

    // ZGW 协议配置
    'zgw' => [
        'version' => '1.0',
        'caller' => 'swagger',
    ],

    // 安全认证配置
    'security' => [
        'bearer' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'JWT Token 认证',
        ],
    ],
];
