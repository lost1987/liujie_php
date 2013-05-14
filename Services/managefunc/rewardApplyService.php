<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-4-3
 * Time: 下午2:05
 * To change this template use File | Settings | File Templates.
 * 奖励申请
 */
class RewardApplyService extends ServerDBChooser
{

    function RewardApplyService(){
        $this->table_user = $this->prefix_1.'user';
        $this -> table_item = $this->prefix_1.'item';
        $this -> table_syslog = 'ljzm_syslog';
        $this -> table_reward_record = 'ljzm_reward_records';

        $this -> db_static = 'MMO2D_StaticLJZM';

        require BASEPATH.'/Common/contentconfig.php';
        $this -> state = $state;
    }

    public function lists($page,$condition){
        $servers = $condition->servers;
        $state = $condition->state;
        $cond = $state == -1 ? '' : " and state = $state ";
        $list = array();
        $flag = 0;
        if(count($servers) > 0){
            foreach($servers as $server){
                $db = new Mssql();//连接分发数据库
                $db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);
                $db -> select_db('MMO2D_admin');
                $sql =  "select a.*,CONVERT(varchar(20), donetime, 120) as dtime,b.playername,b.playerid  from $this->table_syslog a left join $this->table_reward_record b on a.id = b.lid where server_id=$server->id and type=9 $cond order by donetime desc";
                $loglist = $db->query($sql)->result_objects();
                $db -> close();
                unset($db);

                //获取物品列表
            /*    $this->dbConnect($server,$this->db_static);
                $sql = "select id,name from $this->table_item";
                $items = $this->db->query($sql)->result_objects();
                $this->dbClose();*/
                $this->dbConnect($server);
                $items = Datacache::getStaticItems($this->db);

                foreach($loglist as $log){//查找物品
                    if($log->itemid != 0 && !empty($log->itemid)){
                        foreach($items as $item){
                            if($item->id == $log->itemid){
                                $log->itemname = $item->name;
                                break;
                            }
                        }
                    }else{
                        $log->itemname='';
                    }

                    $log -> opname = $log -> refer_name;
                    $log -> statename = $this->state[$log->state];

                    if($log->state != 0)$log->_enabled = FALSE;
                    else $log->_enabled = TRUE;

                    $log->optime = empty($log->optime) ? '' : date('Y-m-d H:i:s',$log->optime);

                    $list[] = $log;
                }
            }

            $return = array();
            foreach($list  as  $obj){
                if($flag >= $page->start && $flag <= $page -> limit){
                    $return[] = $obj;
                }
                $flag++;
            }
        }
        return $return;
    }

    public function num_rows($condition){
        $servers = $condition->servers;
        $nums = 0;
        if(!empty($servers)){
            $db = new Mssql();//连接分发数据库
            $db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);
            $db -> select_db('MMO2D_admin');
            foreach($servers as $server){
                $sql = "select count(id) as num from $this->table_syslog where server_id=$server->id and type=9 ";
                $num = $db -> query($sql) -> result_object() -> num;
                $nums += $num;

            }
            $db-> close();
        }
        return $nums;
    }


    public function getCondition($condition){}


    public function sendRewardsWithPlayer($reward){
        //通过随机验证码关联操作人
        require BASEPATH.'/Common/log.php';
        $playername = $reward -> playername;
        $server = $reward -> server;
        $item_num = empty($reward->item_num) ? 0 : $reward->item_num;

        //分析players的server 并吧它按server分组
        if(!empty($server)){
                $code = time();//每个服务器一个时间标识

                //验证当前的角色是否存在
                $this->dbConnect($server,$server->dynamic_dbname);
                $sql = "select id , name from $this->table_user where name = '$playername'";
                $res = $this->db ->query($sql)->result_object();

                if(!empty($res->id)){
                    $log = new stdClass();
                    $log -> aid = $reward -> admin -> id;
                    $log -> admin = $reward -> admin -> admin;
                    $log -> flagname = $reward -> admin -> flagname;
                    $log -> type = 9;
                    $log -> typename = $log_action_type[9];
                    $log -> donetime = date('Y-m-d H:i:s');
                    $log -> server_id = $server->id;
                    $log -> server_name = $server->name;
                    $log -> refer_id = 0;
                    $log -> refer_name = '';
                    $log -> item_id = 900000001;
                    $log -> item_num = $item_num;
                    $log -> content = '';
                    $log -> title = $reward->title;
                    $log -> state = 0;

                    $slog = new Syslog();
                    $slog -> setlog($log) -> save() -> saveRewardPlayers($res);
                    return 0;
                }

                return -1;
        }
        return 1;
    }

    private function getItemByID($id,$items){
        foreach($items as $item){
            if($item->id == $id){
                $returnObj = $item;
                break;
            }
        }

        if(isset($returnObj))
            return $returnObj -> name;


        return null;
    }

    public function rewardPlayers($server,$lid){
        $list= array();
        if(!empty($server)){
            $db = new Mssql();//连接分发数据库
            $db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);
            $db -> select_db('MMO2D_admin');

            $list = $db -> query("select playername from $this->table_reward_record where lid=$lid") -> result_objects();
            $db -> close();
        }
        return $list;
    }
}
