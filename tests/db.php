<?php
declare(strict_types=1);

require_once '../src/Database.php';
require_once '../src/Parameter.php';

use Minimal\Validate\Database;
use Minimal\Validate\Parameter;


var_dump([
    // 1. 常规数据库字段
    new Parameter('id'),
    // 2. 别名字段
    new Parameter('_id', field: 'id'),

    // 3. 用来临时验证的虚拟字段
    (new Parameter('isDeleted', 'int', '是否为黑用'))->in([0, 1]),

    // 4. 默认值
    (new Parameter('isBet', field: 'is_bet'))->default(-1)->in([-1, 0, 1]),
    (new Parameter('isTransfer', field: 'is_transfer'))->default(-1)->in([-1, 0, 1]),

    // 5. 取数据库默认字段
    (new Parameter('zone')),

    // 6. 使用正则验证
    (new Parameter('phone'))->regex('/\d*/'),

    // 7. 正则 + 字符长度
    (new Parameter('realname'))->regex('/\d*/')->length(2, 10),
    (new Parameter('idcard'))->regex('/\d*/')->length(15, 18),

    // 8. 数值范围
    (new Parameter('money'))->between(100, 100000),
    (new Parameter('signupAt', field: 'created_at'))->between('2020-08-08 12:00:00', '4040-08-08 12:00:00'),





    // 9. 纯类型 - 数字索引 - 一维数组
    // [1, 2, 3]
    (new Parameter('param', 'array', '数组参数'))
            ->type([
                // 数值索引     => 数值内容
                'int'           => 'int'
            ]),






    // 10. 非纯类型 - 数字索引 - 一维数组
    // [1, 'a', 3.3, true, [ 'x', 'y', 'z' ] ]
    (new Parameter('param', 'array', '数组参数'))
            ->type([
                // 数值索引     => 多类型内容
                'int'           => Parameter::raw(['int', 'string', 'float', 'bool',  ['string'] ])
            ]),








    // 11. 纯类型 - 数字索引 - 二维数组
    // [ [1, 11, 111], [2, 22, 222] ]
    (new Parameter('param', 'array', '数组参数'))
            ->type([
                'int' => [ 'int' => 'int' ]
            ]),







    // 12. 非纯类型 - 数字索引 - 二维数组
    // [ [1, 'a', 3.3, true], [ 2, 'b', ['ha', 'he'] ] ]
    (new Parameter('param', 'array', '数组参数'))
            ->type([
                // 数值索引     => 多类型内容
                'int'           => [
                    'int'       =>  Parameter::raw(['int', 'string', 'float', 'bool',  ['string'] ])
                ]
            ]),








    // 11. 纯类型 - 字符索引 - 一维数组
    // [ 'a' => 1, 'b' => 2, 'c' => 3 ]
    (new Parameter('param', 'array', '数组参数'))->type(['int' => ['int', 'string', 'float']]),









    // 11. 非纯类型 - 数字索引 - 二维数组
    // [ [1, 'hello', 1.2 ], [ 1, 1, 1], [ 2.2, 2.2, 2.2 ], ['a', 'b', 'c' ] ]
    (new Parameter('numbers', 'array', '数字列表'))->type(['int' => ['int', 'string', 'float']]),

    // (new Parameter('match', 'array', '模糊匹配'))->

    /**
     * 待完成验证
     *
     * + 数组 及 多维数组
     * + 日期格式验证
     *
     *
     * 以下待完成均能取上下文，按优先级从低至高排
     *
     * + 字段多选一（80）
     * + 和其他字段进行比较（90）
     * + 自定义函数验证（100）
     */
]);


$date = '4040-08-08 12:00:00';
if ($date > '2020-08-08 12:00:00') {
    echo '大于', PHP_EOL;
} else if ($date < '4040-08-08 12:00:00') {
    echo '小于', PHP_EOL;
} else {
    echo '等于', PHP_EOL;
}
