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
 * 端点配置模型
 */
class SysEndpointConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_endpoint_config';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_key',
        'name',
        'description',
        'endpoint_url',
        'request_method',
        'timeout',
        'headers',
        'auth_config',
        'extra_config',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'headers' => 'json',
        'auth_config' => 'json',
        'extra_config' => 'json',
        'timeout' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
} 