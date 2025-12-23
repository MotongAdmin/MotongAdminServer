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

use App\Service\Base\Sms\SmsInterface;
use App\Model\SysSmsConfig;
use ZYProSoft\Log\Log;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use ZYProSoft\Exception\HyperfCommonException;
use App\Constants\ErrorCode;

/**
 * 七牛云短信发送实现类
 */
class QiniuSms implements SmsInterface
{
    protected SysSmsConfig $config;
    protected array $templateMap;

    public function __construct(SysSmsConfig $config)
    {
        $this->config = $config;
        $this->templateMap = $config->template_map ?? [];
    }

    /**
     * 发送短信
     * @param string $phone 手机号
     * @param string $templateType 模板类型
     * @param array $params 模板参数
     */
    public function sendSms(string $phone, string $templateType, array $params = []): void
    {
        $templateId = $this->getTemplateId($templateType);
        
        if (!$templateId) {
            throw new \Exception("Template not found for type: {$templateType}");
        }

        // 这里实现具体的七牛云短信发送逻辑
        $auth = new Auth($this->config->access_key, $this->config->secret_key);
        $smsService = new Sms($auth);
       
        [$result, $error] = $smsService->sendMessage($templateId, [$phone], $params);
        if (isset($error)) {
            Log::info("发送短信错误:".json_encode($error));
            throw new HyperfCommonException(ErrorCode::ERROR_BUSINESS_SEND_SMS_CODE_FAIL);
        }

        Log::info("短信发送结果:".json_encode($result));
        if (!isset($result['job_id'])) {
            throw new HyperfCommonException(ErrorCode::ERROR_BUSINESS_SEND_SMS_CODE_FAIL);
        }
    }

    /**
     * 发送验证码短信
     * @param string $phone 手机号
     * @param string $code 验证码
     */
    public function sendVerificationCode(string $phone, string $code): void
    {
        $this->sendSms($phone, self::SMS_TYPE_VERIFICATION, ['code' => $code]);
    }

    /**
     * 获取模板ID
     * @param string $templateType 模板类型
     * @return string|null 模板ID
     */
    public function getTemplateId(string $templateType): ?string
    {
        return $this->templateMap[$templateType] ?? null;
    }

    /**
     * 验证配置是否正确
     * @return bool 配置是否有效
     */
    public function validateConfig(): bool
    {
        return !empty($this->config->access_key) 
            && !empty($this->config->secret_key) 
            && !empty($this->config->sign_name);
    }
}
