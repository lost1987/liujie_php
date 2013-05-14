<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-18
 * Time: 下午1:37
 * To change this template use File | Settings | File Templates.
 * 综合数据查询
 */

Class complexDataService extends  Service {

    function complexDataService(){
        parent::__construct();
        $this -> table_complex = 'ComplexData';
        $this -> db -> select_db('MMO2D_RecordLJZM');
    }

    public function lists($page,$condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;

        if($starttime == $endtime){
            $timecondition = " cast(date as datetime)='$starttime' ";
        }else{
            $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        }

        $sql = "select * from (select row_number() over (order by date desc) as rownumber, date ,sum(overyuanbao72) as overyuanbao72,
         sum(registernum) as registernum,sum(createnum) as createnum,sum(loginnum) as loginnum,sum(maxonline) as maxonline,sum(aveonline) as aveonline,sum(recharge) as recharge,
         sum(rechargeperson) as rechargeperson,sum(rechargenum) as rechargenum,sum(newrecharge) as newrecharge,sum(newrechargeperson) as newrechargeperson,sum(oldlogin) as oldlogin,
         sum(oldrecharge) as oldrecharge,sum(consumption) as consumption,sum(overyuanbao) as overyuanbao,sum(arpu) as arpu,sum(newarpu) as newarpu,sum(rechargeratio) as rechargeratio
         from $this->table_complex where sid in ($server_ids) and $timecondition group by date) as t where t.rownumber > $page->start and t.rownumber <= $page->limit";
        $list = $this -> db -> query($sql) -> result_objects();

        foreach($list as &$obj){
              foreach($obj as $k => $v){
                  if(empty($v))
                  $obj -> $k = 'N/A';
              }

              if(gettype($obj->arpu) == 'double')
              $obj->arpu = number_format($obj->arpu,2);
              if(gettype($obj->newarpu) == 'double')
              $obj->newarpu = number_format($obj->newarpu,2);
              if(gettype($obj->rechargeratio) == 'double')
              $obj->rechargeratio = (number_format($obj->rechargeratio,2)*100).'%';
        }

        return $list;
    }

    public function num_rows($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;

        if($starttime == $endtime){
            $timecondition = " cast(date as datetime)='$starttime' ";
        }else{
            $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        }

        $sql = "select count(distinct(date)) as num from $this->table_complex where sid in ($server_ids) and $timecondition";
        return $this -> db -> query($sql) -> result_object() -> num;
    }

    public function total($condition){
        $server_ids = $condition -> server_ids;
        $starttime = $condition->starttime;
        $endtime = $condition->endtime;
        $avecount = (strtotime($endtime) - strtotime($starttime)) / (3600*24);

        if($starttime == $endtime){
            $timecondition = " cast(date as datetime)='$starttime' ";
        }else{
            $timecondition = " cast(date as datetime) >= '$starttime' and cast(date as datetime) <= '$endtime' ";
        }

        $sql = "select
         sum(registernum) as registernum,sum(createnum) as createnum,sum(loginnum) as loginnum,max(maxonline) as maxonline,sum(aveonline) as aveonline,sum(recharge) as recharge,
         sum(rechargeperson) as rechargeperson,sum(rechargenum) as rechargenum,sum(newrecharge) as newrecharge,sum(newrechargeperson) as newrechargeperson,sum(oldlogin) as oldlogin,
         sum(oldrecharge) as oldrecharge,sum(consumption) as consumption,sum(arpu) as arpu,sum(newarpu) as newarpu,sum(rechargeratio) as rechargeratio
         from $this->table_complex where sid in ($server_ids) and $timecondition";

         $obj = $this -> db -> query($sql) -> result_object();
         foreach($obj as $k => &$v){
             if(empty($v))$v = 'N/A';
         }

        $sql = "select overyuanbao72,overyuanbao from $this->table_complex where cast(date as datetime) = '$endtime'";
        $temp = $this->db->query($sql)->result_object();
        $obj->overyuanbao72 =  empty($temp -> overyuanbao72) ? 'N/A' : $temp->overyuanbao72;
        $obj->overyuanbao =  empty($temp -> overyuanbao) ? 'N/A' : $temp->overyuanbao;

        $obj -> aveonline = empty($avecount) ? 'N/A' : round($obj->aveonline/$avecount);
        if(gettype($obj->arpu) == 'double')
            $obj->arpu = empty($obj->newrechargeperson) ? 0 : number_format($obj->recharge/$obj->newrechargeperson/10,2);
        if(gettype($obj->newarpu) == 'double')
            $obj->newarpu = empty($obj->newrechargeperson) ? 0 : number_format($obj->newrecharge/$obj->newrechargeperson/10,2);
        if(gettype($obj->rechargeratio) == 'double')
            $obj->rechargeratio = empty($obj->registernum) ? 0  : number_format($obj->newrechargeperson/$obj->registernum);

         return $obj;
    }
}