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

namespace App\Model;

/**
 * 字段字典关联模型
 */
class SysFieldDict extends Model
{
    protected $table = 'sys_field_dict';
    
    protected $fillable = [
        'table_name',
        'field_name',
        'dict_type',
        'description',
        'status'
    ];
    
    /**
     * 获取字段对应的字典类型
     */
    public static function getDictType(string $tableName, string $fieldName): ?string
    {
        $mapping = self::query()
            ->where('table_name', $tableName)
            ->where('field_name', $fieldName)
            ->first();
            
        return $mapping ? $mapping->dict_type : null;
    }
    
    /**
     * 获取表的所有字典映射
     */
    public static function getTableDictMapping(string $tableName): array
    {
        $mappings = self::query()
            ->where('table_name', $tableName)
            ->get();
            
        $result = [];
        foreach ($mappings as $mapping) {
            $result[$mapping->field_name] = $mapping->dict_type;
        }
        
        return $result;
    }
    
    /**
     * 获取使用指定字典类型的所有字段
     */
    public static function getFieldsByDictType(string $dictType): array
    {
        return self::query()
            ->where('dict_type', $dictType)
            ->get()
            ->toArray();
    }
} 