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

namespace App\Service\Base\Sms;

/**
 * 短信发送接口
 * 定义统一的短信发送接口规范，所有具体的短信服务商实现都需要实现此接口
 */
interface SmsInterface
{
    /**
     * 短信类型常量
     */
    public const SMS_TYPE_VERIFICATION = 'verification';  // 验证码短信

    /**
     * 发送短信
     * @param string $phone 手机号
     * @param string $templateType 模板类型
     * @param array $params 模板参数
     */
    public function sendSms(string $phone, string $templateType, array $params = []): void;

    /**
     * 发送验证码短信
     * @param string $phone 手机号
     * @param string $code 验证码
     */
    public function sendVerificationCode(string $phone, string $code): void;

    /**
     * 获取模板ID
     * @param string $templateType 模板类型
     * @return string|null 模板ID
     */
    public function getTemplateId(string $templateType): ?string;

    /**
     * 验证配置是否正确
     * @return bool 配置是否有效
     */
    public function validateConfig(): bool;
}
