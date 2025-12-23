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

namespace App\Annotation\Collector;

use App\Annotation\Description;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MetadataCollector;

class DescriptionCollector extends MetadataCollector
{
    /**
     * @var array
     */
    protected static $container = [];

    /**
     * 收集方法的Description注解
     */
    public static function collectMethod(string $class, string $method, Description $annotation): void
    {
        if (!isset(static::$container[$class])) {
            static::$container[$class] = [];
        }
        
        static::$container[$class][$method] = $annotation->value;
    }

    /**
     * 获取方法的Description注解值
     */
    public static function getMethodDescription(string $class, string $method): string
    {
        return static::$container[$class][$method] ?? '';
    }
    
    /**
     * 获取所有Description注解
     */
    public static function all(): array
    {
        return static::$container;
    }
    
    /**
     * 获取类的所有方法Description注解
     */
    public static function getClassMethodDescriptions(string $class): array
    {
        return static::$container[$class] ?? [];
    }
} 