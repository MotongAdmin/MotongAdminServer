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

use App\Annotation\ApiDoc;
use App\Annotation\ApiGroup;
use App\Annotation\ApiParam;
use App\Annotation\ApiResponse;
use App\Annotation\Description;
use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Annotation\AutoController;
use ReflectionClass;
use ReflectionMethod;

/**
 * Swagger 注解解析器
 * 扫描控制器并解析 API 文档注解
 */
class SwaggerParser
{
    protected ConfigInterface $config;
    protected AnnotationReader $reader;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->reader = new AnnotationReader();
    }

    /**
     * 解析所有控制器
     *
     * @return array
     */
    public function parse(): array
    {
        $apis = [];
        $scanPaths = $this->config->get('swagger.scan.paths', []);
        $excludePaths = $this->config->get('swagger.scan.exclude', []);

        foreach ($scanPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            $apis = array_merge($apis, $this->scanDirectory($path, $excludePaths));
        }

        return $apis;
    }

    /**
     * 扫描目录
     *
     * @param string $directory
     * @param array $excludePaths
     * @return array
     */
    protected function scanDirectory(string $directory, array $excludePaths = []): array
    {
        $apis = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getRealPath();

            // 检查是否在排除路径中
            foreach ($excludePaths as $excludePath) {
                if (strpos($filePath, $excludePath) !== false) {
                    continue 2;
                }
            }

            $className = $this->getClassNameFromFile($filePath);
            if ($className && class_exists($className)) {
                $classApis = $this->parseController($className);
                if (!empty($classApis)) {
                    $apis = array_merge($apis, $classApis);
                }
            }
        }

        return $apis;
    }

    /**
     * 从文件获取类名
     *
     * @param string $filePath
     * @return string|null
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // 匹配命名空间
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // 匹配类名
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            return $namespace ? $namespace . '\\' . $className : $className;
        }

        return null;
    }

    /**
     * 解析控制器
     *
     * @param string $className
     * @return array
     */
    public function parseController(string $className): array
    {
        $apis = [];

        try {
            $reflection = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            return $apis;
        }

        // 检查是否有 AutoController 注解
        $autoController = $this->reader->getClassAnnotation($reflection, AutoController::class);
        if (!$autoController) {
            return $apis;
        }

        // 获取控制器前缀
        $prefix = $autoController->prefix ?? '';
        $prefix = trim($prefix, '/');

        // 获取控制器分组注解
        $apiGroup = $this->reader->getClassAnnotation($reflection, ApiGroup::class);
        $groupName = $apiGroup ? $apiGroup->name : $this->extractGroupFromPrefix($prefix);
        $groupDescription = $apiGroup ? $apiGroup->description : '';

        // 解析方法
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            // 跳过继承的方法和魔术方法
            if ($method->class !== $className || strpos($method->getName(), '__') === 0) {
                continue;
            }

            $apiInfo = $this->parseMethod($method, $prefix, $groupName);
            if ($apiInfo) {
                $apis[] = $apiInfo;
            }
        }

        return $apis;
    }

    /**
     * 解析方法
     *
     * @param ReflectionMethod $method
     * @param string $prefix
     * @param string $groupName
     * @return array|null
     */
    protected function parseMethod(ReflectionMethod $method, string $prefix, string $groupName): ?array
    {
        $methodName = $method->getName();

        // 获取 ApiDoc 注解
        $apiDoc = $this->reader->getMethodAnnotation($method, ApiDoc::class);

        // 如果没有任何文档注解，跳过
        if (!$apiDoc) {
            return null;
        }

        // 获取 Description 注解
        $description = $this->reader->getMethodAnnotation($method, Description::class);

        // 构建接口名称 (ZGW 三段式)
        $interfaceName = $this->buildInterfaceName($prefix, $methodName);

        // 获取参数注解
        $params = $this->parseParams($method);

        // 获取响应注解
        $responses = $this->parseResponses($method);

        // 检查是否需要认证
        $requiresAuth = $this->checkRequiresAuth($method);

        return [
            'interfaceName' => $interfaceName,
            'prefix' => $prefix,
            'methodName' => $methodName,
            'summary' => $apiDoc ? $apiDoc->summary : ($description ? $description->value : ''),
            'description' => $apiDoc ? $apiDoc->description : '',
            'tags' => $apiDoc && !empty($apiDoc->tags) ? $apiDoc->tags : [$groupName],
            'deprecated' => $apiDoc ? $apiDoc->deprecated : false,
            'auth' => $apiDoc ? $apiDoc->auth : $requiresAuth,
            'params' => $params,
            'responses' => $responses,
        ];
    }

    /**
     * 解析参数注解
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function parseParams(ReflectionMethod $method): array
    {
        $params = [];
        $annotations = $this->reader->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ApiParam) {
                $params[] = [
                    'name' => $annotation->name,
                    'type' => $annotation->type,
                    'required' => $annotation->required,
                    'description' => $annotation->description,
                    'example' => $annotation->example,
                    'default' => $annotation->default,
                    'enum' => $annotation->enum,
                    'minimum' => $annotation->minimum,
                    'maximum' => $annotation->maximum,
                    'minLength' => $annotation->minLength,
                    'maxLength' => $annotation->maxLength,
                    'items' => $annotation->items,
                ];
            }
        }

        return $params;
    }

    /**
     * 解析响应注解
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function parseResponses(ReflectionMethod $method): array
    {
        $responses = [];
        $annotations = $this->reader->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ApiResponse) {
                $responses[] = [
                    'code' => $annotation->code,
                    'description' => $annotation->description,
                    'schema' => $annotation->schema,
                    'example' => $annotation->example,
                ];
            }
        }

        // 如果没有定义响应，添加默认响应
        if (empty($responses)) {
            $responses[] = [
                'code' => 200,
                'description' => '成功',
                'schema' => [],
                'example' => [],
            ];
        }

        return $responses;
    }

    /**
     * 检查方法是否需要认证
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    protected function checkRequiresAuth(ReflectionMethod $method): bool
    {
        $parameters = $method->getParameters();
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if (strpos($typeName, 'AuthedRequest') !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 构建 ZGW 三段式接口名称
     *
     * @param string $prefix
     * @param string $methodName
     * @return string
     */
    protected function buildInterfaceName(string $prefix, string $methodName): string
    {
        // 将 /admin/user 转换为 admin.user
        $parts = array_filter(explode('/', $prefix));
        $parts[] = $methodName;
        return implode('.', $parts);
    }

    /**
     * 从前缀提取分组名称
     *
     * @param string $prefix
     * @return string
     */
    protected function extractGroupFromPrefix(string $prefix): string
    {
        $parts = array_filter(explode('/', $prefix));
        if (count($parts) >= 2) {
            return ucfirst($parts[0]) . ' - ' . ucfirst($parts[1]);
        }
        return ucfirst($parts[0] ?? 'Default');
    }
}
