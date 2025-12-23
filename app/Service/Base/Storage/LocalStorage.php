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

namespace App\Service\Base\Storage;

use Psr\Container\ContainerInterface;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Constants\ErrorCode;
use Carbon\Carbon;
use ZYProSoft\Service\PublicFileService;
use Hyperf\HttpMessage\Upload\UploadedFile;

class LocalStorage implements StorageInterface
{
    protected string $domain;

    protected PublicFileService $publicFileService;

    public function __construct(ContainerInterface $container)
    {
        $this->publicFileService = $container->get(PublicFileService::class);
        $this->domain = config('hyperf-common.upload.local.url_prefix');
    }

    public function checkImageUploadFailThrowException(string $objectId, string $message)
    {
        $this->commonCheckUploadFailThenThrowException($objectId, 'image', $message);
    }

    public function checkAudioUploadFailThrowException(string $objectId, string $message)
    {
        $this->commonCheckUploadFailThenThrowException($objectId, 'audio', $message);
    }

    public function checkVideoUploadFailThrowException(string $objectId, string $message)
    {
        $this->commonCheckUploadFailThenThrowException($objectId, 'video', $message);
    }

    public function checkFileUploadFailThrowException(string $objectId, string $message)
    {
        $this->commonCheckUploadFailThenThrowException($objectId, 'common', $message);
    }

    public function checkImageUploadSuccess(string $objectId)
    {
        return $this->commonCheckFileUploadSuccess($objectId, 'image');
    }

    public function checkFileUploadSuccess(string $objectId)
    {
        return $this->commonCheckFileUploadSuccess($objectId, 'common');
    }

    public function checkAudioUploadSuccess(string $objectId)
    {
        return $this->commonCheckFileUploadSuccess($objectId, 'audio');
    }

    public function checkVideoUploadSuccess(string $objectId)
    {
        return $this->commonCheckFileUploadSuccess($objectId, 'video');
    }

    public function getImageAccessUrl(string $objectId)
    {
        return $this->commonGetFileAccessUrl($objectId, 'image');
    }

    public function getFileAccessUrl(string $objectId)
    {
        return $this->commonGetFileAccessUrl($objectId, 'common');
    }

    public function getAudioAccessUrl(string $objectId)
    {
        return $this->commonGetFileAccessUrl($objectId, 'audio');
    }

    public function getVideoAccessUrl(string $objectId)
    {
        return $this->commonGetFileAccessUrl($objectId, 'video');
    }

    public function getImageAccessUrlWithScale(string $objectId, int $scale = null)
    {
        return $this->getImageAccessUrl($objectId);
    }

    public function getImageUploadToken()
    {
        return $this->commonGetUploadToken('image', 'jpg');
    }

    public function getFileUploadToken()
    {
        return $this->commonGetUploadToken('common');
    }

    public function getAudioUploadToken()
    {
        return $this->commonGetUploadToken('audio', 'mp3');
    }

    public function getVideoUploadToken()
    {
        return $this->commonGetUploadToken('video', 'mp4');
    }

    protected function commonGetUploadToken(string $type, string $defaultExtention = '') {
        $filename = Carbon::now()->getTimestampMs();
        if(!empty($defaultExtention)) {
            $filename = "{$filename}.{$defaultExtention}";
        }
        $object = "{$type}/{$filename}";
        
        return [
            "id" => $filename,
            "key" => $object,
            "url" => rtrim($this->domain, '/') . '/' . $object
        ];
    }

    protected function commonGetFileAccessUrl(string $objectId, string $type) {
        $object = "{$type}/{$objectId}";
        return rtrim($this->domain, '/') . '/' . $object;
    }

    protected function commonCheckFileUploadSuccess(string $objectId, string $type) {
        $object = "{$type}/{$objectId}";
        $publicPath = $this->publicFileService->publicPath($object);
        return file_exists($publicPath);
    }

    protected function commonCheckUploadFailThenThrowException(string $objectId, string $type, string $message) {
        $isExist = $this->commonCheckFileUploadSuccess($objectId, $type);
        if (!$isExist) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,$message);
        }
    }

    public function moveUploadFileToDestDirectory(UploadedFile $file, string $id, string $type) 
    {
        $objectPath = "{$type}/{$id}";
        $publicPath = $this->publicFileService->publicPath("/{$objectPath}");
        //获取目录路径
        $dirPath = dirname($publicPath);
        //如果目录不存在则创建
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        $file->moveTo($publicPath);
    }

    public function fetchFileFromUrl(string $url, string $type): string
    {
        $directoryMap = [
            'image' => 'image',
            'audio' => 'audio',
            'video' => 'video',
            'common' => 'common'
        ];
        $directory = $directoryMap[$type];
        $filename = Carbon::now()->getTimestampMs();
        if($type === 'audio') {
            $filename = "{$filename}.mp3";
        }
        $publicPath = $this->publicFileService->publicPath("/{$directory}/{$filename}");
        $data = file_get_contents($url);
        file_put_contents($publicPath, $data);
        return $filename;
    }
}
