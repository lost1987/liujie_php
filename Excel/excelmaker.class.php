<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lost
 * Date: 13-6-26
 * Time: 上午11:47
 * To change this template use File | Settings | File Templates.
 */
class ExcelMaker extends Input
{

    private static $instance;
    private $params;
    private $results;
    private $cellNamesChar = array(
        'A','B','C','D','E','F','G','H','I','J',
        'K','L','M','N','O','P','Q','R','S','T',
        'U','V','W','X','Y','Z'
    );

    private function ExcelMaker(){
        parent::__construct();
    }


    public static function getInstance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function register_auto_load(){
         spl_autoload_register(array(__CLASS__,'_autoload'));
    }

    public static function _autoload($className){
        $classname = lcfirst($className);
        $folders = $GLOBALS['autoload_folders'];
        foreach($folders as $foldername){
            if($foldername == 'Services')
                $classfile = $classname.'.php';
            else
                $classfile = $classname.'.class.php';

            $path = BASEPATH.$foldername.DIRECTORY_SEPARATOR.$classfile;
            if(file_exists($path)){
                require_once $path;
                break;
            }
        }
    }

    private function fetchData(){
        $this->params = array();
        $excel_module = $GLOBALS['excel_keys'][$this->post('module_key')];
        list($this->params['service'],$this->params['method'],$this->params['type']) = explode('|',$excel_module);
        $this->params['start_time'] = $this->post('start_time');
        $this->params['end_time'] = $this->post('end_time');
        $this->params['server_id'] = $this->post('server_id');
        $this->params['columnNames'] = explode('|',$this->post('columnNames'));
        $this->params['columnKeys'] = explode('|',$this->post('columnKeys'));
        $this->params['excel_name'] = $this->post('excel_name');
        return $this;
    }

    private function callMethod(){
        switch($this->params['type']){
            case 0:
                $condition = new stdClass();
                $condition -> starttime = $this->params['start_time'];
                $condition -> endtime = $this->params['end_time'];
                $condition -> server_ids = $this->params['server_id'];

                $page = new stdClass();
                $page -> start = 0;
                $page -> limit = 9999;

                $service = new $this->params['service']();
                $this -> results = call_user_func(array($service,$this->params['method']),$page,$condition);

                break;
            case 1:


                break;
        }
    }

    private function onReadyExcel(){

        $excel = new PHPExcel();
        $excel -> getProperties() -> setCreator("yilong");
        $excel->getProperties()->setLastModifiedBy("yilong");
        $excel->getProperties()->setTitle("Office 2007 XLS Test Document");
        $excel->getProperties()->setSubject("Office 2007 XLS Test Document");
        $excel->getProperties()->setDescription("Test document for Office 2007 XLS, generated using PHP classes.");
        $excel->getProperties()->setKeywords("office 2007 openxml php");
        $excel->getProperties()->setCategory($this->params['excel_name']);

        $excel->setActiveSheetIndex(0);
        $activeSheet  =  $excel->getActiveSheet();
        $activeSheet->setTitle($this->params['excel_name']);

        $columns_names = $this->params['columnNames'];
        $columns_keys = $this->params['columnKeys'];
        $columns_num = count($columns_names);

        //设置列名
        for($i=0;$i<$columns_num;$i++){
            $activeSheet -> setCellValue($this->cellNamesChar[$i].'1',$columns_names[$i]);
            $activeSheet -> getStyle($this->cellNamesChar[$i].'1') -> getFill() -> setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $activeSheet -> getStyle($this->cellNamesChar[$i].'1') -> getFill() -> getStartColor() -> setARGB(PHPExcel_Style_Color::COLOR_GREEN);
            $activeSheet -> getColumnDimension($this->cellNamesChar[$i]) -> setWidth(30);
        }

        //设置值
        $rows = count($this->results);
        if($rows > 0){
            for($i=0;$i<$rows;$i++){
                $index = $i+2;
                $object = $this->results[$i];

                for($k=0;$k<$columns_num;$k++){
                    if(!isset($object->{$columns_keys[$k]})) //如果没有属性的话 跳过 进行下个属性的计算
                    {
                        break 1;
                    }
                    $activeSheet -> setCellValue($this->cellNamesChar[$k].$index,$object->{$columns_keys[$k]});
                }

            }
        }

        // 设置页方向和规模
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $excel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $excel->setActiveSheetIndex(0);
        $date = date('Y-m-d H:i:s');

        /*2007*/
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$this->params['excel_name'].'_'.$date.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save('php://output');

        /**office 2003
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$this->params['excel_name'].'_'.$date.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $objWriter->save('php://output');
         **/
        exit;
    }

    public function output(){
        $this->fetchData();
        $this->callMethod();
        $this->onReadyExcel();
    }

}
