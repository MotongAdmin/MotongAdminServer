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

use App\Annotation\Collector\DescriptionCollector;
use App\Annotation\Description;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\DbConnection\Db;
use App\Model\SysApi;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class ScanApiCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var bool
     */
    protected $debug = false;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('scan:api');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Scan and record API interfaces from Admin controllers');
        $this->addOption('debug', 'd', InputOption::VALUE_NONE, '启用调试模式，显示更多信息');
        $this->addOption('group', 'g', InputOption::VALUE_OPTIONAL, '指定API分组名称，默认为admin', 'admin');
        $this->addOption('show-annotations', null, InputOption::VALUE_NONE, '显示所有收集到的注解');
    }

    public function handle()
    {
        $this->debug = $this->input->getOption('debug');
        $apiGroup = $this->input->getOption('group');
        $showAnnotations = $this->input->getOption('show-annotations');
        
        // 如果启用了显示注解选项，打印所有收集到的注解
        if ($showAnnotations) {
            $this->showAllAnnotations();
            return;
        }
        
        $this->line('Starting to scan Admin controllers...', 'info');

        // Get all classes with Controller or AutoController annotation
        $controllers = array_merge(
            AnnotationCollector::getClassesByAnnotation(Controller::class),
            AnnotationCollector::getClassesByAnnotation(AutoController::class)
        );

        if ($this->debug) {
            $this->line('Found ' . count($controllers) . ' controllers with annotations', 'info');
        }

        $apiCount = 0;

        foreach ($controllers as $class => $annotation) {
            // Only process controllers in Admin directory
            if (strpos($class, 'App\\Controller\\Admin\\') !== 0) {
                if ($this->debug) {
                    $this->line("Skipping non-Admin controller: {$class}", 'comment');
                }
                continue;
            }

            $this->line("Processing controller: {$class}", 'info');

            try {
                $reflectionClass = new ReflectionClass($class);
                
                if (!$reflectionClass->isInstantiable()) {
                    $this->line("Skipping non-instantiable class: {$class}", 'comment');
                    continue;
                }
                
                $prefix = '';

                // Get controller prefix
                if ($annotation instanceof AutoController) {
                    $prefix = $annotation->prefix;
                    if ($this->debug) {
                        $this->line("AutoController prefix: {$prefix}", 'info');
                    }
                } elseif ($annotation instanceof Controller) {
                    $prefix = $annotation->prefix;
                    if ($this->debug) {
                        $this->line("Controller prefix: {$prefix}", 'info');
                    }
                }

                // Extract controller name for API naming
                $controllerName = basename(str_replace('\\', '/', $class));
                $controllerName = str_replace('Controller', '', $controllerName);

                // Process each public method in the controller
                foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                    // Skip parent class methods
                    if ($reflectionMethod->class !== $class) {
                        continue;
                    }
                    
                    // Skip magic methods
                    $methodName = $reflectionMethod->getName();
                    if (strpos($methodName, '__') === 0) {
                        continue;
                    }
                    
                    // Check if method has RequestMapping annotation
                    $methodAnnotation = null;
                    $methodAnnotations = AnnotationCollector::getMethodsByAnnotation(RequestMapping::class);
                    $fullMethodName = $class . '::' . $methodName;
                    
                    if (isset($methodAnnotations[$fullMethodName])) {
                        $methodAnnotation = $methodAnnotations[$fullMethodName];
                    }
                    
                    // 根据ZGW协议，所有接口都是POST方法
                    $httpMethod = 'POST';
                    
                    // 确定API路径
                    $path = $prefix . '/' . $methodName;
                    if ($methodAnnotation !== null && !empty($methodAnnotation->path)) {
                        $path = $prefix . '/' . $methodAnnotation->path;
                    }
                    
                    if ($this->debug) {
                        $this->line("Processing endpoint: {$httpMethod} {$path}", 'info');
                    }
                    
                    // 从注释中获取描述
                    $description = $this->getMethodDescription($class, $methodName, $reflectionMethod);
                    
                    if ($this->debug && !empty($description)) {
                        $this->line("  Description: {$description}", 'info');
                    }

                    // Generate API name
                    $interface = substr($path, 1);
                    $apiName = str_replace('/', '.', $interface);

                    //取第二个为group分组
                    $apiGroup = explode('/', $interface)[1];

                    // Record API to database
                    $this->recordApi([
                        'api_path' => $path,
                        'api_method' => $httpMethod,
                        'api_name' => $apiName,
                        'api_group' => $apiGroup,
                        'description' => $description,
                        'status' => 1,
                    ]);

                    $apiCount++;
                }
            } catch (\Throwable $e) {
                $this->error("Error processing controller {$class}: " . $e->getMessage());
            }
        }

        $this->line("Scan completed! Total {$apiCount} APIs recorded.", 'info');
    }
    
    /**
     * 显示所有收集到的注解
     */
    protected function showAllAnnotations(): void
    {
        $this->line('Showing all collected annotations...', 'info');
        
        // 显示Description收集器中的注解
        $descriptions = DescriptionCollector::all();
        $this->line('Description annotations from DescriptionCollector: ' . count($descriptions), 'info');
        foreach ($descriptions as $class => $methods) {
            $this->line("Class: {$class}", 'info');
            foreach ($methods as $method => $value) {
                $this->line("  Method: {$method}, Description: {$value}", 'info');
            }
        }
        
        // 显示AnnotationCollector中的Description注解
        $this->line('', 'info');
        $this->line('Description annotations from AnnotationCollector:', 'info');
        $methodAnnotations = AnnotationCollector::getMethodsByAnnotation(Description::class);
        $this->line('Total methods with Description annotation: ' . count($methodAnnotations), 'info');
        foreach ($methodAnnotations as $methodKey => $annotation) {
            $this->line("Method: {$methodKey}", 'info');
            $this->line("  Value: " . ($annotation instanceof Description ? $annotation->value : 'N/A'), 'info');
        }
        
        // 显示所有控制器类的方法注解
        $this->line('', 'info');
        $this->line('Method annotations in Admin controllers:', 'info');
        $controllers = array_merge(
            AnnotationCollector::getClassesByAnnotation(Controller::class),
            AnnotationCollector::getClassesByAnnotation(AutoController::class)
        );
        
        foreach ($controllers as $class => $annotation) {
            if (strpos($class, 'App\\Controller\\Admin\\') === 0) {
                $this->line("Controller: {$class}", 'info');
                
                try {
                    $reflectionClass = new ReflectionClass($class);
                    foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        if ($method->class === $class && strpos($method->getName(), '__') !== 0) {
                            $methodName = $method->getName();
                            $this->line("  Method: {$methodName}", 'info');
                            
                            // 获取方法的所有注解
                            $annotations = AnnotationCollector::getClassMethodAnnotation($class, $methodName);
                            if (!empty($annotations)) {
                                $this->line("    Annotations: " . count($annotations), 'info');
                                
                                foreach ($annotations as $annotationClass => $annotation) {
                                    $this->line("      {$annotationClass}: " . get_class($annotation), 'info');
                                    if ($annotation instanceof Description) {
                                        $this->line("        Value: {$annotation->value}", 'info');
                                    }
                                }
                            } else {
                                $this->line("    No annotations found", 'comment');
                            }
                            
                            // 检查方法的文档注释
                            $docComment = $method->getDocComment();
                            if ($docComment) {
                                $this->line("    DocComment: " . substr(str_replace("\n", " ", $docComment), 0, 100) . "...", 'comment');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("Error processing controller {$class}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * 获取方法的描述信息
     */
    protected function getMethodDescription(string $class, string $methodName, ReflectionMethod $reflectionMethod): string
    {
        // 首先尝试从DescriptionCollector中获取描述
        $description = DescriptionCollector::getMethodDescription($class, $methodName);
        if (!empty($description)) {
            if ($this->debug) {
                $this->line("    Found Description from collector: {$description}", 'info');
            }
            return $description;
        }
        
        try {
            // 1. 直接从注解收集器中获取Description注解
            $annotations = AnnotationCollector::getClassMethodAnnotation($class, $methodName);
            if (isset($annotations[Description::class])) {
                $descriptionAnnotation = $annotations[Description::class];
                if ($descriptionAnnotation instanceof Description) {
                    $description = $descriptionAnnotation->value;
                    if ($this->debug) {
                        $this->line("    Found Description annotation: {$description}", 'info');
                    }
                    return $description;
                }
            }
            
            // 2. 如果上面的方法没有找到，尝试从docComment中解析
            $docComment = $reflectionMethod->getDocComment();
            if ($docComment) {
                // 尝试匹配 @Description(value="xxx") 格式
                if (preg_match('/@Description\s*\(\s*value\s*=\s*"([^"]+)"\s*\)/i', $docComment, $matches)) {
                    $description = $matches[1];
                    if ($this->debug) {
                        $this->line("    Parsed from DocComment (format 1): {$description}", 'info');
                    }
                    return $description;
                }
                
                // 尝试匹配 @Description("xxx") 格式
                if (preg_match('/@Description\s*\(\s*"([^"]+)"\s*\)/i', $docComment, $matches)) {
                    $description = $matches[1];
                    if ($this->debug) {
                        $this->line("    Parsed from DocComment (format 2): {$description}", 'info');
                    }
                    return $description;
                }
                
                // 尝试匹配 @description xxx 格式
                if (preg_match('/@description\s+(.+?)($|\n)/i', $docComment, $matches)) {
                    $description = trim($matches[1]);
                    if ($this->debug) {
                        $this->line("    Parsed from DocComment (format 3): {$description}", 'info');
                    }
                    return $description;
                }
            }
        } catch (\Throwable $e) {
            if ($this->debug) {
                $this->error("Error getting description for {$class}::{$methodName}: " . $e->getMessage());
            }
        }
        
        return '';
    }

    protected function recordApi(array $data)
    {
        try {
            // 使用SysApi模型的字段进行更新或插入
            SysApi::updateOrCreate(
                [
                    'api_path' => $data['api_path'],
                    'api_method' => $data['api_method']
                ],
                $data
            );
            
            if ($this->debug) {
                $this->line("Recorded API: {$data['api_method']} {$data['api_path']}", 'info');
                if (!empty($data['description'])) {
                    $this->line("  With description: {$data['description']}", 'info');
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to record API {$data['api_path']}: " . $e->getMessage());
        }
    }
} 