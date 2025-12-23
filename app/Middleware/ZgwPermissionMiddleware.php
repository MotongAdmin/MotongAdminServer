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

namespace App\Middleware;

use App\Service\Admin\System\PermissionService;
use App\Constants\ErrorCode;
use App\Constants\PermissionConstants;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZYProSoft\Facade\Auth;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProsoft\Constants\ErrorCode as ZYProsoftErrorCode;
use ZYProSoft\Log\Log;
use ZYProsoft\Constants\Constants;

/**
 * ZGW协议权限验证中间件
 */
class ZgwPermissionMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;
    protected PermissionService $permissionService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->permissionService = $container->get(PermissionService::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //如果是回调请求，那么直接返回
        $callback = $request->getHeaderLine(Constants::ZYPROSOFT_CALLBACK);
        if (isset($callback) && $callback == '1') {
            return $handler->handle($request);
        }

        $contentType = $request->getHeaderLine('content-type');
        $requestBody = json_decode($request->getBody()->getContents(), true);

        if (!$requestBody) {
            //普通post请求
            Log::info("post method but decode body fail!");
            return $handler->handle($request);
        }

        //不是json请求，那么肯定不是zgw协议
        if (empty($contentType) || strtolower($contentType) !== "application/json") {
            return $handler->handle($request);
        }

        $interfaceName = null;
        $param = null;
        if (isset($requestBody["interface"])) {
            if (isset($requestBody["interface"]["name"]) && isset($requestBody["interface"]["param"])) {
                $interfaceName = $requestBody["interface"]["name"];
                $param = $requestBody["interface"]["param"];
            }
        }
        if (!isset($interfaceName) || !isset($param)) {
            //普通post请求
            Log::info("post method but not zgw protocol request!");
            return $handler->handle($request);
        }
        //zgw协议
        $interfaceArray = explode('.', $interfaceName);
        if (count($interfaceArray) != 3) {
            throw new HyperfCommonException(ZYProsoftErrorCode::PARAM_ERROR, "zgw interfaceName is not validate");
        }

        if (!isset($requestBody['interface']['name'])) {
            return $handler->handle($request);
        }

        // 如果不是admin或者system模块的，不做权限检查
        if ($interfaceArray[0] != 'admin' && $interfaceArray[0] != 'system') {
            return $handler->handle($request);
        }

        // 如果是字典类型和字典数据接口，不做权限检查
        $module = $interfaceArray[1];

        if ($module == 'dictType' || $module == 'dictData') {
            return $handler->handle($request);
        }

        $interfaceName = $requestBody['interface']['name'];

        // 白名单接口
        if (in_array($interfaceName, PermissionConstants::ZGW_WHITE_LIST_INTERFACES)) {
            return $handler->handle($request);
        }

        $user = Auth::user();
        if (!$user) {
            throw new HyperfCommonException(ErrorCode::AUTH_ERROR, '用户未登录');
        }

        // 检查接口权限
        $resource = $interfaceName;
        
        // 使用新的权限服务检查权限
        $hasPermission = $this->permissionService->checkPermission($user->getId(), $resource);
        if (!$hasPermission) {
            throw new HyperfCommonException(ErrorCode::PERMISSION_DENY, '无权限访问');
        }

        return $handler->handle($request);
    }
} 