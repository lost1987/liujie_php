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
        $this ->  db -> select_db('MMO2D_admin');
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
           $logid = $log->id;
           $optime = time();
           $sql = "update ljzm_syslog set state = $state,refer_name='$refername',optime=$optime where id = $logid";
           $res = $this -> db -> query($sql) -> queryState;
           if($state == 2 && $res){
                //如果批准的话 直接调用支付接口
                $sid = $log -> server_id;
                $sql = "select bid,ip,port,dbuser,dbpwd,dynamic_dbname from ljzm_servers where id = $sid";
                $server = $this -> db -> query($sql) -> result_object();


                $db = new Mssql();
                $db -> connect($server->ip.':'.$server->port,$server->dbuser,$server->dbpwd,TRUE);
                $db -> select_db($server->dynamic_dbname);

                $sql = "select account_name from $this->table_player where name = '$log->playername'";
                $player = $db -> query($sql) -> result_object();
                $db -> close();
                unset($db);

                $uname = $player -> account_name;
                $utime = time();
                $aid = $server -> bid;//运营商ID
                $goldmoney = $log -> itemnum;
                $eventid = 'REWARD'.date('YmdHis').make_rand_str(5);
                $realServerId = $this -> getRealSid($aid,$sid);
                $ukey = md5($uname.$utime.$goldmoney.$aid.$realServerId.$eventid.DEF_PLATFORM_KEY);
                $payapi =  new Payapi(PAYHOST,$uname,$ukey,$utime,$aid,$realServerId,$goldmoney,$eventid);
                $result = $payapi -> pay();

                if($result == 1){
                    return TRUE;
                }else{
                    $sql = "update ljzm_syslog set state = 0,refer_name='',optime=null where id = $logid";
                    $this -> db -> query($sql);
                }

                $this -> db -> close();
                return FALSE;
           }else if($res){
               return TRUE;
           }

        return FALSE;
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
