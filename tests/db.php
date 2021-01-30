<?php
declare(strict_types=1);

require_once '../src/Database.php';
require_once '../src/Parameter.php';

use Minimal\Validate\Database;
use Minimal\Validate\Parameter;

/**
 *
 * var jsData = {
 *      match: {
 *          phone: '187'
 *      },
 *      compare: {
 *          zone: 86,
 *          isBet: -1,
 *          money:
 *      }
 * }
 *
 */


$key = new Key();
$key->in('field1', 'field2', 'field3');

$value = new Value();

