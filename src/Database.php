<?php
declare(strict_types=1);

namespace Minimal\Validate;

use RuntimeException;
use InvalidArgumentException;

/**
 * 数据库参数验证类
 */
class Database
{
    /**
     * 数据仓储
     */
    protected array $storage = [];

    /**
     * 数组格式
     */
    public function array(string $name, Parameter $param)
    {

    }

    /**
     * 模糊匹配
     */
    public function match(string $name, ?string $type = null, ?string $comment = null, ?string $field = null) : Parameter
    {
        return $this->array('match', new Parameter($name, $type, $comment, $field ?? $name));
    }
}