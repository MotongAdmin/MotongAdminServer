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

namespace App\Controller\Admin\System;

use ZYProSoft\Http\AuthedRequest;
use App\Service\Admin\System\EndpointConfigService;
use App\Service\Admin\System\SmsConfigService;
use App\Service\Admin\System\StorageConfigService;
use App\Service\Admin\System\MiniappConfigService;
use App\Service\Admin\System\PaymentConfigService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 平台配置总览控制器
 * @AutoController(prefix="/system/platform")
 */
class PlatformConfigController extends AbstractController
{
    /**
     * @Inject
     * @var EndpointConfigService
     */
    protected EndpointConfigService $endpointService;

    /**
     * @Inject
     * @var SmsConfigService
     */
    protected SmsConfigService $smsService;

    /**
     * @Inject
     * @var StorageConfigService
     */
    protected StorageConfigService $storageService;

    /**
     * @Inject
     * @var MiniappConfigService
     */
    protected MiniappConfigService $miniappService;

    /**
     * @Inject
     * @var PaymentConfigService
     */
    protected PaymentConfigService $paymentService;

    /**
     * @Description("获取平台配置总览")
     * ZGW接口名: system.platform.getList
     */
    public function getList(AuthedRequest $request)
    {
        // 查询各配置类型的数量
        $endpointCount = $this->getEndpointCount();
        $smsCount = $this->getSmsCount();
        $storageCount = $this->getStorageCount();
        $miniappCount = $this->getMiniappCount();
        $paymentCount = $this->getPaymentCount();

        $result = [
            'endpoint' => [
                'count' => $endpointCount,
                'default' => null
            ],
            'sms' => [
                'count' => $smsCount,
                'default' => $this->getDefaultSms()
            ],
            'storage' => [
                'count' => $storageCount,
                'default' => $this->getDefaultStorage()
            ],
            'miniapp' => [
                'count' => $miniappCount,
                'default' => $this->getDefaultMiniapp()
            ],
            'payment' => [
                'count' => $paymentCount,
                'default' => $this->getDefaultPayment()
            ]
        ];

        return $this->success($result);
    }

    /**
     * @Description("获取特定类型配置详情")
     * ZGW接口名: system.platform.getDetail
     */
    public function getDetail(AuthedRequest $request)
    {
        $this->validate([
            'type' => 'required|string|in:endpoint,sms,storage,miniapp,payment'
        ]);
        
        $type = $request->param('type');
        $result = null;
        
        switch ($type) {
            case 'endpoint':
                $result = $this->getEndpointInfo();
                break;
            case 'sms':
                $result = $this->getSmsInfo();
                break;
            case 'storage':
                $result = $this->getStorageInfo();
                break;
            case 'miniapp':
                $result = $this->getMiniappInfo();
                break;
            case 'payment':
                $result = $this->getPaymentInfo();
                break;
        }
        
        return $this->success($result);
    }

    /**
     * 获取端点配置数量
     */
    private function getEndpointCount(): int
    {
        $params = [
            'page' => 1,
            'pageSize' => 1
        ];
        $result = $this->endpointService->getList($params);
        return $result['total'] ?? 0;
    }

    /**
     * 获取端点配置信息
     */
    private function getEndpointInfo(): array
    {
        $params = [
            'page' => 1,
            'pageSize' => 10
        ];
        return $this->endpointService->getList($params);
    }

    /**
     * 获取短信配置数量
     */
    private function getSmsCount(): int
    {
        $params = [
            'page' => 1,
            'pageSize' => 1
        ];
        $result = $this->smsService->getList($params);
        return $result['total'] ?? 0;
    }

    /**
     * 获取默认短信配置
     */
    private function getDefaultSms(): ?array
    {
        return $this->smsService->getFirstConfig();
    }

    /**
     * 获取短信配置信息
     */
    private function getSmsInfo(): array
    {
        $params = [
            'page' => 1,
            'pageSize' => 10
        ];
        return $this->smsService->getList($params);
    }

    /**
     * 获取存储配置数量
     */
    private function getStorageCount(): int
    {
        $params = [
            'page' => 1,
            'pageSize' => 1
        ];
        $result = $this->storageService->getList($params);
        return $result['total'] ?? 0;
    }

    /**
     * 获取默认存储配置
     */
    private function getDefaultStorage(): ?array
    {
        return $this->storageService->getFirstConfig();
    }

    /**
     * 获取存储配置信息
     */
    private function getStorageInfo(): array
    {
        $params = [
            'page' => 1,
            'pageSize' => 10
        ];
        return $this->storageService->getList($params);
    }

    /**
     * 获取小程序配置数量
     */
    private function getMiniappCount(): int
    {
        $params = [
            'page' => 1,
            'pageSize' => 1
        ];
        $result = $this->miniappService->getList($params);
        return $result['total'] ?? 0;
    }

    /**
     * 获取默认小程序配置
     */
    private function getDefaultMiniapp(): ?array
    {
        return $this->miniappService->getFirstConfig();
    }

    /**
     * 获取小程序配置信息
     */
    private function getMiniappInfo(): array
    {
        $params = [
            'page' => 1,
            'pageSize' => 10
        ];
        return $this->miniappService->getList($params);
    }

    /**
     * 获取支付配置数量
     */
    private function getPaymentCount(): int
    {
        $params = [
            'page' => 1,
            'pageSize' => 1
        ];
        $result = $this->paymentService->getList($params);
        return $result['total'] ?? 0;
    }

    /**
     * 获取默认支付配置
     */
    private function getDefaultPayment(): ?array
    {
        return $this->paymentService->getFirstConfig();
    }

    /**
     * 获取支付配置信息
     */
    private function getPaymentInfo(): array
    {
        $params = [
            'page' => 1,
            'pageSize' => 10
        ];
        return $this->paymentService->getList($params);
    }
} 