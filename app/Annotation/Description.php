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
 * @Annotation
 * @Target({"METHOD"})
 */
class Description extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $value = '';

    public function __construct($value = null)
    {
        // 处理不同的传值方式
        if (is_array($value)) {
            if (isset($value['value'])) {
                $this->value = $value['value'];
            }
        } elseif (is_string($value)) {
            $this->value = $value;
        }
        
        // 调用父类构造函数并绑定value属性
        parent::__construct($value);
        $this->bindMainProperty('value', $value);
    }
} 