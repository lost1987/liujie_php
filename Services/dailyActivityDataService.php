<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-26
 * Time: 下午3:37
 * To change this template use File | Settings | File Templates.
 * 各类活动
 */
class DailyActivityDataService extends Service
{
    function DailyActivityDataService(){
        parent::__construct();
        $this -> table_dailyactivity = 'DailyActivity';
        $this -> db -> select_db('MMO2D_RecordLJZM');
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';

        $list = array();

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";

        $sql = "select * from (select row_number() over (order by activityname asc) as rownumber, activityname,
                activitylevel,sum(loginlevel) as loginlevel,sum(jionactivityperson) as jionactivityperson,
                sum(jionactivitynum) as jionactivitynum,sum(completeactivitynum) as completeactivitynum
                 from $this->table_dailyactivity where sid in ($server_ids) and $timecondition group by activityname,activitylevel) as t where t.rownumber > $page->start and t.rownumber <= $page->limit";


        $list = $this -> db -> query($sql) -> result_objects();

        foreach($list as &$obj){
            $obj -> completeactivitypercent = $obj->jionactivitynum == 0 ? '0%' :  number_format($obj->completeactivitynum/$obj->jionactivitynum,4)*100 . '%';
            $obj -> loginpercent = $obj->loginlevel==0 ? '0%' :  number_format($obj->jionactivityperson/$obj->loginlevel,4)*100 . '%';
            $obj -> jionave = $obj->jionactivityperson == 0  ? '0' : number_format($obj->jionactivitynum/$obj->jionactivityperson,4);
        }

        return $list;
    }

    public function num_rows($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime.' 00:00:00';
        $endtime = $condition->endtime.' 23:59:59';

        $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        $sql = "select activityname  from $this->table_dailyactivity where sid in ($server_ids) and $timecondition group by activityname";
        $obj = $this -> db -> query($sql) -> result_objects();
        return count($obj);
    }
}
