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

namespace App\Validator;

use App\Constants\DictValueType;
use App\Model\SysDictType;
use App\Utils\DictValueFormatter;
use Hyperf\Validation\Validator;

/**
 * 字典数据验证器
 */
class DictDataValidator
{
    /**
     * 验证字典值是否符合字典类型的值类型要求
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return bool
     */
    public static function validateDictValue(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        // 获取字典类型
        $dictType = $parameters[0] ?? null;
        if (!$dictType) {
            return false;
        }

        // 查询字典类型信息
        $dictTypeModel = SysDictType::where('dict_type', $dictType)->first();
        if (!$dictTypeModel) {
            return false;
        }

        // 获取值类型
        $valueType = $dictTypeModel->value_type ?? DictValueType::STRING;

        // 验证值是否符合类型要求
        try {
            DictValueFormatter::validateValue($value, $valueType);
            return true;
        } catch (\InvalidArgumentException $e) {
            // 设置自定义错误消息
            $validator->errors()->add($attribute, self::getValueTypeErrorMessage($valueType, $e->getMessage()));
            return false;
        }
    }

    /**
     * 获取值类型错误消息
     *
     * @param int $valueType
     * @param string $originalMessage
     * @return string
     */
    private static function getValueTypeErrorMessage(int $valueType, string $originalMessage): string
    {
        $typeNames = [
            DictValueType::STRING => '字符串',
            DictValueType::INTEGER => '整数',
            DictValueType::FLOAT => '浮点数',
            DictValueType::DECIMAL => '高精度浮点数',
            DictValueType::JSON => 'JSON格式'
        ];

        $typeName = $typeNames[$valueType] ?? '未知类型';
        
        return "字典值必须是有效的{$typeName}格式";
    }

    /**
     * 验证字典值并返回格式化后的值
     *
     * @param mixed $value
     * @param string $dictType
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function validateAndFormatValue($value, string $dictType)
    {
        // 查询字典类型信息
        $dictTypeModel = SysDictType::where('dict_type', $dictType)->first();
        if (!$dictTypeModel) {
            throw new \InvalidArgumentException("字典类型 {$dictType} 不存在");
        }

        // 获取值类型
        $valueType = $dictTypeModel->value_type ?? DictValueType::STRING;

        // 验证并格式化值
        DictValueFormatter::validateValue($value, $valueType);
        return DictValueFormatter::formatValue($value, $valueType);
    }

    /**
     * 获取字典类型的值类型验证规则
     *
     * @param string $dictType
     * @return string
     */
    public static function getValueTypeValidationRule(string $dictType): string
    {
        // 查询字典类型信息
        $dictTypeModel = SysDictType::where('dict_type', $dictType)->first();
        if (!$dictTypeModel) {
            return 'string|max:100';
        }

        $valueType = $dictTypeModel->value_type ?? DictValueType::STRING;

        switch ($valueType) {
            case DictValueType::INTEGER:
                return 'integer';
            case DictValueType::FLOAT:
                return 'numeric';
            case DictValueType::DECIMAL:
                return 'numeric';
            case DictValueType::JSON:
                return 'string|json';
            case DictValueType::STRING:
            default:
                return 'string|max:100';
        }
    }

    /**
     * 获取字典类型的值类型错误消息
     *
     * @param string $dictType
     * @return string
     */
    public static function getValueTypeErrorMessageByDictType(string $dictType): string
    {
        // 查询字典类型信息
        $dictTypeModel = SysDictType::where('dict_type', $dictType)->first();
        if (!$dictTypeModel) {
            return '字典值格式错误';
        }

        $valueType = $dictTypeModel->value_type ?? DictValueType::STRING;

        $typeNames = [
            DictValueType::STRING => '字符串',
            DictValueType::INTEGER => '整数',
            DictValueType::FLOAT => '浮点数',
            DictValueType::DECIMAL => '高精度浮点数',
            DictValueType::JSON => 'JSON格式'
        ];

        $typeName = $typeNames[$valueType] ?? '字符串';
        
        return "字典值必须是有效的{$typeName}格式";
    }
}
