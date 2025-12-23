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
use App\Service\Admin\System\MiniappConfigService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use App\Annotation\Description;

/**
 * 小程序配置管理控制器
 * @AutoController(prefix="/system/miniappConfig")
 */
class MiniappConfigController extends AbstractController
{
    /**
     * @Inject
     * @var MiniappConfigService
     */
    protected MiniappConfigService $service;

    /**
     * @Description("获取小程序配置列表")
     * ZGW接口名: system.miniappConfig.getList
     */
    public function getList(AuthedRequest $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 20);
        $name = $request->param('name', '');
        $platform = $request->param('platform', '');

        $params = [
            'page' => $page,
            'size' => $size,
            'name' => $name,
            'platform' => $platform
        ];

        $result = $this->service->getList($params);
        return $this->success($result);
    }

    /**
     * @Description("获取小程序配置详情")
     * ZGW接口名: system.miniappConfig.getDetail
     */
    public function getDetail(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer'
        ]);
        
        $id = $request->param('id');
        $result = $this->service->getDetail($id);
        return $this->success($result);
    }

    /**
     * @Description("创建小程序配置")
     * ZGW接口名: system.miniappConfig.create
     */
    public function create(AuthedRequest $request)
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'platform' => 'required|string|max:20',
            'app_id' => 'required|string|max:100',
            'app_secret' => 'required|string|max:100',
            'auth_redirect' => 'nullable|string|max:255',
            'message_token' => 'nullable|string|max:100',
            'message_aeskey' => 'nullable|string|max:100',
            'extra_config' => 'nullable|array'
        ]);
        
        $data = $request->getParams();
        $id = $this->service->create($data);
        return $this->success(['id' => $id]);
    }

    /**
     * @Description("更新小程序配置")
     * ZGW接口名: system.miniappConfig.update
     */
    public function update(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer',
            'name' => 'string|max:100',
            'platform' => 'string|max:20',
            'app_id' => 'string|max:100',
            'auth_redirect' => 'nullable|string|max:255',
            'message_token' => 'nullable|string|max:100',
            'message_aeskey' => 'nullable|string|max:100',
            'extra_config' => 'nullable|array'
        ]);
        
        $id = $request->param('id');
        $data = $request->getParams();
        unset($data['id']);
        
        $this->service->update($id, $data);
        return $this->success([]);
    }

    /**
     * @Description("删除小程序配置")
     * ZGW接口名: system.miniappConfig.delete
     */
    public function delete(AuthedRequest $request)
    {
        $this->validate([
            'id' => 'required|integer'
        ]);
        
        $id = $request->param('id');
        $this->service->delete($id);
        return $this->success([]);
    }
} 