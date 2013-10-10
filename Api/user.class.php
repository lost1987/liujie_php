<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-10-8
 * Time: 下午4:37
 * To change this template use File | Settings | File Templates.
 */
class User extends Baseapi
{

    function User(){
         parent::__construct();
        $this -> tableInviter = 'ht_invite';
        $this -> tableUser = 'fr_user';
    }

     public function invitefriend(){

         $inviter = $this->input -> get('inviter');
         $invitedOpenids = explode(',',$this -> input -> get('invitedOpenids'));

         //判断发起邀请的人是否有创建角色
         $role = $this->db->select('name,id')->from($this->tableUser) -> where("account_name='$inviter'") -> get() -> result_object();
         if(empty($role->name)){
             echo 0;//邀请失败
             exit;
         }

         //发起邀请人的pid
         $pid = $role->id;

         $data = array();
         foreach($invitedOpenids as $inviterOpenid){
             $data[] = array('pid' => $pid,'openid'=>$inviterOpenid);
         }

         if($this -> db -> insert_multi($data,$this->tableInviter)){
              echo 1;
              exit;
         }

         echo 0;

     }

}
