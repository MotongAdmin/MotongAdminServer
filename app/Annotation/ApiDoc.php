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

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * API 文档注解
 * 用于标注接口方法的文档信息
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class ApiDoc extends AbstractAnnotation
{
    /**
     * 接口摘要/简短描述
     * @var string
     */
    public string $summary = '';

    /**
     * 接口详细描述
     * @var string
     */
    public string $description = '';

    /**
     * 接口标签/分组
     * @var array
     */
    public array $tags = [];

    /**
     * 是否需要认证
     * @var bool
     */
    public bool $auth = false;

    /**
     * 是否废弃
     * @var bool
     */
    public bool $deprecated = false;

    /**
     * 接口版本
     * @var string
     */
    public string $version = '1.0';

    public function __construct($value = null)
    {
        parent::__construct($value);

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $val;
                }
            }
        }
    }
}
