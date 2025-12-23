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

namespace App\Service\Base;

use ZYProSoft\Service\AbstractService;
use App\Constants\ConfigConstants;
use App\Facade\ConfigUtil;
use App\Service\Base\Storage\StorageInterface;
use App\Service\Base\Storage\LocalStorage;
use App\Service\Base\Storage\OssStorage;
use App\Service\Base\Storage\QiniuStorage;
use App\Constants\Constants;
use Hyperf\HttpMessage\Upload\UploadedFile;
use ZYProSoft\Log\Log;
use Hyperf\Utils\ApplicationContext;

class StorageService extends AbstractService
{
    /**
     * 获取当前存储
     * @throws \Exception
     * @return LocalStorage|OssStorage|QiniuStorage
     */
    protected function getStorage(): StorageInterface
    {
        $container = ApplicationContext::getContainer();

        $storageProvider = ConfigUtil::get(ConfigConstants::STORAGE_KEY);
        
        //本地文件存储系统是不需要获取存储配置的
        if($storageProvider != Constants::STORAGE_PROVIDER_LOCAL) {
            $storageConfig = ConfigUtil::getStorageConfig($storageProvider);
        }

        switch ($storageProvider) {
            case Constants::STORAGE_PROVIDER_LOCAL:
                $storage = new LocalStorage($container);
                break;
            case Constants::STORAGE_PROVIDER_ALIYUN:
                $storage = new OssStorage($container, $storageConfig);
                break;
            case Constants::STORAGE_PROVIDER_QINIU:
                $storage = new QiniuStorage($container, $storageConfig);
                break;
            default:
                throw new \Exception('Invalid storage provider');
        }

        return $storage;
    }
    
    /**
     * 魔术方法，代理所有对StorageInterface接口的调用
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $storage = $this->getStorage();
        if (method_exists($storage, $name)) {
            return $storage->$name(...$arguments);
        }
        throw new \BadMethodCallException(sprintf('方法 %s 在存储接口中不存在', $name));
    }

    /**
     * 上传文件到本地目录
     * @param \Hyperf\HttpMessage\Upload\UploadedFile $file
     * @param string $id
     * @param string $type
     * @throws \Exception
     * @return void
     */
    public function uploadToLocal(UploadedFile $file,string $id, string $type) 
    {
        $localStorage = $this->getStorage();
        if($localStorage instanceof LocalStorage) {
            $localStorage->moveUploadFileToDestDirectory($file, $id, $type);
        } else {
            throw new \Exception('Invalid storage provider');
        }
    }

    /**
     * 根据存储对象Id获取对应的访问链接
     * @param array $objectIds
     * @return array|\Hyperf\Utils\Collection<mixed, string[]>
     */
    public function getImageUrlByObjectIds(array $objectIds, int $style = StorageInterface::IMAGE_STYLE_MIDDLE)
    {
        if(empty($objectIds)) { 
            return [];
        }

        $storage = $this->getStorage();
        return collect($objectIds)->map(function(string $objectId) use ($storage, $style) {
            return [$objectId => $storage->getImageAccessUrlWithScale($objectId, $style)];
        });
    }

    /**
     * 根据Id获取图片地址
     * @param string $objectId
     * @param int $style
     * @return string
     */
    public function getImageUrlByObjectId(string $objectId, int $style = StorageInterface::IMAGE_STYLE_MIDDLE) 
    {
        if(empty($objectId)) {
            return '';
        }
        
        $storage = $this->getStorage();
        return $storage->getImageAccessUrlWithScale($objectId, $style);
    }
}