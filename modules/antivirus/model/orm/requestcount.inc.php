<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model\Orm;
use \RS\Orm\Type;

class RequestCount extends \RS\Orm\OrmObject
{

    protected static
        $table = 'antivirus_request_count';
        
    function _init()
    {
        parent::_init()->append(array(
            'ip' => new Type\Varchar(array(
                'maxLength' => 100,
                'description' => t('IP адрес'),
            )),
            'last_time' => new Type\Bigint(array(
                'description' => t('Дата последнего запроса в милисекундах'),
            )),
            'count' => new Type\Integer(array(
                'description' => t('Количество запросов'),
                'allowEmpty' => false,
            )),
            'malicious_count' => new Type\Integer(array(
                'description' => t('Количество вредоносных запросов'),
                'allowEmpty' => false,
            )),
        ));
        
        $this->addIndex(array('ip'), self::INDEX_UNIQUE);
    }


}