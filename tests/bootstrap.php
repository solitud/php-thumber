<?php

/**
 * This file is part of php-thumber.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/php-thumber
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

require_once 'vendor/autoload.php';

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__) . DS);
define('TESTS', ROOT . 'tests' . DS);
define('TMP', sys_get_temp_dir() . DS . 'php-thumber' . DS);

@mkdir(TMP, 0777, true);

require_once 'config/bootstrap.php';

define('THUMBER_EXAMPLE_DIR', TESTS . 'examples' . DS);
define('THUMBER_COMPARING_DIR', THUMBER_EXAMPLE_DIR . 'comparing_files' . DS . THUMBER_DRIVER . DS);
echo 'Running tests for "' . THUMBER_DRIVER . '" driver ' . PHP_EOL;

if (!class_exists('PHPUnit\Runner\Version')) {
    class_alias('PHPUnit_Framework_Error_Deprecated', 'PHPUnit\Framework\Error\Deprecated');
    class_alias('PHPUnit_Framework_ExpectationFailedException', 'PHPUnit\Framework\ExpectationFailedException');
}
