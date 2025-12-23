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

class ConfigConstants
{
    //从系统配置表获取当前使用的云存储配置类型
    const STORAGE_KEY = 'cloud_storage';

    //从系统配置表获取当前使用的短信配置类型
    const SMS_KEY = 'sms_platform';
}