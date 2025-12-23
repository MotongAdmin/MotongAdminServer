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


namespace App\Service\Admin;
use ZYProSoft\Service\AbstractService;
use App\Job\OperateLogJob;
use ZYProSoft\Http\Request;
use App\Constants\LogConstants;
use Hyperf\Utils\Str;
use ZYProSoft\Facade\Auth;
use ZYProSoft\Log\Log;
use App\Model\User;
use App\Service\Base\DataScopeService;
use Hyperf\Di\Annotation\Inject;


class BaseService extends AbstractService
{

    /**
     * @Inject
     * @var DataScopeService
     */
    protected DataScopeService $dataScopeService;

    public function addOperationLog()
    {
        $request = $this->container->get(Request::class);

        if ($request->isZgw() == false) {
            Log::info('非ZGW请求，不记录操作日志');
            return;
        }

        $requestBody = json_decode($request->getBody()->getContents(), true);
        $interfaceName = $requestBody['interface']['name'] ?? '';

        //如果请求不是属于Admin模块的话，也可以不用记录
        $interfaceArr = explode('.', $interfaceName);
        $bigModule = $interfaceArr[0];
        if ($bigModule != 'admin' && $bigModule != 'system') {
            Log::info('非Admin或System模块请求，不记录操作日志');
            return;
        }

        //确定模块和操作
        $module = $interfaceArr[1];
        $execMethod = $interfaceArr[2];

        //初始化用户信息
        $userId = null;
        $username = null;

        //如果没有登录则不记录
        if (Auth::isLogin() == false) {

            if ($module !== 'user' || $execMethod !== 'login') {
                Log::info('未登录并且不是登录操作，不记录操作日志');
                return;
            }

            if($execMethod == 'login') {
                //根据参数查找登录用户
                $username = $request->param('username');
                $user = User::where('username', $username)->first();
                if ($user instanceof User) {
                    $userId = $user->user_id;
                    $username = $user->username;
                } else {
                    Log::info('未找到登录用户，不记录操作日志');
                    return;
                }
            }
        }

        // 判断最终的接口名，不是以get开头的
        if (Str::startsWith($execMethod, 'get')) {
            Log::info('以get开头的接口，不记录操作日志');
            return;
        }

        // 确定操作类型
        $operation = $this->determineOperationType($execMethod);

        // 确定日志级别
        $level = $this->determineLogLevel($operation);

        // 获取请求参数
        $requestParam = $request->getParams();

        // 获取用户信息
        if ($userId === null) {
            $userId = $this->userId();
            $username = $this->user()->username ?? 'unknown';
        }

        // 获取请求头信息
        $ip = $request->getHeaderLine('x-real-ip') ?: $request->getServerParams()['remote_addr'];

        // 创建操作日志任务
        $job = new OperateLogJob(
            $module,  // 模块
            $operation, // 操作
            $userId,
            $username,
            $requestParam,
            $level,
            $ip
        );

        Log::info('操作日志记录' . json_encode([
            'module' => $module,
            'operation' => $operation,
            'userId' => $userId,
            'username' => $username,
            'requestParam' => $requestParam,
        ]));

        // 推送到队列
        $this->driver->push($job);
    }

    /**
     * 根据请求方法和路径确定操作类型
     *
     * @param string $method 请求方法
     * @param string $path 请求路径
     * @return string
     */
    private function determineOperationType(string $operation): string
    {
        // 登录相关
        if (strpos($operation, 'login') !== false) {
            return LogConstants::OPERATION_TYPE_LOGIN;
        }

        if (strpos($operation, 'logout') !== false) {
            return LogConstants::OPERATION_TYPE_LOGOUT;
        }

        if (strpos($operation, 'create') !== false) {
            return LogConstants::OPERATION_TYPE_INSERT;
        }

        if (strpos($operation, 'update') !== false) {
            return LogConstants::OPERATION_TYPE_UPDATE;
        }

        if (strpos($operation, 'delete') !== false) {
            return LogConstants::OPERATION_TYPE_DELETE;
        }

        if (strpos($operation, 'bind') !== false) {
            return LogConstants::OPERATION_TYPE_UPDATE;
        }

        return LogConstants::OPERATION_TYPE_OTHER;
    }

    /**
     * 根据操作类型确定日志级别
     *
     * @param string $operation 操作类型
     * @return int
     */
    private function determineLogLevel(string $operation): int
    {
        // 关键操作
        $criticalOperations = [
            LogConstants::OPERATION_TYPE_LOGIN,
            LogConstants::OPERATION_TYPE_LOGOUT,
            LogConstants::OPERATION_TYPE_FORCE_LOGOUT,
        ];

        // 重要操作
        $importantOperations = [
            LogConstants::OPERATION_TYPE_INSERT,
            LogConstants::OPERATION_TYPE_DELETE,
            LogConstants::OPERATION_TYPE_UPDATE,
            LogConstants::OPERATION_TYPE_GRANT,
        ];

        if (in_array($operation, $criticalOperations)) {
            return LogConstants::LOG_LEVEL_CRITICAL;
        }

        if (in_array($operation, $importantOperations)) {
            return LogConstants::LOG_LEVEL_IMPORTANT;
        }

        return LogConstants::LOG_LEVEL_NORMAL;
    }
}