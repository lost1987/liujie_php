<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-4-25
 * Time: 上午11:05
 * To change this template use File | Settings | File Templates.
 * 充值玩家排名
 */
class RechargeOrderService extends ServerDBChooser
{

    private $_profession = array('降魔','御法','牧云');

    function RechargeOrderService(){
        $this->table_record = $this->prefix_2.'record';
        $this->table_player = $this->prefix_1.'user';
    }

    public function lists($condition){
        $servers = $condition->servers;
        $starttime = $condition -> starttime.' 00:00:00';
        $endtime = $condition -> endtime .' 23:59:59';
        $list = array();
        $flag = 1;
        if(count($servers)>0){
            foreach($servers as $server){
                 $this->dbConnect($server,$server->dynamic_dbname);


                //查询玩家充值的元宝
                $sql = "
                select a.*,b.account_name,b.name,b.levels,b.profession from(
                select * from (select row_number() over (order by sum(param2) desc) as rownumber, id1 as pid,sum(param2) as recharge_yuanbao from $this->table_record  where type=0 and param1 = 90000001 and param4 = 44 and id2 <> null and  time > '$starttime' and time < '$endtime'  group by id1 )as t where t.rownumber > 0 and t.rownumber <= 50
                ) as a left join $this->table_player b on a.pid = b.id";

                $templist_recharge = $this -> db -> query($sql) -> result_objects();

                foreach($templist_recharge as $recharge){
                    //查询玩家消耗的总元宝
                    $sql = "select sum(param2) as used_yuanbao from $this->table_record where type = 1 and  param1=90000001 and time > '$starttime' and time < '$endtime' and id1=$recharge->pid";
                    $recharge -> used_yuanbao = $this->db -> query($sql) -> result_object() -> used_yuanbao;

                    //玩家非充值获得的元宝
                    $sql = "select sum(param2) as unrecharge_yuanbao from $this->table_record where type = 0 and param4 <> 44  and  param1=90000001 and time > '$starttime' and time < '$endtime' and id1=$recharge->pid";
                    $recharge -> unrecharge_yuanbao = $this->db -> query($sql) -> result_object() -> unrecharge_yuanbao;

                    $recharge -> servername = $server->name;
                    $flag++;

                    $recharge -> profession = $this -> _profession[$recharge->profession];

                    $recharge -> shengyu_yuanbao = ( $recharge -> unrecharge_yuanbao + $recharge -> recharge_yuanbao) - $recharge -> used_yuanbao;

                    $list[] = $recharge;
                }

            }

            for($i=0;$i<count($list) ; $i++){
                if($i+1 < count($list)){
                    if($list[$i]->recharge_yuanbao < $list[$i+1]->recharge_yuanbao){
                        $temp = $list[$i+1];
                        $list[$i+1] = $list[$i];
                        $list[$i] = $temp;
                    }
                }
            }

            $end = count($list) > 50 ? 50 : count($list);
            $list = array_slice($list,0,$end);

        }
        return $list;
    }

    public function getCondition($condition){}


    public function num_rows($condition){
        $servers = $condition->servers;
        $starttime = $condition -> starttime.' 00:00:00';
        $endtime = $condition -> endtime .' 23:59:59';
        $nums = 0;
        if(count($servers)>0){
            foreach($servers as $server){
                $this->dbConnect($server,$server->dynamic_dbname);
                $sql = "select a.id1 as pid from $this->table_record a  where a.type=0 and a.param1 = 90000001 and a.time > '$starttime' and a.time < '$endtime'  group by a.id1";
                $templist = $this -> db -> query($sql) -> result_objects();
                $nums+=count($templist);
            }
        }
        return $nums;
    }

}
