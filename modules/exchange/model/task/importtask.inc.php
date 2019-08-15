<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Task;
use \Exchange\Model\Log;

/**
* Объект этого класса хранится в сессии, соотвественно все свойства объекта доступны 
* не только до окончания выполнения скрипта, но и в течении всей сессии
*/

class ImportTask extends \Exchange\Model\Task\AbstractTask
{
    protected $filename;  
    protected $offset = 0;
    
    public function __construct($filename)
    {
        $this->filename = $filename;
    }
    
    public function exec($max_exec_time = 0)
    {
        $api = \Exchange\Model\Api::getInstance();
        $readed_nodes = $api->catalogImport($this->filename, $this->offset, $max_exec_time);
        
        if($readed_nodes === true){ 
            return true;
        }
        else{
            $this->offset = $readed_nodes + 1;
        }
    }
    
    public function getOffset()
    {
        return $this->offset;
    }
}