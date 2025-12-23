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

namespace App\Service\Base;

use ZYProSoft\Service\AbstractService;
use App\Constants\ConfigConstants;
use App\Constants\ErrorCode;
use App\Facade\ConfigUtil;
use App\Service\Base\Sms\SmsInterface;
use App\Service\Base\Sms\QiniuSms;
use App\Service\Base\Sms\AliyunSms;
use App\Constants\Constants;
use ZYProSoft\Exception\HyperfCommonException;

/**
 * 短信发送服务
 */
class SmsService extends AbstractService
{
    protected const SMS_CODE_TTL = 5 * 60;  // 验证码有效期5分钟

    protected const SMS_RESEND_TTL = 60;    // 重发间隔60秒

    /**
     * 获取当前短信发送实例
     * @throws \Exception
     * @return SmsInterface
     */
    protected function getSms(): SmsInterface
    {
        // 从系统配置获取当前使用的短信平台
        $smsProvider = ConfigUtil::get(ConfigConstants::SMS_KEY);
        
        if (empty($smsProvider)) {
            throw new \Exception('SMS provider not configured');
        }

        // 获取短信配置
        $smsConfig = ConfigUtil::getSmsConfig($smsProvider);
        
        if (!$smsConfig) {
            throw new \Exception("SMS config not found for provider: {$smsProvider}");
        }

        switch ($smsProvider) {
            case Constants::SMS_PROVIDER_QINIU:
                $sms = new QiniuSms($smsConfig);
                break;
            case Constants::SMS_PROVIDER_ALIYUN:
                $sms = new AliyunSms($smsConfig);
                break;
            default:
                throw new \Exception("Unsupported SMS provider: {$smsProvider}");
        }

        // 验证配置
        if (!$sms->validateConfig()) {
            throw new \Exception("Invalid SMS configuration for provider: {$smsProvider}");
        }

        return $sms;
    }

    /**
     * 快捷发送验证码
     * @param string $mobile 手机号
     * @return void
     */
    public function sendCode(string $mobile): void
    {
        // 检查是否刚刚发送过验证码
        $existCode = $this->cache->get($mobile . 'r');
        if (!empty($existCode)) {
            throw new HyperfCommonException(ErrorCode::ERROR_BUSINESS_SEND_SMS_CODE_LIMIT);
        }

        // 生成4位验证码
        $smsCode = "" . rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9);

        $sms = $this->getSms();

        // 发送验证码
        $sms->sendVerificationCode($mobile, $smsCode);

        // 写入缓存以便后续校验
        $this->cache->set($mobile, $smsCode, self::SMS_CODE_TTL);
        $this->cache->set($mobile . 'r', '1', self::SMS_RESEND_TTL);
    }

    /**
     * 验证提交的验证码是否正确
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @return void
     */
    public function checkCode(string $mobile, string $code): void 
    {
        $existCode = $this->cache->get($mobile);
        if (empty($existCode)) {
            throw new HyperfCommonException(ErrorCode::ERROR_BUSINESS_SMS_CODE_DID_EXPIRED);
        }

        if ($existCode !== $code) {
            throw new HyperfCommonException(ErrorCode::ERROR_BUSINESS_SMS_CODE_NOT_VALIDATE);
        }

        // 验证成功后删除缓存
        $this->cache->delete($mobile);
    }
}
