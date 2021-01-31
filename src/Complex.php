<?php
declare(strict_types=1);

namespace Minimal\Validate;

use RuntimeException;
use InvalidArgumentException;

/**
 * 复杂验证类
 */
class Complex
{
    /**
     * 类型标签
     */
    protected array $typeLabels = ['int' => '整数', 'float' => '小数', 'array' => '数组', 'timestamp' => '日期时间'];

    /**
     * 参数列表
     */
    protected array $parameters = [];

    /**
     * 错误信息
     */
    protected array $messages = [
        'required'      =>  '很抱歉、:attribute不能为空！',
        'type'          =>  '很抱歉、:attribute必须是:type类型！',
        'confirm'       =>  '很抱歉、:attribute必须和:attribute2保持一致！',

        // 大小
        'lt'            =>  '很抱歉、:attribute必须小于:condition！',
        'elt'           =>  '很抱歉、:attribute必须小于等于:condition！',
        'eq'            =>  '很抱歉、:attribute必须等于:condition！',
        'gt'            =>  '很抱歉、:attribute必须大于:condition！',
        'egt'           =>  '很抱歉、:attribute必须大于等于:condition！',

        // 范围
        'in'            =>  '很抱歉、:attribute只能在[:condition]之间！',

        // 长度
        'length'        =>  '很抱歉、:attribute的长度只能在[:condition]位之间！',
        'length1'       =>  '很抱歉、:attribute的长度必须是[:condition]位！',

        // 正则
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

        // 数组
        'size'          =>  '很抱歉、:attribute的元素数量只能在[:size]个之间',
        'size1'         =>  '很抱歉、:attribute必须是:size个元素',
        'valueType'     =>  '很抱歉、:attribute的元素类型必须在[:valueType]之间！',
        'valueType1'    =>  '很抱歉、:attribute的元素类型必须是:valueType类型！',
    ];

    /**
     * 构造函数
     * @param $structs  array   单个或多个数据表的字段信息
     * @param $tables   array   参数1存在多个表字段时，需要用到
     */
    public function __construct(protected array $structs = [], protected array $tables = [], array $messages = [])
    {
        // 错误消息
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * 绑定参数
     */
    public function bind(string $name, ?string $type = 'string', ?string $comment = null, ?string $field = null) : Parameter
    {
        if (func_num_args() == 1) {
            // 只有一个参数，属于数据库字段
            $field = $name;
            [, $type, $comment] = $this->getField($name);
        } else if (func_num_args() == 2) {
            // 有两个参数，一个虚构名，一个数据库字段名
            $field = $type;
            [, $type, $comment] = $this->getField($type);
        }

        // 整理参数
        // $type = in_array($type, ['string', 'int', 'float', 'bool', 'array', 'timestamp']) ? $type : 'string';
        $comment = $comment ?: $name;

        // 返回结果
        return $this->parameters[$name] = new Parameter($name, $type, $comment, $field);
    }

    /**
     * 排序字段
     */
    public function order(array $rules, string $field = 'order', string $comment = '排序字段') : static
    {
        $keyAlias = [];
        foreach ($rules as $key => $type) {
            if (!isset($this->parameters[$key])) {
                throw new InvalidArgumentException(sprintf('很抱歉、排序字段[%s]尚未绑定！', $key));
            }
            $keyAlias[$key] = $this->parameters[$key]->getField();
        }
        $this->bind($field, 'array', $comment)
            ->key(array_keys($rules))
            ->alias($keyAlias)
            ->value(['desc', 'asc'])
            ->default($rules);
        return $this;
    }

    /**
     * 分页设置
     */
    public function page(int $no, int $size, string $field = 'page', string $comment = '分页字段') : static
    {
        $this->bind($field, 'array', $comment)->valueType('int')->default([$no, $size])->size(2);
        return $this;
    }

    /**
     * 校验数据
     */
    public function check(array $userParams) : array
    {
        // 最终数据
        $data = [];

        var_dump($userParams);

        // 循环：预定参数 [必填|默认值]
        foreach ($this->parameters as $param) {
            // 用户未提供
            if (!isset($userParams[$param->getName()])) {
                // 必填字段 | 且没默认值
                if ($param->isRequired() && !$param->hasDefaultValue()) {
                    throw new InvalidArgumentException(
                        $this->getMessage($param->getName(), 'required', [], $userParams, [])
                    );
                }
                // 赋与默认值
                $userParams[$param->getName()] = $param->getDefaultValue();
            } else {
                // 参数为数组，合并默认值
                // if ($param->getType() == 'array' && $param->hasDefaultValue() && is_array($userParams[$param->getName()])) {
                //     $userParams[$param->getName()] = array_merge($param->getDefaultValue(), $userParams[$param->getName()]);
                // }
            }
        }

        // 循环：用户参数
        foreach ($userParams as $key => $value) {
            // 参数过滤
            if (!isset($this->parameters[$key])) {
                continue;
            }
            // 对应参数
            $param = $this->parameters[$key];
            // 检查参数 [必填 或 提供了值]
            if ($param->isRequired() || isset($value)) {
                // 获取规则
                $rules = $param->getRules();
                echo PHP_EOL;
                echo PHP_EOL;
                echo PHP_EOL;
                echo str_repeat('=', 30);
                echo $key, PHP_EOL;
                var_dump($value);
                // 循环规则 -
                foreach ($rules as $ruleName => $ruleArguments) {
                    echo str_repeat('-', 30);
                    echo $ruleName, PHP_EOL;
                    var_dump($ruleArguments);
                    // 根据需要传递上下文
                    if (Validator::needContext($ruleName)) {
                        $ruleArguments[] = $userParams;
                        $ruleArguments[] = $data;
                    }
                    // 校验失败
                    if (false === Validator::$ruleName($value, ...$ruleArguments)) {
                        throw new InvalidArgumentException(
                            $this->getMessage($key, $ruleName, $ruleArguments, $userParams, $data)
                        );
                    }
                }
                // 数组参数
                if ($param->getType() == 'array' && is_array($value)) {
                    // 别名处理
                    $value = array_combine(array_map(fn($s) => $param->hasAlias($s) ? $param->getAlias($s) : $s, array_keys($value)), array_values($value));
                }
                // 字段名称
                $field = $param->getField() ?? $param->getName();
                // 保存数据 - 同时转换类型
                $data[$field] = $this->transform(
                    $value,
                    $param->getType() == 'array' && count($param->getValueTypes()) == 1
                        ? $param->getValueTypes()[0]
                        : $param->getType()
                );
            }
        }


        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;


        var_dump($data);

        // 返回数据
        return $data;
    }

    /**
     * 类型转换
     */
    public function transform(mixed $value, string $type) : mixed
    {
        if (is_array($value) && in_array($type, ['int', 'float', 'bool', 'string'])) {
            $data = [];
            foreach ($value as $v) {
                $data[] = $this->transform($v, $type);
            }
            return $data;
        }
        switch($type)
        {
            case 'int':
            case 'time':
                return (int) $value;
                break;
            case 'float':
                return (float) number_format((float) $value, 4, '.', '');
                break;
            case 'bool':
                if ($value == 'false') return false;
                if ($value == 'true') return true;
                return (bool) $value;
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
     * 获取信息
     */
    public function getMessage(string $name, string $ruleName, int|float|bool|string|array $ruleArguments, array $userParams, array $data) : string
    {
        // 正则表达式
        if ($ruleName == 'regex' && isset($this->messages[$ruleArguments[0]])) {
            $ruleName = $ruleArguments[0];
        }
        // 消息模板
        $message = $this->messages["$name.$ruleName" . count($ruleArguments)]
            ?? $this->messages["$name.$ruleName"]
            ?? $this->messages[$ruleName . count($ruleArguments)]
            ?? $this->messages[$ruleName]
            ?? '很抱歉、参数[:attribute]验证失败！';
        // 对应参数
        $param = $this->parameters[$name];
        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
        var_dump($ruleArguments);
        // 解析模板
        $message = strtr($message, [
            // 类型
            ':type'         =>  $this->typeLabels[$param->getType()] ?? $param->getType(),
            // 属性1
            ':attribute'    =>  $param->getComment(),
            // 属性2
            ':attribute2'   =>  isset($ruleArguments[0]) && is_string($ruleArguments[0]) && isset($this->parameters[$ruleArguments[0]])
                                    ? $this->parameters[$ruleArguments[0]]->getComment()
                                    : '相关字段',
            // 条件
            ':condition'    =>  implode(',', array_filter(array_values($ruleArguments), fn($v) => is_scalar($v))),
            // 大小
            ':size'         =>  $ruleArguments[0] ?? 0,
            // 元素类型
            ':valueType'    =>  isset($ruleArguments[0]) && is_array($ruleArguments[0])
                                    ? implode(',', array_map(fn($s) => $this->typeLabels[$s] ?? $s, array_filter($ruleArguments[0], fn($v) => is_string($v))))
                                    : '',
        ]);
        // 返回结果
        return $message;
    }

    /**
     * 获取字段
     */
    protected function getField(string $name) : array
    {
        // 得到表名
        $table = count($this->tables) == 1 ? $this->tables[0] : null;
        if (false !== strpos($name, '.')) {
            [$table, $name] = explode('.', $name, 2);
        }

        // 未指定表名，需要搜索
        if (is_null($table) && !empty($this->tables)) {
            foreach ($this->tables as $tableName) {
                if (isset($this->structs[$tableName]['fields'][$name])) {
                    $table = $tableName;
                    break;
                }
            }
        }

        // 返回结果
        $field = $this->structs[$table][$name]['fields'] ?? $this->structs['fields'][$name] ?? [];
        return [$field['name'] ?? $name, $field['type'] ?? 'string', $field['comment'] ?? $name];
    }
}