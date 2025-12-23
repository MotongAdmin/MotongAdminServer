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
 * Swagger 文档生成器
 * 生成 OpenAPI 3.0 规范的 JSON 文档
 */
class SwaggerGenerator
{
    protected ConfigInterface $config;
    protected SwaggerParser $parser;
    protected ZgwSchemaBuilder $schemaBuilder;

    public function __construct(
        ConfigInterface $config,
        SwaggerParser $parser,
        ZgwSchemaBuilder $schemaBuilder
    ) {
        $this->config = $config;
        $this->parser = $parser;
        $this->schemaBuilder = $schemaBuilder;
    }

    /**
     * 生成 Swagger 文档
     *
     * @param string|null $outputPath
     * @return array
     */
    public function generate(?string $outputPath = null): array
    {
        $apis = $this->parser->parse();
        $document = $this->buildDocument($apis);

        if ($outputPath) {
            $this->saveDocument($document, $outputPath);
        }

        return $document;
    }

    /**
     * 构建 OpenAPI 文档
     *
     * @param array $apis
     * @return array
     */
    protected function buildDocument(array $apis): array
    {
        $info = $this->config->get('swagger.info', []);
        $servers = $this->config->get('swagger.servers', []);

        // 确保 servers 不为空
        if (empty($servers)) {
            $servers = [
                ['url' => 'http://127.0.0.1:9506', 'description' => '开发环境']
            ];
        }

        $paths = $this->buildPaths($apis);
        // 如果没有扫描到任何 API，添加一个占位路径
        if (empty($paths)) {
            $paths = new \stdClass();
        }

        $document = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $info['title'] ?? 'Motong Server API',
                'version' => $info['version'] ?? '1.0.0',
                'description' => $info['description'] ?? 'Motong Server 接口文档 (ZGW协议)',
            ],
            'servers' => $servers,
            'tags' => $this->buildTags($apis),
            'paths' => $paths,
            'components' => $this->buildComponents(),
        ];

        if (!empty($info['contact'])) {
            $document['info']['contact'] = $info['contact'];
        }

        return $document;
    }

    /**
     * 构建标签列表
     *
     * @param array $apis
     * @return array
     */
    protected function buildTags(array $apis): array
    {
        $tags = [];
        $tagNames = [];

        foreach ($apis as $api) {
            foreach ($api['tags'] as $tag) {
                if (!in_array($tag, $tagNames)) {
                    $tagNames[] = $tag;
                    $tags[] = [
                        'name' => $tag,
                        'description' => '',
                    ];
                }
            }
        }

        return $tags;
    }

    /**
     * 构建路径
     *
     * @param array $apis
     * @return array
     */
    protected function buildPaths(array $apis): array
    {
        $paths = [];

        foreach ($apis as $api) {
            $path = '/' . str_replace('.', '/', $api['interfaceName']);
            $paths[$path] = [
                'post' => $this->buildOperation($api),
            ];
        }

        return $paths;
    }

    /**
     * 构建操作
     *
     * @param array $api
     * @return array
     */
    protected function buildOperation(array $api): array
    {
        $operation = [
            'tags' => $api['tags'],
            'summary' => $api['summary'],
            'description' => $this->buildOperationDescription($api),
            'operationId' => str_replace('.', '_', $api['interfaceName']),
            'requestBody' => $this->buildRequestBody($api),
            'responses' => $this->buildResponses($api),
        ];

        if ($api['deprecated']) {
            $operation['deprecated'] = true;
        }

        if ($api['auth']) {
            $operation['security'] = [['bearerAuth' => []]];
        }

        return $operation;
    }

    /**
     * 构建操作描述
     *
     * @param array $api
     * @return string
     */
    protected function buildOperationDescription(array $api): string
    {
        $desc = $api['description'] ?: $api['summary'];
        $desc .= "\n\n**ZGW 接口名**: `{$api['interfaceName']}`";

        if ($api['auth']) {
            $desc .= "\n\n**需要认证**: 是";
        }

        return $desc;
    }

    /**
     * 构建请求体
     *
     * @param array $api
     * @return array
     */
    protected function buildRequestBody(array $api): array
    {
        $schema = $this->schemaBuilder->buildRequestSchema(
            $api['interfaceName'],
            $api['params']
        );

        $example = $this->buildRequestExample($api);

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => $schema,
                    'example' => $example,
                ],
            ],
        ];
    }

    /**
     * 构建请求示例
     *
     * @param array $api
     * @return array
     */
    protected function buildRequestExample(array $api): array
    {
        $paramExample = [];
        foreach ($api['params'] as $param) {
            if (isset($param['example'])) {
                $paramExample[$param['name']] = $param['example'];
            } elseif (isset($param['default'])) {
                $paramExample[$param['name']] = $param['default'];
            } else {
                $paramExample[$param['name']] = $this->getDefaultExample($param['type']);
            }
        }

        return $this->schemaBuilder->buildRequestExample(
            $api['interfaceName'],
            $paramExample
        );
    }

    /**
     * 获取默认示例值
     *
     * @param string $type
     * @return mixed
     */
    protected function getDefaultExample(string $type)
    {
        $defaults = [
            'string' => '',
            'integer' => 0,
            'number' => 0.0,
            'boolean' => false,
            'array' => [],
            'object' => new \stdClass(),
        ];

        return $defaults[$type] ?? '';
    }

    /**
     * 构建响应
     *
     * @param array $api
     * @return array
     */
    protected function buildResponses(array $api): array
    {
        $responses = [];

        foreach ($api['responses'] as $response) {
            $code = (string) $response['code'];
            $schema = !empty($response['schema'])
                ? $this->schemaBuilder->buildResponseSchema($response['schema'])
                : $this->schemaBuilder->buildResponseSchema();

            $responses[$code] = [
                'description' => $response['description'],
                'content' => [
                    'application/json' => [
                        'schema' => $schema,
                    ],
                ],
            ];

            if (!empty($response['example'])) {
                $responses[$code]['content']['application/json']['example'] = $response['example'];
            }
        }

        // 添加错误响应
        if (!isset($responses['400'])) {
            $responses['400'] = [
                'description' => '请求参数错误',
                'content' => [
                    'application/json' => [
                        'schema' => $this->schemaBuilder->buildErrorResponseSchema(),
                    ],
                ],
            ];
        }

        return $responses;
    }

    /**
     * 构建组件
     *
     * @return array
     */
    protected function buildComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'JWT Token 认证',
                ],
            ],
            'schemas' => [
                'ZgwRequest' => [
                    'type' => 'object',
                    'description' => 'ZGW 协议请求格式',
                    'properties' => [
                        'version' => ['type' => 'string'],
                        'seqId' => ['type' => 'string'],
                        'timestamp' => ['type' => 'integer'],
                        'eventId' => ['type' => 'integer'],
                        'caller' => ['type' => 'string'],
                        'interface' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'param' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
                'ZgwResponse' => [
                    'type' => 'object',
                    'description' => 'ZGW 协议响应格式',
                    'properties' => [
                        'code' => ['type' => 'integer'],
                        'message' => ['type' => 'string'],
                        'data' => ['type' => 'object'],
                        'timestamp' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 保存文档到文件
     *
     * @param array $document
     * @param string $outputPath
     */
    protected function saveDocument(array $document, string $outputPath): void
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputPath, $json);
    }
}
