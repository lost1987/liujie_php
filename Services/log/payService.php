<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-4-1
 * Time: 上午10:18
 * To change this template use File | Settings | File Templates.
 * 充值日志
 */
class PayService extends ServerDBChooser
{

    private $ratio = 10;
    private $paysource = array(
        0 => '内部充值'
    );

    function PayService(){
        $this -> table_player = $this->prefix_1.'user';
        $this -> table_record = $this->prefix_2.'record';
    }


    public function lists($page,$condition){
           $this->dbConnect($condition->server,$condition->server->dynamic_dbname);
           $condSql = $this->getCondition($condition);
           $sql = "select * from (select row_number() over (order by a.time desc) as rownumber,a.id1,
            a.param2 as yuanbao,a.id2, a.str2,CONVERT(varchar(20),  a.time, 120) as time,b.id,b.account_name,b.name,b.levels from $this->table_record a left join $this->table_player b on a.id1=b.id $condSql)
            as t where t.rownumber > $page->start and t.rownumber <= $page->limit";

            error_log($sql);
           $list = $this -> db -> query($sql) -> result_objects();
           foreach($list  as &$obj){
               $obj->servername = $condition->server->name;
               $obj->money = '￥'.($obj->yuanbao/$this->ratio);
               $obj->paysource = $this->paysource[empty($obj->id2) ? 0 : $obj->id2];
           }
           return $list;
    }

    public function num_rows($condition){
        $this->dbConnect($condition->server,$condition->server->dynamic_dbname);
        $condSql = $this->getCondition($condition);
        $sql = "select count(a.id) as num from $this->table_record a left join $this->table_player b on a.id1=b.id $condSql";
        return $this->db->query($sql)->result_object()->num;
    }


    public function getCondition($condition){
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;
        $account_name = $condition -> account_name;
        $level_start = $condition -> level_start;
        $level_limit = $condition -> level_limit;
        $vip_start = $condition -> vip_start;
        $vip_limit = $condition -> vip_limit;

        $sql = '';
        if(!empty($starttime) && !empty($endtime)){
            $starttime .= ' 00:00:00';
            $endtime .= ' 23:59:59';

            $cond1 = " cast(a.time as datetime) >= '$starttime' and cast(a.time as datetime) <= '$endtime'";
        }

        if(!empty($account_name)){
            $cond2 = "  (b.account_name like '$account_name%' or b.name like '$account_name%' )";
        }

        if(!empty($level_limit) && !empty($level_start)){
            if($level_limit == $level_start){
                $cond3 = " b.levels = $level_limit ";
            }else{
                $cond3 = " (b.levels >= $level_start and b.levels <= $level_limit ) ";
            }
        }

        if(!empty($vip_start) && !empty($vip_limit)){
            if($vip_limit == $vip_start)
                $cond4= " (b.mask0%100) = $vip_limit";
            else
                $cond4 = " ((b.mask0%100) >= $vip_start and (b.mask0%100) <= $vip_limit) ";
        }

        if(isset($cond1)){
            $sql .= $cond1;
        }

        if(isset($cond2)){
            if(!empty($sql)){
                $sql .= ' and '.$cond2;
            }else{
                $sql .= $cond2;
            }
        }

        if(isset($cond3)){
            if(!empty($sql)){
                $sql .= ' and '.$cond3;
            }else{
                $sql .= $cond3;
            }
        }

        if(isset($cond4)){
            if(!empty($sql)){
                $sql .= ' and '.$cond4;
            }else{
                $sql .= $cond4;
            }
        }

        if(empty($sql)) return ' where a.param4 = 44 and str2 <> null ';
        return $sql = ' where a.param4 = 44 and  str2 <> null and '.$sql;
    }
}
