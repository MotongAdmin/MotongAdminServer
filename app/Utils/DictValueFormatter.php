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

namespace App\Utils;

use App\Constants\DictValueType;
use ZYProSoft\Exception\HyperfCommonException;
use App\Constants\ErrorCode;

/**
 * 字典值格式化工具类
 */
class DictValueFormatter
{
    /**
     * 根据值类型格式化字典值
     */
    public static function formatValue(string $value, int $valueType): mixed
    {
        switch ($valueType) {
            case DictValueType::STRING:
                return $value;
                
            case DictValueType::INTEGER:
                return self::formatToInteger($value);
                
            case DictValueType::FLOAT:
                return self::formatToFloat($value);
                
            case DictValueType::DECIMAL:
                return self::formatToDecimal($value);
                
            case DictValueType::JSON:
                return self::formatToJson($value);
                
            default:
                throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, '不支持的值类型: ' . $valueType);
        }
    }
    
    /**
     * 格式化字典数据列表
     */
    public static function formatDictDataList(array $dictDataList, int $valueType): array
    {
        foreach ($dictDataList as &$item) {
            if (isset($item['dict_value'])) {
                $item['dict_value'] = self::formatValue($item['dict_value'], $valueType);
            }
        }
        
        return $dictDataList;
    }
    
    /**
     * 格式化单个字典数据项
     */
    public static function formatDictDataItem(array $dictDataItem, int $valueType): array
    {
        if (isset($dictDataItem['dict_value'])) {
            $dictDataItem['dict_value'] = self::formatValue($dictDataItem['dict_value'], $valueType);
        }
        
        return $dictDataItem;
    }
    
    /**
     * 格式化为整型
     */
    private static function formatToInteger(string $value): int
    {
        if (!is_numeric($value)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, "无法将值 '{$value}' 转换为整型");
        }
        
        return (int) $value;
    }
    
    /**
     * 格式化为浮点型
     */
    private static function formatToFloat(string $value): float
    {
        if (!is_numeric($value)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, "无法将值 '{$value}' 转换为浮点型");
        }
        
        return (float) $value;
    }
    
    /**
     * 格式化为高精度浮点型（保留更多小数位）
     */
    private static function formatToDecimal(string $value): float
    {
        if (!is_numeric($value)) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, "无法将值 '{$value}' 转换为高精度浮点型");
        }
        
        return (float) $value;
    }
    
    /**
     * 格式化为JSON
     */
    private static function formatToJson(string $value): mixed
    {
        $decoded = json_decode($value, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HyperfCommonException(ErrorCode::BUSINESS_ERROR, "无法将值 '{$value}' 解析为JSON: " . json_last_error_msg());
        }
        
        return $decoded;
    }
    
    /**
     * 获取值类型的默认值
     */
    public static function getDefaultValue(int $valueType): mixed
    {
        switch ($valueType) {
            case DictValueType::STRING:
                return '';
            case DictValueType::INTEGER:
                return 0;
            case DictValueType::FLOAT:
            case DictValueType::DECIMAL:
                return 0.0;
            case DictValueType::JSON:
                return [];
            default:
                return null;
        }
    }
    
    /**
     * 验证值是否符合指定类型
     */
    public static function validateValue(mixed $value, int $valueType): bool
    {
        try {
            if (is_string($value)) {
                self::formatValue($value, $valueType);
            } else {
                // 如果不是字符串，先转换为字符串再验证
                self::formatValue((string) $value, $valueType);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
