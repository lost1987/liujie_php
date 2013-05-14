<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-20
 * Time: 下午1:53
 * To change this template use File | Settings | File Templates.
 * 创建注册
 */
class CreateDataService extends Service
{
    function CreateDataService(){
        parent::__construct();
        $this -> table_createData = 'CreateData';
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
            case 1: $sql = "select CONVERT(varchar(30), cast(date as datetime), 120) as date,registernum,createnum from $this->table_createData where sid in ($server_ids) and $timecondition";
                    break;
            //24小时
            case 2: $sql = "select CONVERT(varchar(10), cast(date as datetime), 120) as date,sum(registernum) as registernum,sum(createnum) as createnum from $this->table_createData where sid in ($server_ids) and $timecondition
                            group by CONVERT(varchar(10), cast(date as datetime), 120)";
        }

        $list = $this -> db -> query($sql) -> result_objects();
        return $list;
    }

}
