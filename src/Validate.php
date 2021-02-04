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
     * 类型转换
     */
    public static function transform(mixed $value, string $type) : mixed
    {
        if (is_array($value) && in_array($type, ['int', 'float', 'bool', 'string'])) {
            $data = [];
            foreach ($value as $v) {
                $data[] = self::transform($v, $type);
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
}