<?php
declare(strict_types=1);

namespace Minimal\Validate;

/**
 * 验证器
 * 所有验证方法按优先级从高到低排列
 */
class Validator
{
    /**
     * 需要上下文的函数
     */
    protected static $needContext = ['confirm'];

    /**
     * 内置正则验证规则
     * 取自ThinkPHP
     */
    public static $regexs = [
        'int'         => '/^-?\d+$/',
        'float'       => '/^-?\d+(\.\d+)?$/',
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
     * 是否需要上下文
     */
    public static function needContext(string $ruleName) : bool
    {
        return in_array($ruleName, self::$needContext);
    }

    /**
     * 检查：必填
     */
    public static function required(mixed $value) : bool
    {
        return isset($value);
    }

    /**
     * 检查：判断数据类型
     */
    public static function type(mixed $value, string $dataType) : bool
    {
        if (is_array($value) && in_array($dataType, ['int', 'float', 'bool', 'string'])) {
            foreach ($value as $v) {
                if (false === self::type($v, $dataType)) {
                    return false;
                }
            }
            return true;
        }
        switch ($dataType) {
            case 'int':
                return self::regex($value, 'int');
                break;
            case 'float':
                return self::regex($value, 'float');
                break;
            case 'bool':
                return self::regex($value, 'bool');
                break;
            case 'string':
                return is_scalar($value);
                break;
            case 'array':
                return is_array($value);
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * 检查：正则表达式
     */
    public static function regex(mixed $value, string $rule) : bool
    {
        $rule = self::$regexs[$rule] ?? $rule;
        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            $rule = '/^' . $rule . '$/';
        }
        return is_scalar($value) && 1 === preg_match($rule, (string) $value);
    }

    /**
     * 长度：字符长度在最小(含)和最大(含)之间
     */
    public static function length(int|float|string $value, int $min, ?int $max = null) : bool
    {
        $length = strlen((string) $value);
        if (is_null($max)) {
            return $length == $min;
        } else {
            return $length >= $min && $length <= $max;
        }
    }

    /**
     * 区间：在最小(含)和最大(含)之间
     */
    public static function between(int|float|string $value, int|float|string $min, int|float|string $max) : bool
    {
        if ($min == '-inf' && $max != '+inf') {
            return $this->elt($value, $max, $userParams, $context);
        } else if ($min != '-inf' && $max == '+inf') {
            return $this->egt($value, $min, $userParams, $context);
        } else {
            return $this->elt($value, $max, $userParams, $context) && $this->egt($value, $min, $userParams, $context);
        }
    }

    /**
     * 范围：在若干个选项之中
     */
    public static function in(int|float|string|bool $value, int|float|string|bool ...$haystack) : bool
    {
        return in_array($value, $haystack);
    }

    /**
     * 比较：大于
     */
    public static function gt(int|float|string $value, int|float|string $value2) : bool
    {
        return $value > $value2;
    }

    /**
     * 比较：大于等于
     */
    public static function egt(int|float|string $value, int|float|string $value2) : bool
    {
        return $value >= $value2;
    }

    /**
     * 比较：等于
     */
    public static function eq(int|float|string $value, int|float|string $value2) : bool
    {
        return $value == $value2;
    }

    /**
     * 比较：小于等于
     */
    public static function elt(int|float|string $value, int|float|string $value2) : bool
    {
        return $value <= $value2;
    }

    /**
     * 比较：小于
     */
    public static function lt(int|float|string $value, int|float|string $value2) : bool
    {
        return $value < $value2;
    }

    /**
     * 对比：日期格式
     */
    public static function date(string|array $value, string $format) : bool
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                if (false === self::date($v, $format)) {
                    return false;
                }
            }
            return true;
        }
        echo PHP_EOL;
        echo PHP_EOL;
        var_dump($format);
        var_dump($value);
        echo PHP_EOL;
        echo PHP_EOL;
        $info = date_parse_from_format($format, $value);
        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    /**
     * 字段：和其他字段相等
     */
    public static function confirm(mixed $value, string $name, array $userParams, array $data) : bool
    {
        return isset(self::$userParams[$name]) && $value == self::$userParams[$name];
    }
}