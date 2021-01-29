<?php
declare(strict_types=1);

namespace Minimal\Validate;

use RuntimeException;
use InvalidArgumentException;

/**
 * 参数类
 */
class Parameter
{
    /**
     * 参数名称
     */
    protected string $name;

    /**
     * 参数类型
     */
    protected ?string $type;

    /**
     * 参数备注
     */
    protected ?string $comment;

    /**
     * 数据字段
     */
    protected ?string $field;

    /**
     * 绑定条件
     */
    protected array $bindings = [];

    /**
     * 构造函数
     */
    public function __construct(string $name, ?string $type = null, ?string $comment = null, ?string $field = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->comment = $comment;
        $this->field = $field;
    }

    /**
     * 设置名称
     */
    public function setName(string $str) : static
    {
        return $this;
    }

    /**
     * 设置类型
     */
    public function setType(string $str) : static
    {
        return $this;
    }

    /**
     * 设置字段
     */
    public function setField(string $str) : static
    {
        return $this;
    }

    /**
     * 设置备注
     */
    public function setComment(string $str) : static
    {
        return $this;
    }

    /**
     * 设置：默认值
     */
    public function default(mixed $value) : static
    {
        $this->bindings['default'] = $value;
        return $this;
    }

    /**
     * 类型：判断数据类型
     */
    public function type(string $dataType) : static
    {
        $this->bindings['type'] = $dataType;
        return $this;
    }

    /**
     * 检查：正则表达式
     */
    public function regex(string $exp) : static
    {
        $this->bindings['regex'] = $exp;
        return $this;
    }

    /**
     * 长度：字符长度在最小(含)和最大(含)之间
     */
    public function length(int $min, ?int $max = null) : static
    {
        $this->bindings['length'] = is_null($max) ? $min : [$min, $max];
        return $this;
    }

    /**
     * 区间：在最小(含)和最大(含)之间
     */
    public function between(int|float|string $min, int|float|string $max) : static
    {
        $this->bindings['between'] = [$min, $max];
        return $this;
    }

    /**
     * 范围：在若干个选项之间
     */
    public function in(array $haystack) : static
    {
        $this->bindings['in'] = $haystack;
        return $this;
    }

    /**
     * 比较：小于
     */
    public function lt(int|float|string $value) : static
    {
        $this->bindings['lt'] = $value;
        return $this;
    }

    /**
     * 比较：小于等于
     */
    public function elt(int|float|string $value) : static
    {
        $this->bindings['elt'] = $value;
        return $this;
    }

    /**
     * 比较：等于
     */
    public function eq(int|float|string $value) : static
    {
        $this->bindings['eq'] = $value;
        return $this;
    }

    /**
     * 比较：大于等于
     */
    public function egt(int|float|string $value) : static
    {
        $this->bindings['egt'] = $value;
        return $this;
    }

    /**
     * 比较：大于
     */
    public function gt(int|float|string $value) : static
    {
        $this->bindings['gt'] = $value;
        return $this;
    }

    /**
     * 数据调试
     */
    public function __debugInfo() : array
    {
        return [
            'name'      =>  $this->name,
            'type'      =>  $this->type,
            'comment'   =>  $this->comment,
            'field'     =>  $this->field,
            'bindings'  =>  $this->bindings,
        ];
    }
}