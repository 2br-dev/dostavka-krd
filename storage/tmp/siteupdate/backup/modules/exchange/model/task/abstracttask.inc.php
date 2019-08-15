<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Task;
use \Exchange\Model\Log;


abstract class AbstractTask{
    protected $startTime;
    protected $maxExecTime;
    
    final public function _exec($max_exec_time = 0)
    {
        $this->startTime    = time();
        $this->maxExecTime  = $max_exec_time;
        
        Log::w("{$this} started...");
        Log::indentInc();

        $ret = $this->exec($max_exec_time);

        Log::indentDec();
        
        if($ret === true){
            Log::w($this." is completed.");
        }
        else{
            Log::w($this." is breaked in progress.");
        }
       
        return $ret;
    }

    abstract public function exec($max_exec_time = 0);
    
    protected function isExceed()
    {
        if($this->maxExecTime == 0) return false;
        return time() >= $this->startTime + $this->maxExecTime;
    }

    public function __toString()
    {
       $vars = get_object_vars ( $this );
       $str = get_class($this).' ['.http_build_query($vars, null, ', ').']';
       
       return $str;
    }
}