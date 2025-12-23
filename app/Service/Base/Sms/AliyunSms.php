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
use ZYProSoft\Exception\HyperfCommonException;
use App\Constants\ErrorCode;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Utils\ApplicationContext;

/**
 * 阿里云短信发送实现类 - 基于Hyperf协程HTTP客户端
 */
class AliyunSms implements SmsInterface
{
    protected SysSmsConfig $config;
    protected array $templateMap;
    private string $endpoint = 'https://dysmsapi.aliyuncs.com';
    private string $action = 'SendSms';
    private string $version = '2017-05-25';

    protected ClientFactory $clientFactory;

    public function __construct(SysSmsConfig $config)
    {
        $this->config = $config;
        $this->templateMap = $config->template_map ?: [];
        $this->clientFactory = ApplicationContext::getContainer()->get(ClientFactory::class);
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

        try {
            // 构建请求参数
            $requestParams = [
                'PhoneNumbers' => $phone,
                'SignName' => $this->config->sign_name,
                'TemplateCode' => $templateId,
                'TemplateParam' => json_encode($params, JSON_UNESCAPED_UNICODE)
            ];

            // 发送请求
            $response = $this->sendRequest($requestParams);
            
            Log::info("阿里云短信发送结果: " . json_encode($response));
            
            // 检查发送结果
            if ($response['Code'] !== 'OK') {
                Log::error("阿里云短信发送失败: " . ($response['Message'] ?? 'Unknown error'));
                throw new HyperfCommonException(ErrorCode::ERROR_BUSINESS_SEND_SMS_CODE_FAIL);
            }
            
        } catch (\Exception $e) {
            Log::error("阿里云短信发送异常: " . $e->getMessage());
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

    /**
     * 发送HTTP请求到阿里云短信服务 (V3版本签名)
     * @param array $params 请求参数
     * @return array 响应结果
     */
    private function sendRequest(array $params): array
    {
        $clientFactory = ApplicationContext::getContainer()->get(ClientFactory::class);
        $client = $clientFactory->create();

        // 构建请求体
        $requestBody = json_encode($params, JSON_UNESCAPED_UNICODE);
        
        // 生成时间戳和随机数
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $nonce = uniqid();
        
        // 构建请求头
        $headers = [
            'Content-Type' => 'application/json',
            'x-acs-action' => $this->action,
            'x-acs-version' => $this->version,
            'x-acs-signature-nonce' => $nonce,
            'x-acs-date' => $timestamp,
            'x-acs-content-sha256' => hash('sha256', $requestBody),
        ];

        // 生成Authorization头
        $authorization = $this->generateV3Signature($headers, $requestBody, $timestamp);
        $headers['Authorization'] = $authorization;

        // 调试日志
        Log::info('阿里云短信V3请求参数: ' . json_encode([
            'endpoint' => $this->endpoint,
            'headers' => $headers,
            'body' => $requestBody
        ]));

        // 发送请求
        $response = $client->post($this->endpoint, [
            'headers' => $headers,
            'body' => $requestBody,
            'timeout' => 10
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from Aliyun SMS API');
        }

        return $result;
    }

    /**
     * 生成阿里云API V3版本签名
     * @param array $headers 请求头
     * @param string $requestBody 请求体
     * @param string $timestamp 时间戳
     * @return string Authorization头
     */
    private function generateV3Signature(array $headers, string $requestBody, string $timestamp): string
    {
        // 1. 构建规范请求
        $canonicalRequest = $this->buildCanonicalRequest($headers, $requestBody);
        
        // 2. 构建待签名字符串
        $stringToSign = $this->buildStringToSign($canonicalRequest, $timestamp);
        
        // 3. 计算签名
        $signature = $this->calculateSignature($stringToSign, $timestamp);
        
        // 4. 构建Authorization头
        return "ACS3-HMAC-SHA256 Credential={$this->config->access_key},SignedHeaders=host;x-acs-action;x-acs-content-sha256;x-acs-date;x-acs-signature-nonce;x-acs-version,Signature={$signature}";
    }

    /**
     * 构建规范请求
     */
    private function buildCanonicalRequest(array $headers, string $requestBody): string
    {
        $method = 'POST';
        $uri = '/';
        $queryString = '';
        
        // 构建规范请求头
        $canonicalHeaders = '';
        $signedHeaders = [];
        
        // 添加host头
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $canonicalHeaders .= "host:{$host}\n";
        $signedHeaders[] = 'host';
        
        // 添加其他必需的头
        $requiredHeaders = ['x-acs-action', 'x-acs-content-sha256', 'x-acs-date', 'x-acs-signature-nonce', 'x-acs-version'];
        foreach ($requiredHeaders as $headerName) {
            if (isset($headers[$headerName])) {
                $canonicalHeaders .= "{$headerName}:{$headers[$headerName]}\n";
                $signedHeaders[] = $headerName;
            }
        }
        
        $signedHeadersStr = implode(';', $signedHeaders);
        $payloadHash = hash('sha256', $requestBody);
        
        return "{$method}\n{$uri}\n{$queryString}\n{$canonicalHeaders}\n{$signedHeadersStr}\n{$payloadHash}";
    }

    /**
     * 构建待签名字符串
     */
    private function buildStringToSign(string $canonicalRequest, string $timestamp): string
    {
        $algorithm = 'ACS3-HMAC-SHA256';
        $date = substr($timestamp, 0, 8);
        $credentialScope = "{$date}/cn-hangzhou/sms/aliyun3_request";
        $canonicalRequestHash = hash('sha256', $canonicalRequest);
        
        return "{$algorithm}\n{$timestamp}\n{$credentialScope}\n{$canonicalRequestHash}";
    }

    /**
     * 计算签名
     */
    private function calculateSignature(string $stringToSign, string $timestamp): string
    {
        $date = substr($timestamp, 0, 8);
        $dateKey = hash_hmac('sha256', $date, "aliyun3{$this->config->secret_key}", true);
        $regionKey = hash_hmac('sha256', 'cn-hangzhou', $dateKey, true);
        $serviceKey = hash_hmac('sha256', 'sms', $regionKey, true);
        $signingKey = hash_hmac('sha256', 'aliyun3_request', $serviceKey, true);
        
        return hash_hmac('sha256', $stringToSign, $signingKey);
    }

}