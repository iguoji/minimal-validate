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
     * 高级组合变量名
     */
    protected string $variable = '_complex_';

    /**
     * 参数列表
     */
    protected array $parameters = [];

    /**
     * 排序字段
     */
    protected array $orders = [];

    /**
     * 分页设置
     */
    protected array $page = [];

    /**
     * 构造函数
     * @param $structs  array   单个或多个数据表的字段信息
     * @param $tables   array   参数1存在多个表字段时，需要用到
     */
    public function __construct(protected array $structs = [], protected array $tables = [])
    {
    }

    /**
     * 绑定参数
     */
    public function bind(string $name, ?string $type = 'string', ?string $comment = null) : Parameter
    {
        if (func_num_args() == 1) {
            // 只有一个参数，属于数据库字段
            [$field, $type, $comment] = $this->getField($name);
        } else if (func_num_args() == 2) {
            // 有两个参数，一个虚构名，一个数据库字段名
            [$field, $type, $comment] = $this->getField($type);
        } else {
            // 多个参数，纯虚构，没有对应数据库字段
            $field = null;
        }

        // 整理参数
        $type = in_array($type, ['string', 'int', 'float', 'bool', 'array']) ? $type : 'string';
        $comment = $comment ?: $name;

        // 返回结果
        return $this->parameters[$name] = new Parameter($name, $type, $comment, $field);
    }

    /**
     * 排序字段
     */
    public function order(string $name, string $type = 'asc') : static
    {
        if (!isset($this->parameters[$name])) {
            throw new InvalidArgumentException(sprintf('很抱歉、排序字段[%s]尚未绑定！', $name));
        }
        if (!in_array($type, ['asc', 'desc'])) {
            throw new InvalidArgumentException(sprintf('很抱歉、排序方式[%s]不存在！', $type));
        }
        $this->orders[$name] = $type;


        // $param = $this->parameters['order'] ?? $this->bind('order', 'array', '排序字段');
        // $param->key($name)->value()

        return $this;
    }

    /**
     * 分页设置
     */
    public function page(int $no, int $size) : static
    {
        $this->page = [$no, $size];
        return $this;
    }

    /**
     * 校验数据
     */
    public function check(array $userParams) : array
    {
        // 最终数据
        $data = [];

        // 循环：预定参数
        foreach ($this->parameters as $param) {
            // 用户未提供值的参数
            if (!isset($userParams[$param->getName()])) {
                // 必填参数 - 且 不存在默认值
                if ($param->hasRule('required') && !$param->hasDefaultValue()) {
                    throw new InvalidArgumentException(sprintf('很抱歉、%s必须提供！', $param->getComment()));
                }
                // 给与默认值
                $userParams[$param->getName()] = $param->getDefaultValue();
            }
        }

        // 高级组合
        $complex = $userParams[$this->variable] ?? [];

        // 循环：高级规则
        foreach ($complex as $ruleName => $ruleArguments) {
            if ($ruleName == 'order') {
                // 排序规则
                $complex[$ruleName] = array_filter($ruleArguments, fn($v, $k) => isset($this->orders[$k]) && in_array($v, ['desc', 'asc']), ARRAY_FILTER_USE_BOTH);
            } else if ($ruleName == 'page') {
                // 分页规则
                if (!is_array($ruleArguments) || count($ruleArguments) != 2) {
                    $ruleArguments = $this->page;
                }
                $complex[$ruleName] = array_slice(array_map( fn($v) => is_scalar($v) ? (int) $v : 0, $ruleArguments ), 0, 2);
            } else if (!method_exists(Validator::class, $ruleName)) {
                // 无效规则
                unset($complex[$ruleName]);
            }
        }

        // 循环：用户参数
        foreach ($userParams as $key => $value) {
            // 参数过滤
            if (!isset($this->parameters[$key])) {
                continue;
            }
            // 获取规则
            $rules = $this->parameters[$key]->getRules();

            echo PHP_EOL;
            echo str_repeat('=', 30), PHP_EOL;
            echo $key, PHP_EOL;
            var_dump($value);

            // 循环规则 -
            foreach ($rules as $ruleName => $ruleArguments) {
                // 存入高级组合
                if (is_array($value)) {
                    $complex[count($value) <= 2 ? 'between' : 'in'][$key] = $value;
                }
                $complex[$ruleName][$key] = $value;


                echo str_repeat('-', 30), PHP_EOL;
                echo $ruleName, PHP_EOL;
                var_dump($ruleArguments);

                // 根据需要传递上下文
                if (Validator::needContext($ruleName)) {
                    $ruleArguments[] = $userParams;
                    $ruleArguments[] = $data;
                }
                // 校验失败
                if (false === Validator::$ruleName($value, ...$ruleArguments)) {
                    throw new RuntimeException(
                        $this->getMessage($key, $ruleName, $ruleArguments, $userParams, $data)
                    );
                }
            }
            // 保存数据 - 同时转换类型
            $data[$key] = $this->transform($value, $this->parameters[$key]->getType());
        }

        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
        var_dump($complex);
        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;


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
        // 最终结果
        $message = sprintf(
            '很抱歉、参数[%s]验证失败, 规则[%s]%s',
            $this->parameters[$name]->getComment(), $ruleName,
            '[' . (is_array($ruleArguments) ? implode(',', $ruleArguments) : $ruleArguments) . ']',
        );
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
        return [$field['name'], $field['type'], $field['comment']];
    }
}