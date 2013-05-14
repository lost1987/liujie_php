<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-26
 * Time: 下午3:25
 * To change this template use File | Settings | File Templates.
 * 全部副本
 */
class DailyCopyDataService extends Service
{
    function DailyCopyDataService(){
        parent::__construct();
        $this -> table_dailycopy = 'DailyCopy';
        $this -> db -> select_db('MMO2D_RecordLJZM');
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';

        $list = array();

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";

        $sql = "select * from (select row_number() over (order by copyname asc) as rownumber,
                copyname,copylevel,sum(loginlevel) as loginlevel,sum(jioncopyperson) as jioncopyperson,
                sum(jioncopynum) as jioncopynum,sum(completecopynum) as completecopynum
                 from $this->table_dailycopy where sid in ($server_ids) and $timecondition group by copyname,copylevel) as t where t.rownumber > $page->start and t.rownumber <= $page->limit";


        $list = $this -> db -> query($sql) -> result_objects();

        foreach($list as &$obj){
            $obj -> completecopypercent = $obj -> jioncopynum == 0 ? '0%' : number_format($obj->completecopynum/$obj->jioncopynum,4)*100 . '%';
            $obj -> loginpercent = $obj -> loginlevel == 0 ? '0%' : number_format($obj->jioncopyperson/$obj->loginlevel,4)*100 . '%';
            $obj -> jionave = $obj->jioncopyperson == 0 ? '0' : number_format($obj->jioncopynum/$obj->jioncopyperson,4);
        }

        return $list;
    }

    public function num_rows($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        $sql = "select copyname from $this->table_dailycopy where sid in ($server_ids) and $timecondition group by copyname,copylevel";
        $obj = $this -> db -> query($sql) -> result_objects();
        return count($obj);
    }
}
