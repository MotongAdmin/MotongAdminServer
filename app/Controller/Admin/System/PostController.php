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

use App\Annotation\Description;
use App\Service\Admin\System\PostService;
use ZYProSoft\Controller\AbstractController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use ZYProSoft\Http\AuthedRequest;

/**
 * @AutoController(prefix="/system/post")
 */
class PostController extends AbstractController
{
    /**
     * @Inject
     * @var PostService
     */
    protected PostService $service;

    /**
     * 自定义验证错误消息
     * @return array
     */
    public function messages()
    {
        return [
            'required' => ':attribute不能为空',
            'string' => ':attribute必须是字符串',
            'integer' => ':attribute必须是整数',
            'max' => ':attribute长度不能超过:max位',
            'min' => ':attribute不能小于:min',
            'in' => ':attribute的值不在允许范围内',
            'exists' => ':attribute不存在',

            'post_id.required' => '职位ID不能为空',
            'post_id.integer' => '职位ID必须是整数',
            'post_id.min' => '职位ID不能小于1',
            'post_id.exists' => '职位ID不存在',
            
            'post_name.required' => '职位名称不能为空',
            'post_name.max' => '职位名称长度不能超过128位',
            'post_code.required' => '职位编码不能为空',
            'post_code.max' => '职位编码长度不能超过128位',
            'post_code.unique' => '职位编码已存在',
            'sort.integer' => '显示顺序必须是整数',
            'status.in' => '状态值只能是0或1',
            'remark.max' => '备注长度不能超过255位',
        ];
    }

    /**
     * @Description(value="获取职位列表")
     */
    final public function getPostList(AuthedRequest $request)
    {
        // ZGW协议：system.post.getPostList
        $this->validate([
            'page' => 'integer|min:1',
            'size' => 'integer|min:1|max:100',
            'keyword' => 'nullable|string|max:50',
            'status' => 'nullable|in:0,1'
        ]);

        $params = $request->getParams();
        $result = $this->service->getPostList($params);

        return $this->success($result);
    }

    /**
     * @Description(value="获取所有职位选择器数据")
     */
    final public function getAllPosts(AuthedRequest $request)
    {
        // ZGW协议：system.post.getAllPosts
        $result = $this->service->getAllPosts();

        return $this->success($result);
    }

    /**
     * @Description(value="创建职位")
     */
    final public function createPost(AuthedRequest $request)
    {
        // ZGW协议：system.post.createPost
        $this->validate([
            'post_name' => 'required|string|max:128',
            'post_code' => 'required|string|max:128',
            'sort' => 'integer|min:0',
            'status' => 'in:0,1',
            'remark' => 'string|max:255'
        ]);

        $params = $request->getParams();
        $result = $this->service->createPost($params);

        return $this->success($result);
    }

    /**
     * @Description(value="更新职位")
     */
    final public function updatePost(AuthedRequest $request)
    {
        // ZGW协议：system.post.updatePost
        $postId = $request->param('post_id');
        $this->validate([
            'post_id' => 'required|integer|min:1|exists:sys_post,post_id',
            'post_name' => 'required|string|max:128',
            'post_code' => 'required|string|max:128|unique:sys_post,post_code,' . $postId . ',post_id',
            'sort' => 'integer|min:0',
            'status' => 'in:0,1',
            'remark' => 'string|max:255'
        ]);

        $params = $request->getParams();
        $result = $this->service->updatePost($params);

        return $this->success($result);
    }

    /**
     * @Description(value="删除职位")
     */
    final public function deletePost(AuthedRequest $request)
    {
        // ZGW协议：system.post.deletePost
        $this->validate([
            'post_id' => 'required|integer|min:1|exists:sys_post,post_id'
        ]);

        $postId = $request->param('post_id');
        $this->service->deletePost($postId);

        return $this->success();
    }

    /**
     * @Description(value="切换职位状态")
     */
    final public function toggleStatus(AuthedRequest $request)
    {
        // ZGW协议：system.post.toggleStatus
        $this->validate([
            'post_id' => 'required|integer|min:1|exists:sys_post,post_id'
        ]);

        $postId = $request->param('post_id');
        $result = $this->service->toggleStatus($postId);

        return $this->success($result);
    }
}
