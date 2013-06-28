<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-3-6
 * Time: 上午11:35
 * To change this template use File | Settings | File Templates.
 */
class Mssql
{

        public $link;

        public $queryState;

        function Mssql(){

        }

        public function connect($DB_HOST,$DB_USER,$DBPWD,$NEWLINK=FALSE){
            $this->link = mssql_connect($DB_HOST,$DB_USER,$DBPWD,$NEWLINK);
        }

        public function charset($charset){
        }

        public function select_db($dbname){
             mssql_select_db($dbname,$this->link);
             return $this;
        }

        public function query($sql){
            if(!empty($this->link))
            $this -> queryState = mssql_query($sql,$this->link);
            return $this;
        }


        public function result_objects(){
            if(!empty($this->queryState)){
                $list = array();
                while($row = mssql_fetch_object($this->queryState)){
                        $list[] = $row;
                }
                return $list;
            }
            return FALSE;
        }

       public function result_object(){
           if(!empty($this->queryState)){
               if($row = mssql_fetch_object($this->queryState)){
                   return  $row;
               }
           }
           return FALSE;
       }

       public function insert_id($table){
           $sql = "select IDENT_CURRENT('$table')  as  insert_id";
           return $this->query($sql)->result_object()->insert_id;
       }

       public function close(){
           mssql_close($this->link);
       }
}
