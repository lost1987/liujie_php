<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-6-26
 * Time: 上午11:29
 * To change this template use File | Settings | File Templates.
 */

define('BASEPATH',dirname(__FILE__).DIRECTORY_SEPARATOR);
require BASEPATH.'Conf/db.inc.php';
require BASEPATH.'Common/common.php';
require BASEPATH.'Conf/config.excel.php';
require BASEPATH.'Excel/input.class.php';
require BASEPATH.'Excel/excelmaker.class.php';
require BASEPATH.'Excel/security.class.php';
require BASEPATH.'Excel/utf8.class.php';


require BASEPATH.'Excel/PHPExcel.php';
require BASEPATH.'Excel/PHPExcel/Writer/Excel2007.php';
//require BASEPATH.'Excel/PHPExcel/Writer/Excel5.php';//office2003 using this
require BASEPATH.'Excel/PHPExcel/IOFactory.php';

ExcelMaker::register_auto_load();

ExcelMaker::getInstance() -> output();