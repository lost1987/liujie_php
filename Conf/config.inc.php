<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-8
 * Time: 下午12:33
 * To change this template use File | Settings | File Templates.
 */

define('APPKEY','buhuan');
define('__QQWRY__' ,BASEPATH."/Lib/QQWry.Dat");
define('PAYHOST','http://58.49.56.147/api_pay/');
//define('PAYHOST','192.168.20.160');
define("DEF_PLATFORM_KEY", "265g");//充值key

$service_paths = array(
    BASEPATH.'/Services/',
    BASEPATH.'/Services/player/',
    BASEPATH.'/Services/log/',
    BASEPATH.'/Services/sys/',
    BASEPATH.'/Services/managefunc/',
    BASEPATH.'/Services/adminfunc/'
);
