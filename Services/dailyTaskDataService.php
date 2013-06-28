<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-26
 * Time: 下午2:46
 * To change this template use File | Settings | File Templates.
 * 每日任务
 */
class DailyTaskDataService extends Service
{
    function DailyTaskDataService(){
        parent::__construct();
        $this -> table_dailytask = 'DailyTask';
        $this -> db -> select_db('MMO2D_RecordLJZM');
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';

        $list = array();

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";

        $sql = "select * from (select row_number() over (order by taskname asc) as rownumber,taskname, sum(jiontasknum) as jiontasknum,
                sum(accepttasknum) as accepttasknum,sum(completetasknum) as completetasknum, tasklevel,sum(loginlevel) as loginlevel
                 from $this->table_dailytask where sid in ($server_ids) and $timecondition group by taskname,tasklevel) as t where t.rownumber > $page->start and t.rownumber <= $page->limit";


        $list = $this -> db -> query($sql) -> result_objects();

        foreach($list as &$obj){
            //$obj -> completetaskpercent = number_format($obj->completetaskpercent,2)*100 . '%';
           // $obj -> loginpercent = number_format($obj->loginpercent,2)*100 . '%';
            $obj -> completetaskpercent = $obj->accepttasknum == 0 ? '0%' : number_format($obj->completetasknum/$obj->accepttasknum,4)*100 .'%';
            $obj -> loginpercent =  $obj->loginlevel==0 ? '0%' : number_format($obj->jiontasknum/$obj->loginlevel,4)*100 .'%';
            $obj -> jionave =  $obj->jiontasknum==0 ? '0' : number_format($obj->accepttasknum/$obj->jiontasknum,4);
        }

        return $list;
    }

    public function num_rows($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        $sql = "select taskname as num from $this->table_dailytask where sid in ($server_ids) and $timecondition group by taskname,tasklevel";
        $obj = $this -> db -> query($sql) -> result_objects();
        return count($obj);
    }
}
