<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-4
 * Time: 上午10:37
 * To change this template use File | Settings | File Templates.
 * 登录
 */
class LoginService extends Service
{

    function __construct(){
        parent::__construct();
        $this -> db -> select_db('MMO2D_admin');
    }

    public function login($admin){
        $username = $admin -> username;
        $password = $admin -> password;

        $admin = $this -> db -> query("select id,passwd,permission,bid,flagname from ljzm_admin where admin='$username'")->result_object();
        $return = null;
        if($admin->passwd == md5($password.APPKEY)){

               $return_admin = new stdClass();
               $return_admin->admin= $username;
               $return_admin->id = $admin->id;
               $return_admin->permission = $admin->permission;
               $return_admin->bid = $admin->bid;
               $return_admin->flagname = $admin->flagname;

               //查询权限
               $sql = "select * from ljzm_permission where id = $return_admin->id";
               $permission = $this -> db -> query($sql) -> result_object();
               $return_admin->child_permissions = $permission;

               if($admin->bid == -1){
                   $return_buissnessers = $this->db->query("select id,name from ljzm_buissnesser where stat=1")->result_objects();
               }else{
                   $return_buissnessers = $this->db->query("select id,name from ljzm_buissnesser where id in ($admin->bid)  and stat=1")->result_objects();
               }


               $return[] = $return_admin;
               $return[] = $return_buissnessers;

        }
        return $return;
    }

}
