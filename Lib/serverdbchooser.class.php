<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-27
 * Time: 下午3:02
 * To change this template use File | Settings | File Templates.
 */
abstract class ServerDBChooser
{

    protected  $db;
    protected  $prefix_1 = 'fr_';
    protected  $prefix_2 = 'fr2_';
    protected  $prefix_3 = 'ht_';

    protected  function dbConnect($server,$dbname='',$newlink=FALSE){
        $this -> db = new DB();
        $this -> db -> connect($server->ip.':'.$server->port,$server->dbuser,$server->dbpwd,$newlink);
        if(!empty($dbname))
        $this -> db -> select_db($dbname);
    }

    protected  function dbClose(){
        $this -> db -> close();
    }

    protected abstract function getCondition($condition);

}
