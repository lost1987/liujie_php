<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-6-26
 * Time: 上午11:30
 * To change this template use File | Settings | File Templates.
 */

$GLOBALS['excel_keys'] = array(
     //0表示综合数据(只需要传入按逗号分割的server_id),1表示非综合数据(需传入server对象数组)
    'synthesis_list' => 'complexDataService|lists|0',

);

$GLOBALS['autoload_folders'] = array(
    'Excel',
    'DB',
    'Lib',
    'Services',
    'Services/adminfunc',
    'Services/log',
    'Services/managefunc',
    'Services/player',
    'Services/sys',
);