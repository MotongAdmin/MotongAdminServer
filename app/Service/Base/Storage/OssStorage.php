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

use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;
use ZYProSoft\Exception\HyperfCommonException;
use ZYProSoft\Constants\ErrorCode;
use App\Model\SysStorageConfig;
use Carbon\Carbon;
use OSS\OssClient;
use Hyperf\Filesystem\FilesystemFactory;

class OssStorage implements StorageInterface
{
    protected string $bucket;

    protected string $mainDirectory;

    protected OssClient $client;

    protected int $timeout;

    protected Filesystem $ossFileSystem;

    protected FilesystemFactory $fileSystemFactory;

    public function __construct(ContainerInterface $container, SysStorageConfig $sysStorageConfig)
    {
        $this->bucket = $sysStorageConfig->bucket;
        $this->mainDirectory = $sysStorageConfig->main_directory;
        $accessId = $sysStorageConfig->access_key;
        $accessSecret = $sysStorageConfig->secret_key;
        $endpoint = $sysStorageConfig->extra_config['endpoint'];
        $this->timeout = $sysStorageConfig->extra_config['timeout'] ?? 3600;
        $connectTimeout = $sysStorageConfig->extra_config['connectTimeout'] ?? 3600;
        $isCName = $sysStorageConfig->extra_config['isCName'] ?? false;

        $this->client = new OssClient(
            $accessId,
            $accessSecret,
            $endpoint,
            $isCName
        );

        $this->client->setTimeout($this->timeout);
        $this->client->setConnectTimeout($connectTimeout);
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

        if (collect([StorageInterface::IMAGE_STYLE_SMALL, StorageInterface::IMAGE_STYLE_MIDDLE])->contains($scale) === false) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,"scale参数只能为1或者2");
        }

        $style = "image/resize,";
        if ($scale === StorageInterface::IMAGE_STYLE_SMALL) {
            $style .= "p_40";
        } else if ($scale === StorageInterface::IMAGE_STYLE_MIDDLE) {
            $style .= "p_70";
        }

        $options = [
            OssClient::OSS_PROCESS => $style
        ];

        $object = "image/{$objectId}";
        return $this->client->signUrl($this->bucket, $object, $this->timeout, OssClient::OSS_HTTP_GET, $options);
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
        $options = [
            OssClient::OSS_CONTENT_TYPE => 'application/octet-stream'
        ];
        $signUrl = $this->client->signUrl($this->bucket, $object, $this->timeout,OssClient::OSS_HTTP_PUT, $options);

        return [
            "id" => $filename,
            "key" => $object,
            "url" => $signUrl
        ];
    }

    protected function commonGetFileAccessUrl(string $objectId, string $type) {
        $object = "{$type}/{$objectId}";
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }
        return $this->client->signUrl($this->bucket, $object, $this->timeout);
    }

    protected function commonCheckFileUploadSuccess(string $objectId, string $type) {
        $object = "{$type}/{$objectId}";
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }
        return $this->client->doesObjectExist($this->bucket, $object);
    }

    protected function commonCheckUploadFailThenThrowException(string $objectId, string $type, string $message) {
        $isExist = $this->commonCheckFileUploadSuccess($objectId, $type);
        if (!$isExist) {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR,$message);
        }
    }

    public function fetchFileFromUrl(string $url, string $type): ?string
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
        $object = "{$directory}/{$filename}";
        if(!empty($this->mainDirectory)) {
            $object = "{$this->mainDirectory}/{$object}";
        }
        $result = $this->client->putObject($this->bucket, $object, file_get_contents($url));
        if($result === null) {
            return null;
        }
        return $filename;
    }
}
