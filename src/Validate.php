<?php
declare(strict_types=1);

namespace Minimal\Validate;

use RuntimeException;
use InvalidArgumentException;

/**
 * 验证器
 */
class Validate
{
    /**
     * 字段
     */
    protected array $fields = [];

    /**
     * 规则
     */
    protected array $rules = [];

    /**
     * 默认值
     */
    protected array $defaults = [];

    /**
     * 信息
     */
    protected array $messages = [
        'required'  =>  '很抱歉、:attribute不能为空！',
        'type'      =>  '很抱歉、:attribute必须是:type类型！',
        'min'       =>  '很抱歉、:attribute:unit不能小于:condition！',
        'max'       =>  '很抱歉、:attribute:unit不能大于:condition！',
        'in'        =>  '很抱歉、:attribute只能在[:condition]之间！',
    ];

    /**
     * 请求对象
     */
    protected $req;

    /**
     * 数据
     */
    protected array $data = [];

    /**
     * 检测数据
     */
    public function check(array $fields, array $parameter = []) : array
    {
        // 参数整理
        $parameter = array_merge($this->getData(), $parameter);
        // 最终数据
        $data = [];
        // 循环字段
        foreach ($fields as $field => $ruleStr) {
            // 没有额外的规则
            if (is_int($field)) {
                $field = $ruleStr;
                $ruleStr = '';
            }
            // 参数的值
            $value = $parameter[$field] ?? null;
            // 是否必填
            $isRequired = false;
            // 是否使用默认值
            $isDefault = false;
            // 获取规则
            $rules = $this->getRules($field, $ruleStr);
            // 验证类型
            foreach ($rules as $rule) {
                // 验证方法和参数
                $method = array_shift($rule);
                array_unshift($rule, $value);
                $args = $rule;
                // 全局型规则处理
                if ($method == 'required') {
                    // 必填项
                    $isRequired = true;
                } else if ($method == 'default') {
                    // 标记使用默认值
                    $isDefault = true;
                    // 用户没有提供值
                    if (is_null($value)) {
                        // 直接给与默认值
                        $value = empty($args[1]) ? $this->getDefault($field) : $args[1][0];
                    }
                    continue;
                }
                // 不存在的规则
                if (!method_exists($this, $method)) {
                    throw new RuntimeException(sprintf('unknown validate rule "%s"', $method));
                }
                // 提供了值或是必填，但是规则验证又不通过
                if ((!is_null($value) || $isRequired) && !$this->$method(...$args)) {
                    $message = $this->getMessage($field, $method, $args);
                    throw new InvalidArgumentException($message);
                }
                // 如果执行了类型判断，则进行一次类型转换
                if ($method == 'type' && !is_null($value)) {
                    $value = $this->transform($value, $this->getType($field));
                }
            }
            // 保存数据
            if ($isRequired || $isDefault || isset($parameter[$field])) {
                $data[$field] = $value;
            }
        }
        // 返回数据
        return $data;
    }

    /**
     * 类型转换
     */
    public function transform(mixed $value, string $type) : mixed
    {
        switch($type)
        {
            case 'int':
            case 'time':
                return (int) $value;
                break;
            case 'string':
                return $value === 'null' ? null : (string) $value;
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * 检查：必填项
     */
    public function required(mixed $value) : bool
    {
        return isset($value);
    }

    /**
     * 检查：类型
     */
    public function type(mixed $value, string $type) : bool
    {
        switch($type) {
            case 'int':
            case 'time':
                return filter_var($value, FILTER_VALIDATE_INT) === false ? false : true;
                break;
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) === false ? false : true;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * 检查：最小长度|数值
     */
    public function min(mixed $value, int $condition = 0) : bool
    {
        if (is_int($value)) {
            return $value >= $condition;
        } else if (is_string($value)) {
            return strlen($value) >= $condition;
        }
        return false;
    }

    /**
     * 检查：最大长度|数值
     */
    public function max(mixed $value, int $condition = 0) : bool
    {
        if (is_int($value)) {
            return $value <= $condition;
        } else if (is_string($value)) {
            return strlen($value) <= $condition;
        }
        return false;
    }

    /**
     * 检查：在指定范围内
     */
    public function in(mixed $value, array $array) : bool
    {
        return in_array($value, $array);
    }

    /**
     * 获取类型
     */
    public function getType(string $field) : string
    {
        return $this->fields[$field]['type'] ?? 'string';
    }

    /**
     * 存在类型
     */
    public function hasType(string $field) : bool
    {
        return isset($this->fields[$field]['type']);
    }

    /**
     * 根据字符串解析规则
     */
    public function parseRule(string $str) : array
    {
        $rules = [];
        $str = trim($str);
        if (!strlen($str)) {
            return $rules;
        }
        $arr = array_map(fn($s) => trim($s), explode('|', $str));
        foreach ($arr as $str1) {
            [$name, $argsStr] = strpos($str1, ':') ? explode(':', $str1) : [$str1, null];
            $rules[] = [$name, is_null($argsStr) ? [] : array_map(fn($s) => trim($s), explode(',', $argsStr))];
        }
        return $rules;
    }

    /**
     * 获取规则
     */
    public function getRules(string $field, string $extraRuleStr = null) : array
    {
        // 默认规则
        $rules = [];
        // 首先必须验证类型
        if ($this->hasType($field)){
            $rules[] = ['type', $this->getType($field)];
        }
        // 当前默认规则数组中的规则类型
        $defaultRuleKeys = array_column($rules, 0, 0);
        // 自定义或特殊规则
        $extraRules = $this->parseRule($extraRuleStr);
        foreach ($extraRules as $rule) {
            if (!isset($defaultRuleKeys[$rule[0]])) {
                $rules[] = $rule;
            }
        }
        // 当前默认规则数组中的规则类型
        $defaultRuleKeys = array_column($rules, 0, 0);
        // 数据类型规则
        foreach ($this->rules[$field] ?? [] as $name => $condition) {
            if (!isset($defaultRuleKeys[$name])) {
                $rules[] = [$name, $condition];
            }
        }
        // 返回规则
        return $rules;
    }

    /**
     * 获取默认值
     */
    public function getDefault(string $field) : mixed
    {
        $value = $this->defaults[$field] ?? null;
        if (is_callable($value)) {
            $value = $value();
        }
        if (is_null($value)) {
            switch ($this->getType($field)) {
                case 'int':
                case 'float':
                    $value = 0;
                    break;
                case 'string':
                    $value = '';
                    break;
                case 'time':
                    $value = time();
                    break;
            }
        }
        return $value;
    }

    /**
     * 获取备注
     */
    public function getComment(string $field) : string
    {
        return $this->fields[$field]['comment'] ?? $field;
    }

    /**
     * 获取消息
     */
    public function getMessage(string $field, string $rule, array $context = []) : string
    {
        // 上下文
        $context['attribute'] = $this->getComment($field);
        $context['unit'] = $this->getType($field) == 'string' ? '长度' : '';
        $context['condition'] = $context[1];
        // 默认消息
        $message = 'verifier check ":attribute" failed';
        // 字段规则
        $key = sprintf("%s.%s", $field, $rule);
        if (isset($this->messages[$key])) {
            $message = $this->messages[$key];
        } else if (isset($this->messages[$rule])) {
            $key = $rule;
            $message = $this->messages[$rule];
        }
        // 填充上下文
        $message = $this->interpolate($message, $context);
        // 返回消息
        return $message;
    }

    /**
     * 设置请求
     */
    public function setRequest($req) : static
    {
        $this->req = $req;
        return $this;
    }

    /**
     * 获取请求
     */
    public function getRequest() : mixed
    {
        return $this->req;
    }

    /**
     * 设置数据
     */
    public function setData(array $data) : static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取数据
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * 用上下文替换占位符
     */
    public function interpolate(string $message, array $context = []) : string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace[':' . $key] = is_array($val) ? implode(',', $val) : $val;
        }
        return strtr($message, $replace);
    }
}