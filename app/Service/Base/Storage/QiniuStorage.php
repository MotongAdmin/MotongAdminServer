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
use Qiniu\Auth as QiniuAuth;
use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;
use Qiniu\Storage\BucketManager;
use Hyperf\Filesystem\FilesystemFactory;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Constants\ErrorCode;
use App\Model\SysStorageConfig;
use Carbon\Carbon;

class QiniuStorage implements StorageInterface
{
    protected int $ttl = 3600;

    protected QiniuAuth $qiniuAuth;

    protected string $bucket;

    protected string $mainDirectory;

    protected string $domain;

    protected Filesystem $ossFileSystem;

    protected BucketManager $bucketManager;

    protected FileSystemFactory $fileSystemFactory;

    public function __construct(ContainerInterface $container, SysStorageConfig $sysStorageConfig)
    {
        $this->fileSystemFactory = $container->get(FileSystemFactory::class);
        $accessKey = $sysStorageConfig->access_key;
        $secretKey = $sysStorageConfig->secret_key;
        $this->domain = $sysStorageConfig->domain;
        $this->bucket = $sysStorageConfig->bucket;
        $this->mainDirectory = $sysStorageConfig->main_directory;
        $this->qiniuAuth = new QiniuAuth($accessKey, $secretKey);
        $extraConfig = $sysStorageConfig->extra_config;
        $this->ttl = $extraConfig['timeout'] ?? 3600;
        $this->ossFileSystem = $this->fileSystemFactory->get('qiniu');
        $this->bucketManager = new BucketManager($this->qiniuAuth);
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
        if (!isset($scale)) {
            return $this->getImageAccessUrl($objectId);
        }

        if (collect([self::IMAGE_STYLE_SMALL, self::IMAGE_STYLE_MIDDLE])->contains($scale) === false) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,"scale参数只能为1或者2");
        }

        $style = "-";
        if ($scale === self::IMAGE_STYLE_SMALL) {
            $style .= "small";
        } else if ($scale === self::IMAGE_STYLE_MIDDLE) {
            $style .= "middle";
        }

        $url = $this->domain."/image/{$objectId}".$style;

        return $this->qiniuAuth->privateDownloadUrl($url);
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
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }
        $token = $this->qiniuAuth->uploadToken($this->bucket,$object,$this->ttl);

        return [
            "id" => $filename,
            "key" => $object,
            "token" => $token
        ];
    }

    protected function commonGetFileAccessUrl(string $objectId, string $type) {
        $object = "{$type}/{$objectId}";
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }
        $url = $this->domain."/{$object}";
        return $this->qiniuAuth->privateDownloadUrl($url);
    }

    protected function commonCheckFileUploadSuccess(string $objectId, string $type) {
        $object = "{$type}/{$objectId}";
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }
        return $this->ossFileSystem->fileExists($object);
    }

    public function commonCheckUploadFailThenThrowException(string $objectId, string $type, string $message) {
        $isExist = $this->commonCheckFileUploadSuccess($objectId, $type);
        if (!$isExist) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,$message);
        }
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
        $object = "{$type}/{$filename}";
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }

        $this->ossFileSystem->writeStream($object, fopen($url, 'r'));
        return $filename;
    }
}
