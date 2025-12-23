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

namespace App\Swagger\Generator;

use Hyperf\Contract\ConfigInterface;

/**
 * ZGW 协议 Schema 构建器
 * 用于构建符合 ZGW 协议格式的 OpenAPI Schema
 */
class ZgwSchemaBuilder
{
    protected ConfigInterface $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * 构建 ZGW 协议请求体 Schema
     *
     * @param string $interfaceName 接口名称 (如: admin.user.getList)
     * @param array $params 参数列表
     * @return array
     */
    public function buildRequestSchema(string $interfaceName, array $params = []): array
    {
        $zgwConfig = $this->config->get('swagger.zgw', []);
        $version = $zgwConfig['version'] ?? '1.0';
        $caller = $zgwConfig['caller'] ?? 'swagger';

        return [
            'type' => 'object',
            'required' => ['version', 'seqId', 'timestamp', 'eventId', 'caller', 'interface'],
            'properties' => [
                'version' => [
                    'type' => 'string',
                    'description' => '协议版本',
                    'example' => $version,
                ],
                'seqId' => [
                    'type' => 'string',
                    'description' => '请求序列ID',
                    'example' => 'seq_' . time(),
                ],
                'timestamp' => [
                    'type' => 'integer',
                    'description' => '时间戳',
                    'example' => time(),
                ],
                'eventId' => [
                    'type' => 'integer',
                    'description' => '事件ID',
                    'example' => time(),
                ],
                'caller' => [
                    'type' => 'string',
                    'description' => '调用方标识',
                    'example' => $caller,
                ],
                'interface' => [
                    'type' => 'object',
                    'description' => '接口信息',
                    'required' => ['name', 'param'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => '接口名称',
                            'example' => $interfaceName,
                        ],
                        'param' => $this->buildParamSchema($params),
                    ],
                ],
            ],
        ];
    }

    /**
     * 构建参数 Schema
     *
     * @param array $params 参数列表
     * @return array
     */
    public function buildParamSchema(array $params): array
    {
        if (empty($params)) {
            return [
                'type' => 'object',
                'description' => '请求参数',
                'properties' => new \stdClass(), // 空对象
            ];
        }

        $properties = [];
        $required = [];

        foreach ($params as $param) {
            $name = $param['name'] ?? '';
            if (empty($name)) {
                continue;
            }

            $property = [
                'type' => $this->mapType($param['type'] ?? 'string'),
            ];

            // 描述
            if (!empty($param['description'])) {
                $property['description'] = $param['description'];
            }

            // 示例值
            if (isset($param['example'])) {
                $property['example'] = $param['example'];
            }

            // 默认值
            if (isset($param['default'])) {
                $property['default'] = $param['default'];
            }

            // 枚举值
            if (!empty($param['enum'])) {
                $property['enum'] = $param['enum'];
            }

            // 数值范围
            if (isset($param['minimum'])) {
                $property['minimum'] = $param['minimum'];
            }
            if (isset($param['maximum'])) {
                $property['maximum'] = $param['maximum'];
            }

            // 字符串长度
            if (isset($param['minLength'])) {
                $property['minLength'] = $param['minLength'];
            }
            if (isset($param['maxLength'])) {
                $property['maxLength'] = $param['maxLength'];
            }

            // 数组元素类型
            if (($param['type'] ?? 'string') === 'array' && !empty($param['items'])) {
                $property['items'] = [
                    'type' => $this->mapType($param['items']),
                ];
            }

            $properties[$name] = $property;

            // 必填参数
            if (!empty($param['required'])) {
                $required[] = $name;
            }
        }

        $schema = [
            'type' => 'object',
            'description' => '请求参数',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * 构建 ZGW 协议响应体 Schema
     *
     * @param array $dataSchema 业务数据 Schema
     * @return array
     */
    public function buildResponseSchema(array $dataSchema = []): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'integer',
                    'description' => '响应码 (0表示成功)',
                    'example' => 0,
                ],
                'message' => [
                    'type' => 'string',
                    'description' => '响应消息',
                    'example' => 'success',
                ],
                'data' => empty($dataSchema) ? [
                    'type' => 'object',
                    'description' => '响应数据',
                ] : $dataSchema,
                'timestamp' => [
                    'type' => 'integer',
                    'description' => '响应时间戳',
                    'example' => time(),
                ],
            ],
        ];
    }

    /**
     * 构建错误响应 Schema
     *
     * @return array
     */
    public function buildErrorResponseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'integer',
                    'description' => '错误码',
                    'example' => 10001,
                ],
                'message' => [
                    'type' => 'string',
                    'description' => '错误消息',
                    'example' => '请求参数错误',
                ],
                'data' => [
                    'type' => 'object',
                    'description' => '错误详情',
                    'nullable' => true,
                ],
                'timestamp' => [
                    'type' => 'integer',
                    'description' => '响应时间戳',
                    'example' => time(),
                ],
            ],
        ];
    }

    /**
     * 构建分页响应 Schema
     *
     * @param array $itemSchema 列表项 Schema
     * @return array
     */
    public function buildPaginatedResponseSchema(array $itemSchema = []): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'list' => [
                    'type' => 'array',
                    'description' => '数据列表',
                    'items' => empty($itemSchema) ? ['type' => 'object'] : $itemSchema,
                ],
                'total' => [
                    'type' => 'integer',
                    'description' => '总记录数',
                    'example' => 100,
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => '当前页码',
                    'example' => 1,
                ],
                'size' => [
                    'type' => 'integer',
                    'description' => '每页数量',
                    'example' => 20,
                ],
            ],
        ];
    }

    /**
     * 映射类型到 OpenAPI 类型
     *
     * @param string $type
     * @return string
     */
    protected function mapType(string $type): string
    {
        $typeMap = [
            'int' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'bool' => 'boolean',
            'str' => 'string',
            'arr' => 'array',
            'obj' => 'object',
        ];

        return $typeMap[$type] ?? $type;
    }

    /**
     * 构建完整的请求示例
     *
     * @param string $interfaceName
     * @param array $paramExample
     * @return array
     */
    public function buildRequestExample(string $interfaceName, array $paramExample = []): array
    {
        $zgwConfig = $this->config->get('swagger.zgw', []);

        return [
            'version' => $zgwConfig['version'] ?? '1.0',
            'seqId' => 'seq_' . time(),
            'timestamp' => time(),
            'eventId' => time(),
            'caller' => $zgwConfig['caller'] ?? 'swagger',
            'interface' => [
                'name' => $interfaceName,
                'param' => $paramExample,
            ],
        ];
    }
}
