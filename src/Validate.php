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
     * 数据库专用字段
     */
    protected array $fields = [];

    /**
     * 参数
     * 临时验证专用字段
     */
    protected array $params = [];

    /**
     * 默认值
     */
    protected array $defaults = [];

    /**
     * 错误信息
     */
    protected array $messages = [];

    /**
     * 内置错误信息
     */
    protected array $defaultMessages = [
        'required'      =>  '很抱歉、:attribute不能为空！',
        'type'          =>  '很抱歉、:attribute必须是:type类型！',
        'confirm'       =>  '很抱歉、:attribute必须和:attribute2保持一致！',
        'lt'            =>  '很抱歉、:attribute必须小于:condition！',
        'elt'           =>  '很抱歉、:attribute必须小于等于:condition！',
        'eq'            =>  '很抱歉、:attribute必须等于:condition！',
        'gt'            =>  '很抱歉、:attribute必须大于:condition！',
        'egt'           =>  '很抱歉、:attribute必须大于等于:condition！',
        'in'            =>  '很抱歉、:attribute只能在[:condition]之间！',
        'dateFormat'    =>  '很抱歉、:attribute的格式必须是[:condition]！',
        'length'        =>  '很抱歉、:attribute的长度只能在[:condition]之间！',
        'alpha'         =>  '很抱歉、:attribute只能是纯字母！',
        'alphaNum'      =>  '很抱歉、:attribute只能是字母和数字！',
        'alphaDash'     =>  '很抱歉、:attribute只能是字母和数字，下划线_及破折号-！',
        'chs'           =>  '很抱歉、:attribute只能是汉字！',
        'chsAlpha'      =>  '很抱歉、:attribute只能是汉字、字母！',
        'chsAlphaNum'   =>  '很抱歉、:attribute汉字、字母和数字！',
        'chsDash'       =>  '很抱歉、:attribute只能是汉字、字母、数字和下划线_及破折号-',
        'mobile'        =>  '很抱歉、:attribute格式不正确！',
        'idCard'        =>  '很抱歉、:attribute格式不正确！',
        'zip'           =>  '很抱歉、:attribute格式不正确！',
    ];

    /**
     * 内置正则验证规则
     * 取自ThinkPHP
     */
    protected $defaultRegex = [
        'alpha'       => '/^[A-Za-z]+$/',
        'alphaNum'    => '/^[A-Za-z0-9]+$/',
        'alphaDash'   => '/^[A-Za-z0-9\-\_]+$/',
        'chs'         => '/^[\x{4e00}-\x{9fa5}]+$/u',
        'chsAlpha'    => '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u',
        'chsAlphaNum' => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u',
        'chsDash'     => '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u',
        'mobile'      => '/^1[3-9]\d{9}$/',
        'idCard'      => '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/',
        'zip'         => '/\d{6}/',
    ];

    /**
     * 检测数据
     * @param $field array  字段规则列表：[字段1 => 额外规则,字段2 => 额外规则]
     */
    public function check(array $fields, array $parameters = []) : array
    {
        // 参数整理
        $data = [];

        // 循环字段
        foreach ($fields as $field => $ruleStr) {
            // 可选参数，没有额外的规则
            if (is_int($field)) {
                $field = $ruleStr;
                $ruleStr = '';
            }
            // 参数的值
            $value = $parameters[$field] ?? null;

            // 获取规则
            $rules = $this->getRules($field, $ruleStr);
            $ruleNames = array_column($rules, 0);

            // 是否必填
            $isRequired = in_array('required', $ruleNames);
            // 是否使用默认值
            $isDefault = !$isRequired && !isset($parameters[$field]);

            // 循环验证
            foreach ($rules as $rule) {

                // 方法名称、即验证规则名
                $method = array_shift($rule);
                // 将值插入规则前面，方便直接给函数调用
                array_unshift($rule, $value);
                // 参数列表、即验证参数表
                $arguments = $rule;

                // 比较函数
                if ($method == 'confirm') {
                    $arguments[] = $parameters;
                }

                // 存在正则表达式、将方法名调整为正则
                if (isset($this->defaultRegex[$method])) {
                    $arguments[] = $this->defaultRegex[$method];
                    $method = 'regex';
                }

                // 不存在指定的检查函数
                if (!method_exists($this, $method)) {
                    throw new RuntimeException(sprintf('unknown validate rule "%s"', $method));
                }
                // 执行检查，但是没通过
                if ((!is_null($value) || $isRequired) && !$this->$method(...$arguments)) {
                    $message = $this->getMessage($field, $method, $arguments);
                    throw new InvalidArgumentException($message);
                }
            }

            // 类型转换
            if (!is_null($value)) {
                $value = $this->transform($value, $this->getType($field));
            }
            // 给与默认值
            if ($isDefault && isset($this->defaults[$field])) {
                $value = $this->getDefault($field);
            }

            // 保存数据
            $data[$field] = $value;
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
            case 'float':
                return (float) number_format((float) $value, 4, '.', '');
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
     * 类型转换、批量
     */
    public function transforms(array $data) : array
    {
        foreach ($data as $field => $value) {
            $data[$field] = $this->transform($value, $this->getType($field));
        }
        return $data;
    }

    /**
     * 检查：正则表达式
     */
    public function regex(int|float|bool|string $value, string $rule): bool
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        } elseif (isset($this->defaultRegex[$rule])) {
            $rule = $this->defaultRegex[$rule];
        }

        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }

        return is_scalar($value) && 1 === preg_match($rule, (string) $value);
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
     * 检查：两个字段比较
     */
    public function confirm(mixed $value, string $field, array $data) : bool
    {
        return $value == ($data[$field] ?? null);
    }

    /**
     * 检查：必须小于数值
     */
    public function lt(mixed $value, mixed $condition) : bool
    {
        return $value < $condition;
    }

    /**
     * 检查：必须小于等于数值
     */
    public function elt(mixed $value, mixed $condition) : bool
    {
        return $value <= $condition;
    }

    /**
     * 检查：必须等于数值
     */
    public function eq(mixed $value, mixed $condition) : bool
    {
        return $value == $condition;
    }

    /**
     * 检查：必须大于数值
     */
    public function gt(mixed $value, mixed $condition) : bool
    {
        return $value > $condition;
    }

    /**
     * 检查：必须大于等于数值
     */
    public function egt(mixed $value, mixed $condition) : bool
    {
        return $value >= $condition;
    }

    /**
     * 检查：在指定范围内
     */
    public function in(mixed $value, ...$array) : bool
    {
        return in_array($value, $array);
    }

    /**
     * 检查：字符串长度
     */
    public function length(mixed $value, int|string $min, int|string $max = null) : bool
    {
        $length = strlen((string) $value);
        if (is_null($max)) {
            return $length == $min;
        } else {
            return $length >= $min && $length <= $max;
        }
    }

    /**
     * 检查：日期格式
     */
    public function dateFormat(string $value, string $rule) : bool
    {
        $info = date_parse_from_format($rule, $value);
        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    /**
     * 获取类型
     */
    public function getType(string $field) : string
    {
        return $this->fields[$field]['type'] ?? $this->params[$field]['type'] ?? 'string';
    }

    /**
     * 存在类型
     */
    public function hasType(string $field) : bool
    {
        return isset($this->fields[$field]['type']) || isset($this->params[$field]['type']);
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
        // ['rule1', 'rule2:1,2', 'rule3:"a","b"']
        $array = array_map(fn($s) => trim($s), explode('|', $str));

        foreach ($array as $str1) {
            // [ruleName, null]  || [ruleName, "1, 2"]  || [ruleName, "a", "b"]
            [$name, $argsStr] = false !== strpos($str1, ':') ? explode(':', $str1, 2) : [$str1, null];

            $rule = [$name];
            if (!is_null($argsStr)) {
                $rule = array_merge($rule, array_map(fn($s) => trim($s), explode(',', $argsStr)));
            }
            $rules[] = $rule;
        }
        return $rules;
    }

    /**
     * 获取规则
     */
    public function getRules(string $field, string $extraRuleStr = null) : array
    {
        // 额外规则
        $rules = $this->parseRule($extraRuleStr);
        // 验证类型
        if ($this->hasType($field)){
            $rules[] = ['type', $this->getType($field)];
        }
        // 字段规则
        $rules = array_merge(
            $rules,
            $this->parseRule($this->fields[$field]['rule'] ?? ''),
            $this->parseRule($this->params[$field]['rule'] ?? ''),
        );
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
    public function getName(string $field) : string
    {
        return $this->fields[$field]['name'] ?? $this->params[$field]['name'] ?? $field;
    }

    /**
     * 获取消息
     */
    public function getMessage(string $field, string $rule, array $context = []) : string
    {
        // 上下文
        $context['condition'] = implode(' - ', array_filter(array_slice($context, 1), fn($item) => is_scalar($item)));
        $context['attribute'] = $this->getName($field);
        $context['unit'] = $this->getType($field) == 'string' ? '长度' : '';

        // 比较
        if ($rule == 'confirm') {
            $context['attribute2'] = $this->getName($context[1]);
        }

        // 类型调整
        if ($rule == 'type') {
            switch ($context[1]) {
                case 'int':
                    $context['type'] = '整数';
                    break;
                case 'float':
                    $context['type'] = '数字';
                    break;
            }
        }

        // 正则调整
        if ($rule == 'regex') {
            foreach ($this->defaultRegex as $k => $v) {
                if ($v == $context[1]) {
                    $rule = $k;
                    break;
                }
            }
        }

        // 默认消息
        $message = 'validate check ":attribute" failed in ' . $rule;

        // 字段规则
        $key = sprintf("%s.%s", $field, $rule);

        if (isset($this->messages[$key])) {
            $message = $this->messages[$key];

        } else if (isset($this->defaultMessages[$key])) {
            $message = $this->defaultMessages[$key];

        } else if (isset($this->messages[$rule])) {
            $key = $rule;
            $message = $this->messages[$rule];

        } else if (isset($this->defaultMessages[$rule])) {
            $key = $rule;
            $message = $this->defaultMessages[$rule];
        }

        // 填充上下文
        $message = $this->interpolate($message, $context);
        // 返回消息
        return $message;
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