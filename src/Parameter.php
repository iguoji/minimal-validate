<?php
declare(strict_types=1);

namespace Minimal\Validate;

use RuntimeException;

/**
 * 参数类
 */
class Parameter
{
    /**
     * 默认值
     */
    protected mixed $defaultValue;

    /**
     * 绑定条件
     */
    protected array $bindings = [];

    /**
     * 构造函数
     * @param $name     string  名称
     * @param $type     string  类型
     * @param $comment  string  备注
     * @param $field    string  对应数据库字段
     */
    public function __construct(protected string $name, protected ?string $type = null, protected ?string $comment = null, protected ?string $field = null)
    {
        // 默认必定验证类型
        $this->type($type);
    }

    /**
     * 获取规则
     */
    public function getRules() : array
    {
        return $this->bindings;
    }

    /**
     * 存在规则
     */
    public function hasRule(string $ruleName) : bool
    {
        return isset($this->bindings[$ruleName]);
    }

    /**
     * 获取名称
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * 获取类型
     */
    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * 获取备注
     */
    public function getComment() : ?string
    {
        return $this->comment;
    }

    /**
     * 获取字段
     */
    public function getField() : ?string
    {
        return $this->field;
    }

    /**
     * 存在默认值
     */
    public function hasDefaultValue() : mixed
    {
        return isset($this->defaultValue);
    }

    /**
     * 获取默认值
     */
    public function getDefaultValue() : mixed
    {
        return $this->defaultValue;
    }

    /**
     * 设置默认值
     */
    public function default(mixed $value) : static
    {
        $this->defaultValue = $value;
        return $this;
    }

    /**
     * 绑定条件
     */
    public function __call(string $ruleName, array $ruleArguments) : ?static
    {
        if (method_exists(Validator::class, $ruleName)) {
            $this->bindings[$ruleName] = $ruleArguments;
            return $this;
        }
        throw new RuntimeException(sprintf('Call to undefined method %s::%s()', static::class, $ruleName));
    }
}