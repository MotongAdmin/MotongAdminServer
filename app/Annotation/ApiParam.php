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
 * API 参数注解
 * 用于标注接口方法的参数信息
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class ApiParam extends AbstractAnnotation
{
    /**
     * 参数名称
     * @var string
     */
    public string $name = '';

    /**
     * 参数类型 (string|integer|number|boolean|array|object)
     * @var string
     */
    public string $type = 'string';

    /**
     * 是否必填
     * @var bool
     */
    public bool $required = false;

    /**
     * 参数描述
     * @var string
     */
    public string $description = '';

    /**
     * 示例值
     * @var mixed
     */
    public $example = null;

    /**
     * 默认值
     * @var mixed
     */
    public $default = null;

    /**
     * 枚举值列表
     * @var array
     */
    public array $enum = [];

    /**
     * 最小值 (用于 integer/number)
     * @var int|float|null
     */
    public $minimum = null;

    /**
     * 最大值 (用于 integer/number)
     * @var int|float|null
     */
    public $maximum = null;

    /**
     * 最小长度 (用于 string)
     * @var int|null
     */
    public ?int $minLength = null;

    /**
     * 最大长度 (用于 string)
     * @var int|null
     */
    public ?int $maxLength = null;

    /**
     * 数组元素类型 (当 type 为 array 时使用)
     * @var string
     */
    public string $items = 'string';

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
