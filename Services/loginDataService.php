<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-21
 * Time: 上午11:05
 * To change this template use File | Settings | File Templates.
 * 登录在线
 */
class LoginDataService extends Service{
    function LoginDataService(){
        parent::__construct();
        $this -> table_loginData = 'OnlineData';
        $this -> db -> select_db('MMO2D_RecordLJZM');
    }

    public function lists($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';
        $timediff = $condition->timediff;

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        $sql = '';
        switch($timediff){
            //所有
            case 1: $sql = "select CONVERT(varchar(30), cast(date as datetime), 120) as date,loginnum,maxonline,aveonline from $this->table_loginData where sid in ($server_ids) and $timecondition order by date asc";
                break;
            //24小时
            case 2: $sql = "select CONVERT(varchar(10), cast(date as datetime), 120) as date,sum(loginnum) as loginnum,max(maxonline) as maxonline,max(aveonline) as aveonline from $this->table_loginData where sid in ($server_ids) and $timecondition
                            group by CONVERT(varchar(10), cast(date as datetime), 120) order by date asc ";
        }

        $list = $this -> db -> query($sql) -> result_objects();
        return $list;
    }

}

?>