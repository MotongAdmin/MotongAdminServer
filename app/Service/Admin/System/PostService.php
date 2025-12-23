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

namespace App\Service\Admin\System;

use App\Service\Admin\BaseService;
use App\Model\SysPost;
use App\Model\User;
use App\Constants\ErrorCode;
use ZYProSoft\Exception\HyperfCommonException;

class PostService extends BaseService
{
    /**
     * 获取职位列表（分页）
     * @param array $params 查询参数
     * @return array 职位列表数据
     */
    final public function getPostList(array $params = [])
    {
        $page = data_get($params, 'page', 1);
        $size = data_get($params, 'size', 20);
        $keyword = data_get($params, 'keyword', '');
        $status = data_get($params, 'status');
        
        $query = SysPost::query();
        
        // 关键字搜索（职位名称和编码）
        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('post_name', 'like', "%{$keyword}%")
                  ->orWhere('post_code', 'like', "%{$keyword}%");
            });
        }
        
        // 状态筛选
        if (isset($status)) {
            $query->where('status', $status);
        }
        
        $total = $query->count();
        $list = $query->ordered()
                      ->offset(($page - 1) * $size)
                      ->limit($size)
                      ->get()
                      ->toArray();
                      
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'pages' => ceil($total / $size)
        ];
    }

    /**
     * 获取所有职位选择器数据
     * @return array 职位选择器数据
     */
    final public function getAllPosts()
    {
        $posts = SysPost::normal()->ordered()->get(['post_id', 'post_name', 'post_code'])->toArray();
        
        return array_map(function($post) {
            return [
                'value' => $post['post_id'],
                'label' => $post['post_name'],
                'code' => $post['post_code']
            ];
        }, $posts);
    }

    /**
     * 创建职位
     * @param array $params 职位参数
     * @return SysPost 创建的职位
     */
    final public function createPost(array $params)
    {
        $postCode = data_get($params, 'post_code');
        
        // 检查职位编码唯一性
        $existPost = SysPost::where('post_code', $postCode)->first();
        if ($existPost) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "职位编码已存在!");
        }

        $post = new SysPost();
        $post->post_name = data_get($params, 'post_name');
        $post->post_code = $postCode;
        $post->sort = data_get($params, 'sort', 0);
        $post->status = data_get($params, 'status', 1);
        $post->remark = data_get($params, 'remark', '');
        
        $post->saveOrFail();
        
        //记录操作日志
        $this->addOperationLog();
        
        return $post->refresh();
    }

    /**
     * 更新职位
     * @param array $params 职位参数
     * @return SysPost 更新的职位
     */
    final public function updatePost(array $params)
    {
        $postId = data_get($params, 'post_id');
        $post = SysPost::find($postId);
        if (!$post instanceof SysPost) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "职位不存在!");
        }

        $postCode = data_get($params, 'post_code');
        
        // 检查职位编码唯一性（排除自己）
        $existPost = SysPost::where('post_code', $postCode)
                             ->where('post_id', '!=', $postId)
                             ->first();
        if ($existPost) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "职位编码已存在!");
        }

        $post->post_name = data_get($params, 'post_name');
        $post->post_code = $postCode;
        $post->sort = data_get($params, 'sort', 0);
        $post->status = data_get($params, 'status', 1);
        $post->remark = data_get($params, 'remark', '');
        
        $post->saveOrFail();
        
        //记录操作日志
        $this->addOperationLog();
        
        return $post->refresh();
    }

    /**
     * 删除职位
     * @param int $postId 职位ID
     * @return bool 删除结果
     */
    final public function deletePost(int $postId)
    {
        $post = SysPost::find($postId);
        if (!$post instanceof SysPost) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "职位不存在!");
        }

        // 检查是否有关联用户
        $userCount = User::where('post_id', $postId)->count();
        if ($userCount > 0) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, "职位下存在用户，无法删除!");
        }

        $post->delete();
        
        //记录操作日志
        $this->addOperationLog();
        
        return true;
    }

    /**
     * 切换职位状态
     * @param int $postId 职位ID
     * @return SysPost 更新的职位
     */
    final public function toggleStatus(int $postId)
    {
        $post = SysPost::find($postId);
        if (!$post instanceof SysPost) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "职位不存在!");
        }

        $post->status = $post->status == 1 ? 0 : 1;
        $post->saveOrFail();
        
        //记录操作日志
        $this->addOperationLog();
        
        return $post;
    }

    /**
     * 获取职位详情
     * @param int $postId 职位ID
     * @return SysPost 职位信息
     */
    final public function getPostDetail(int $postId)
    {
        $post = SysPost::find($postId);
        if (!$post instanceof SysPost) {
            throw new HyperfCommonException(ErrorCode::RECORD_NOT_EXIST, "职位不存在!");
        }

        return $post;
    }
}
