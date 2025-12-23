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

namespace App\Controller\Admin\System;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use App\Service\Base\StorageService;
use ZYProSoft\Controller\AbstractController;
use ZYProSoft\Http\AuthedRequest;
use App\Annotation\Description;

/**
 * @AutoController(prefix="/system/storage")
 * Class StorageController
 * @package App\Controller\Admin\System
 */
class StorageController extends AbstractController
{
    /**
     * @Inject
     * @var StorageService
     */
    protected StorageService $storageService;

    /**
     * @Description(value="获取图片上传凭证")
     * 获取图片上传凭证
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getImageUploadToken(AuthedRequest $request)
    {
        $token = $this->storageService->getImageUploadToken();
        return $this->success($token);
    }

    /**
     * @Description(value="获取文件上传凭证")
     * 获取文件上传凭证
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getFileUploadToken(AuthedRequest $request)
    {
        $token = $this->storageService->getFileUploadToken();
        return $this->success($token);
    }

    /**
     * @Description(value="获取音频上传凭证")
     * 获取音频上传凭证
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getAudioUploadToken(AuthedRequest $request)
    {
        $token = $this->storageService->getAudioUploadToken();
        return $this->success($token);
    }

    /**
     * @Description(value="获取视频上传凭证")
     * 获取视频上传凭证
     * @param AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getVideoUploadToken(AuthedRequest $request)
    {
        $token = $this->storageService->getVideoUploadToken();
        return $this->success($token);
    }

    /**
     * @Description(value="上传文件到本地目录")
     * 上传文件到本地目录，实际的uri是/upload
     * @param \ZYProSoft\Http\AuthedRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function uploadToLocal(AuthedRequest $request)
    {
        $this->validate([
            'key' => 'required|string'
        ]);

        $key = $request->param('key');
        $file = $request->file('file');

        //从key中获取对应的真实类型目录
        $keyArray = explode('/', $key);
        $type = $keyArray[0];
        $id = $keyArray[1];

        $this->storageService->uploadToLocal($file, $id, $type);

        return $this->success();
    }
}
