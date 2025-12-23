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

namespace App\Constants;

/**
 * 字典值类型常量
 */
class DictValueType
{
    /**
     * 字符串类型
     */
    const STRING = 1;
    
    /**
     * 整型
     */
    const INTEGER = 2;
    
    /**
     * 浮点型
     */
    const FLOAT = 3;
    
    /**
     * 高精度浮点型
     */
    const DECIMAL = 4;
    
    /**
     * JSON字符串
     */
    const JSON = 5;
    
    /**
     * 获取所有值类型
     */
    public static function getAllTypes(): array
    {
        return [
            self::STRING => '字符串',
            self::INTEGER => '整型',
            self::FLOAT => '浮点型',
            self::DECIMAL => '高精度浮点型',
            self::JSON => 'JSON字符串',
        ];
    }
    
    /**
     * 获取值类型名称
     */
    public static function getTypeName(int $type): string
    {
        $types = self::getAllTypes();
        return $types[$type] ?? '未知类型';
    }
    
    /**
     * 验证值类型是否有效
     */
    public static function isValidType(int $type): bool
    {
        return in_array($type, array_keys(self::getAllTypes()));
    }
}
