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
 * API 分组注解
 * 用于标注控制器类的分组信息
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiGroup extends AbstractAnnotation
{
    /**
     * 分组名称
     * @var string
     */
    public string $name = '';

    /**
     * 分组描述
     * @var string
     */
    public string $description = '';

    /**
     * 分组排序 (数值越小越靠前)
     * @var int
     */
    public int $order = 0;

    public function __construct($value = null)
    {
        parent::__construct($value);

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $val;
                }
            }
        } elseif (is_string($value)) {
            $this->name = $value;
        }
    }
}
