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
 * API 响应注解
 * 用于标注接口方法的响应信息
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class ApiResponse extends AbstractAnnotation
{
    /**
     * HTTP 状态码
     * @var int
     */
    public int $code = 200;

    /**
     * 响应描述
     * @var string
     */
    public string $description = '成功';

    /**
     * 响应数据结构 (JSON Schema 格式)
     * @var array
     */
    public array $schema = [];

    /**
     * 响应示例
     * @var array
     */
    public array $example = [];

    /**
     * 响应头
     * @var array
     */
    public array $headers = [];

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
