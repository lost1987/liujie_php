<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-4-3
 * Time: 下午2:05
 * To change this template use File | Settings | File Templates.
 * 邮件发送
 */
class MailService extends ServerDBChooser
{
     function MailService(){
           $this->table_user = $this->prefix_1.'user';
           $this->table_mail = $this->prefix_3.'mail';
           $this->table_mailDetail = $this->prefix_1.'postmail';
           $this -> table_item = $this->prefix_1.'item';
           $this -> table_syslog = 'ljzm_syslog';
           $this -> table_mail_record = 'ljzm_mail_records';
           $this -> db_static = 'MMO2D_StaticLJZM';
     }

     public function lists($page,$condition){
            $servers = $condition->servers;
            $list = array();
            $flag = 0;
            if(count($servers) > 0){
                foreach($servers as $server){
                    $db = new Mssql();//连接分发数据库
                    $db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);
                    $db -> select_db('MMO2D_admin');
                    $sql =  "select *,CONVERT(varchar(20), donetime, 120) as dtime from $this->table_syslog where server_id=$server->id and (type=2 or type=10) order by donetime desc";
                    $loglist = $db->query($sql)->result_objects();
                    $db -> close();
                    unset($db);

                    //获取物品列表
                    $this->dbConnect($server,$this->db_static);
                    $sql = "select id,name from $this->table_item";
                    $items = $this->db->query($sql)->result_objects();
                    $this->dbClose();

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
                 $sql = "select count(id) as num from $this->table_syslog where server_id=$server->id and type=2 ";
                 $num = $db -> query($sql) -> result_object() -> num;
                 $nums += $num;

             }
             $db-> close();
         }
         return $nums;
     }


     public function getCondition($condition){}

     public function sendMailsWithServers($mail){
         //通过随机验证码关联操作人
        require BASEPATH.'/Common/log.php';
        $servers = $mail -> servers;
        $item_id = empty($mail->item_id) ? 0 : $mail->item_id;
        $item_num = empty($mail->item_num) ? 0 : $mail->item_num;
        if(!empty($servers)){
            foreach($servers as $server){
                $code = time();
                $this -> dbConnect($server,$this->db_static);
                //验证当前附件ID的正确性
                $items = $this->db->query("select id,name from $this->table_item")->result_objects();
                $item_arr = array();
                foreach($items as $item){
                    $item_arr[] =  $item -> id;
                }

                if(!in_array($item_id,$item_arr) && $item_id != 0){
                    return 1;//附件ID错误
                }

                $this -> db -> select_db($server->dynamic_dbname);
                //查询此服所有玩家pid
                $sql = "select id,name from $this->table_user";
                $players = $this->db->query($sql)->result_objects();
                $pernum = 100;//每次插入100条
                $cur = 1;//游标
                $total = count($players);
                $sql = "insert into $this->table_mail (pid,itemid,itemnum,theme,contents,code) ";

                foreach($players as $player){
                        if($cur%$pernum == 0){
                            $this->db->query($sql);
                            $sql = "insert into $this->table_mail (pid,itemid,itemnum,theme,contents,code) ";
                        }else if($cur%$pernum == 1){
                            $sql .=  " select $player->id,$item_id,$item_num,'$mail->title','$mail->context',$code ";
                        }
                        else{
                            $sql .= " union all select $player->id,$item_id,$item_num,'$mail->title','$mail->context',$code ";
                        }

                        if($total == $cur && $cur%$pernum !=0 ){
                            $this->db->query($sql);
                        }

                        $cur++;
                }
                $this->dbClose();

                if(count($players) > 0){
                    $log = new stdClass();
                    $log -> aid = $mail -> admin -> id;
                    $log -> admin = $mail -> admin -> admin;
                    $log -> flagname = $mail -> admin -> flagname;
                    $log -> type = 10;
                    $log -> typename = $log_action_type[10];
                    $log -> donetime = date('Y-m-d H:i:s');
                    $log -> server_id = $server->id;
                    $log -> server_name = $server->name;
                    $log -> refer_id = $code;
                    $log -> refer_name = 'code';
                    $log -> item_id = $item_id;
                    $log -> item_num = $item_num;
                    $log -> content = $mail->context;
                    $log -> title = $mail->title;

                    $slog = new Syslog();
                    $slog -> setlog($log) -> save() -> saveMailPlayers($players);
                }
            }
            return TRUE;
        }
        return FALSE;
     }

    public function sendMailsWithPlayers($mail){
        //通过随机验证码关联操作人
        require BASEPATH.'/Common/log.php';
        $players = $mail -> players;
        $servers = $mail -> servers;
        $item_id = empty($mail->item_id) ? 0 : $mail->item_id;
        $item_num = empty($mail->item_num) ? 0 : $mail->item_num;

        //分析players的server 并吧它按server分组
        if(!empty($servers)){
            foreach($servers as $server){
                $code = time();//每个服务器一个时间标识
                $this -> dbConnect($server,$this->db_static);
                //验证当前附件ID的正确性
                $items = $this->db->query("select id from $this->table_item")->result_objects();
                $item_arr = array();
                foreach($items as $item){
                    $item_arr[] =  $item -> id;
                }

                if(!in_array($item_id,$item_arr) && $item_id != 0){
                    return 1;//附件ID错误
                }

                //取当前server 被选中的player
                $plist = array();
                foreach($players as $player){
                    if($player->server->id == $server->id){
                        $plist[] = $player;
                    }
                }

                if(count($plist) > 0){
                    $this ->db->select_db($server->dynamic_dbname);
                    $sql = "insert into $this->table_mail (pid,itemid,itemnum,theme,contents,code) ";
                    foreach($plist as $player){
                        $sql .= " select $player->id,$item_id,$item_num,'$mail->title','$mail->context',$code union all ";
                    }
                    $sql = substr($sql,0,strlen($sql) - 10);
                    $this->db->query($sql);
                    $this->dbClose();
                }

                if(count($plist) > 0){
                    $log = new stdClass();
                    $log -> aid = $mail -> admin -> id;
                    $log -> admin = $mail -> admin -> admin;
                    $log -> flagname = $mail -> admin -> flagname;
                    $log -> type = 2;
                    $log -> typename = $log_action_type[2];
                    $log -> donetime = date('Y-m-d H:i:s');
                    $log -> server_id = $server->id;
                    $log -> server_name = $server->name;
                    $log -> refer_id = $code;
                    $log -> refer_name = 'code';
                    $log -> item_id = $item_id;
                    $log -> item_num = $item_num;
                    $log -> content = $mail->context;
                    $log -> title = $mail->title;

                    $slog = new Syslog();
                    $slog -> setlog($log) -> save() -> saveMailPlayers($plist);
                }

            }
            return 0;
        }
        return -1;
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

    public function mailPlayers($server,$lid){
        $list= array();
        if(!empty($server)){
            $db = new Mssql();//连接分发数据库
            $db -> connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PWD);
            $db -> select_db('MMO2D_admin');

            $list = $db -> query("select playername from $this->table_mail_record where lid=$lid") -> result_objects();
            error_log("select playername from $this->table_mail_record where lid=$lid");
            $db -> close();
        }
        return $list;
    }

}
