<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  1003081775@qq.com
 * @author   zyvincent 
 * @Company  Icodefuture Information Technology Co., Ltd.
 * @license  GPL
 */

declare(strict_types=1);

namespace App\Controller\Common;

use App\Service\Base\SmsService;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Controller\AbstractController;
use App\Annotation\Description;
use App\Annotation\ApiDoc;
use App\Annotation\ApiGroup;
use App\Annotation\ApiParam;
use App\Annotation\ApiResponse;

/**
 * 短信服务控制器
 * @AutoController(prefix="/common/sms")
 * @ApiGroup(name="短信服务", description="短信验证码相关接口")
 */
class SmsController extends AbstractController
{
    /**
     * @Inject
     * @var SmsService
     */
    protected SmsService $smsService;

    /**
     * @Description("发送短信验证码")
     * @ApiDoc(
     *     summary="发送短信验证码",
     *     description="向指定手机号发送短信验证码，用于登录或注册验证",
     *     tags={"短信服务"},
     *     auth=false
     * )
     * @ApiParam(name="mobile", type="string", required=true, description="手机号码", example="13800138000", minLength=11, maxLength=11)
     * @ApiResponse(code=200, description="发送成功", example={"code": 0, "message": "success", "data": {}})
     * @ApiResponse(code=400, description="参数错误", example={"code": 10001, "message": "手机号格式不正确"})
     * ZGW接口名: common.sms.sendVerificationCode
     */
    public function sendVerificationCode()
    {
        // 验证参数
        $this->validate([
            'mobile' => 'required|min:11|max:11'
        ]);

        $mobile = $this->request->param('mobile');

        // 发送验证码
        $this->smsService->sendCode($mobile);

        return $this->success();
    }
}