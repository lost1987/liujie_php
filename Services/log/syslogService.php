<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-5-2
 * Time: 下午4:04
 * To change this template use File | Settings | File Templates.
 */
class SyslogService extends Service
{

    function SyslogService(){
        parent::__construct();
        $this->table_player = 'fr_user';
        $this ->  db -> select_db(DB_NAME);
    }


    /**
     *
     * 修改日志的状态[字段state]
     *
     * @param logIDs 日志ID字符串 逗号隔开
     * @param state  日志要修改的状态
     * @Param refername  这里 此字段作为 日志的操作人
     *
     */
    public function updateLogState($log,$state,$refername){
          $result = 0;
           try{
               $logid = $log->id;
               $optime = time();
               $this->db->trans_begin();
               $sql = "update ljzm_syslog set state = $state,refer_name='$refername',optime=$optime where id = $logid";
               $res = $this -> db -> query($sql) -> queryState;
               if(!$res) throw new Exception('log-pay error!');
               if($state == 2 && $res){//批准
                   //如果批准的话 直接调用支付接口
                   $sid = $log -> server_id;
                   $sql = "select bid,ip,port,dbuser,dbpwd,dynamic_dbname from ljzm_servers where id = $sid";
                   $server = $this -> db -> query($sql) -> result_object();

                   $db = new DB();
                   $db -> connect($server->ip.':'.$server->port,$server->dbuser,$server->dbpwd,TRUE);
                   $db -> select_db($server->dynamic_dbname);

                   $sql = "select account_name from $this->table_player where name = '$log->playername'";
                   $uname = $db -> query($sql) -> result_object() -> account_name;

                   if(empty($uname))return 0;
                   $db -> close();
                   unset($db);

                   $utime = time();
                   $aid = $server -> bid;//运营商ID
                   $goldmoney = $log -> itemnum;
                   $eventid = 'REWARD'.date('YmdHis').make_rand_str(5);
                   $realServerId = $this -> getRealSid($aid,$sid);
                   $ukey = md5($uname.$utime.$goldmoney.$aid.$realServerId.$eventid.DEF_PLATFORM_KEY);
                   $payapi =  new Payapi(PAYHOST,$uname,$ukey,$utime,$aid,$realServerId,$goldmoney,$eventid);
                   $result = $payapi -> pay();

                   if($result != 1)throw new Exception('pay error!');

                   $this-> db ->commit();
                   $this -> db -> close();
                   return 1;
               }else if($res){//拒绝
                   $this->db->commit();
                   $this -> db -> close();
                   return 1;
               }

               return intval($result);
           }catch (Exception $e){
                $this->db->rollback();
                $this->db->close();
                return intval($result);
           }
    }

    //得到真实的服务器ID
    private function getRealSid($bid,$sid){
        /**
         * sid的填写方式
         * 公式  服务器唯一标识 = 服务器ID + 运营商ID*10000;
         */
        if($sid > 10000*$bid){
            $serverid = $sid % (10000*$bid);
        }else{
            $serverid = (10000*$bid - $sid) * -1;//测试服务器 一般为负数 或者 0
        }
        return $serverid;
    }

}
