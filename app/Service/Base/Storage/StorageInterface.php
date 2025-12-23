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

interface StorageInterface
{
    /**
     * 图片缩放至原图的40%
     */
    public const IMAGE_STYLE_SMALL = 1;

    /**
     * 图片缩放至原图的70%
     */
    public const IMAGE_STYLE_MIDDLE = 2;

    /**
     * 检查图片上传是否成功，失败则抛出异常
     * @param string $id 图片ID
     * @param string $message 错误信息
     * @throws \ZYProSoft\Exception\HyperfCommonException
     */
    public function checkImageUploadFailThrowException(string $objectId, string $message);
    /**
     * 检查文件上传是否成功，失败则抛出异常
     * @param string $id 文件ID
     * @param string $message 错误信息
     * @throws \ZYProSoft\Exception\HyperfCommonException
     */
    public function checkFileUploadFailThrowException(string $objectId, string $message);

    /**
     * 检查音频上传是否成功，失败则抛出异常
     * @param string $id 音频ID
     * @param string $message 错误信息
     * @throws \ZYProSoft\Exception\HyperfCommonException
     */
    public function checkAudioUploadFailThrowException(string $objectId, string $message);

    /**
     * 检查视频上传是否成功，失败则抛出异常
     * @param string $id 视频ID
     * @param string $message 错误信息
     * @throws \ZYProSoft\Exception\HyperfCommonException
     */
    public function checkVideoUploadFailThrowException(string $objectId, string $message);

    /**
     * 检查音频是否上传成功
     * @param string $id 音频ID
     * @return bool
     */
    public function checkAudioUploadSuccess(string $objectId);

    /**
     * 检查视频是否上传成功
     * @param string $id 视频ID
     * @return bool
     */
    public function checkVideoUploadSuccess(string $objectId);

    /**
     * 获取音频访问URL
     * @param string $id 音频ID
     * @return string
     */
    public function getAudioAccessUrl(string $objectId);

    /**
     * 获取视频访问URL
     * @param string $id 视频ID
     * @return string
     */
    public function getVideoAccessUrl(string $objectId);

    /**
     * 检查图片是否上传成功
     * @param string $id 图片ID
     * @return bool
     */
    public function checkImageUploadSuccess(string $objectId);

    /**
     * 检查文件是否上传成功
     * @param string $id 文件ID
     * @return bool
     */
    public function checkFileUploadSuccess(string $objectId);

    /**
     * 获取图片访问URL
     * @param string $id 图片ID
     * @return string
     */
    public function getImageAccessUrl(string $objectId);

    /**
     * 获取文件访问URL
     * @param string $id 文件ID
     * @return string
     */
    public function getFileAccessUrl(string $objectId);

    /**
     * 获取指定缩放比例的图片访问URL
     * @param string $id 图片ID
     * @param int|null $scale 缩放比例
     * @return string
     */
    public function getImageAccessUrlWithScale(string $objectId, int $scale = null);

    /**
     * 获取图片上传凭证
     * @return array
     */
    public function getImageUploadToken();

    /**
     * 获取文件上传凭证
     * @return array
     */
    public function getFileUploadToken();

    /**
     * 获取音频文件上传凭证
     * @return array
     */
    public function getAudioUploadToken();

    /**
     * 获取视频上传凭证
     * @return array
     */
    public function getVideoUploadToken();

    /**
     * 从URL获取文件进行转存
     * @param string $url
     * @return string|null
     */
    public function fetchFileFromUrl(string $url, string $type): ?string;
}
