<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model;

/**
* Exchange Exception 
*/
class Exception extends \RS\Exception 
{
    const
        CODE_UNKNOWN_COMMAND = 1,
        CODE_AUTH_ERROR = 2,
        CODE_RIGHTS_ERROR = 3,
        CODE_NOT_AUTORIZED = 4,
        CODE_EXCHANGE_LOCKED = 5,
        CODE_NOT_INIT = 6;
    
    function __construct($message = '', $code = 0, Exception $previous = null, $extra_info = '')
    {
        parent::__construct($message, $code, $previous, $extra_info);
    }  
}
