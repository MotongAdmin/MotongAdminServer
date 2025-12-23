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

namespace App\Listener;

use App\Annotation\Collector\DescriptionCollector;
use App\Annotation\Description;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * @Listener
 */
class DescriptionAnnotationListener implements ListenerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        // 获取名为 default 的 Logger
        $this->logger = $loggerFactory->get('default');
    }
    
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        try {
            // 从注解收集器中获取所有类方法注解
            $classMethodAnnotations = AnnotationCollector::getContainer();
            
            // 遍历所有类
            foreach ($classMethodAnnotations as $class => $classAnnotations) {
                // 检查是否有方法注解
                if (isset($classAnnotations['_m'])) {
                    $methodAnnotations = $classAnnotations['_m'];
                    
                    // 遍历所有方法
                    foreach ($methodAnnotations as $method => $annotations) {
                        // 检查是否有Description注解
                        if (isset($annotations[Description::class])) {
                            $description = $annotations[Description::class];
                            
                            // 收集Description注解
                            if ($description instanceof Description) {
                                DescriptionCollector::collectMethod($class, $method, $description);
                                $this->logger->info("Collected Description for {$class}::{$method}: {$description->value}");
                            }
                        }
                    }
                }
            }
            
            $this->logger->info("Description annotations collected successfully");
        } catch (\Throwable $e) {
            $this->logger->error("Error collecting Description annotations: {$e->getMessage()}");
        }
    }
} 