<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-4-17
 * Time: 下午3:41
 * To change this template use File | Settings | File Templates.
 */
class PartnerService extends ServerDBChooser
{

    function PartnerService(){
        $this -> table_partner = $this -> prefix_2.'huoban';
        $this -> table_dynamic_item = $this -> prefix_1 . 'dynamicitem';
        $this -> table_item = $this -> prefix_1.'item';
        $this -> table_huoban = $this -> prefix_2.'huobans';
        $this -> db_static = 'MMO2D_StaticLJZM';
    }

    public function detail($pid,$server){
        if(!empty($server)){
            $this->dbConnect($server,$server->dynamic_dbname);
            $sql = "select * from $this->table_partner where  pid = $pid";
            $huoban_list = $this->db->query($sql) -> result_objects();
            if(empty($huoban_list))return null;

            $this->db->select_db($this->db_static);
            foreach($huoban_list as &$huoban){
                $sql = "select name,color from $this->table_huoban where id = $huoban->hid";
                $huobans_static = $this -> db -> query($sql)->result_object();
                $huoban -> name = $huobans_static->name;
                $huoban -> color = $this->getColor($huobans_static->color);
            }

            $this->db->select_db($server->dynamic_dbname);
            foreach($huoban_list as &$huoban){
                $huoban_item_ids = $this->get_huoban_item_ids($huoban);
                if(!empty($huoban_item_ids)){
                    $sql = "select itemid,strength from $this->table_dynamic_item where id in ($huoban_item_ids)";
                    $ditems = $this->db->query($sql)->result_objects();
                    $dynamic_item_ids = $this->get_dynamic_item_ids($ditems);
                    if(!empty($dynamic_item_ids)){
                        $this->db->select_db($this->db_static);
                        $sql = "select name from $this->table_item where id in ($dynamic_item_ids)";
                        $items = $this->db->query($sql) -> result_objects();
                        $this->db->select_db($server->dynamic_dbname);
                    }
                    for($i = 0 ; $i < count($ditems) ; $i++){
                        $huoban -> {'zb'.($i+1)} = $items[$i]->name . ' +' . $ditems[$i]->strength;
                        $huoban -> {'zbcolor'.($i+1)} = Color::getColor(substr($ditems[$i]->itemid,-1,1));
                    }
                }
            }

            return $huoban_list;

        }
    }

    private function get_huoban_item_ids($huoban){
        $huoban_item_ids = '';
        for($i = 1 ; $i < 7 ; $i++){
            if($huoban -> {'zb'.$i} !=0 && !empty($huoban -> {'zb'.$i}) )
            $huoban_item_ids .= $huoban -> {'zb'.$i} . ',';
        }
        if(strlen($huoban_item_ids) > 0)
        $huoban_item_ids = substr($huoban_item_ids,0,strlen($huoban_item_ids)-1);
        return $huoban_item_ids;
    }

    private function get_dynamic_item_ids($ditems){
        $dynamic_item_ids = '';
        foreach($ditems as $item){
            $dynamic_item_ids .= $item -> itemid.',';
        }
        if(strlen($dynamic_item_ids) > 0)
        $dynamic_item_ids = substr($dynamic_item_ids,0,strlen($dynamic_item_ids)-1);
        return $dynamic_item_ids;
    }

    private function getColor($color){
        switch($color){
            case 1: $_color = 'green';break;
            case 2: $_color = 'blue';break;
            case 3: $_color = 'purple';break;
            case 4: $_color = 'orange';break;
        }
        return $_color;
    }

    public function getCondition($condition){}
}
