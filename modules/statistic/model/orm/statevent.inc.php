<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\Orm;
use \RS\Orm\Type;

class StatEvent extends \RS\Orm\OrmObject
{
    protected static
        $table = 'statistic_events';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'dateof' => new Type\Date(array(
                'description' => t('Дата события'),
            )),
            'type' => new Type\Varchar(array(
                'description' => t('Тип события'),
                'attr' => array(array('size' => 1)),
                'list' => array(array('\Main\Model\StatisticEvent', 'getTypeList')),
            )),
            'details' => new Type\Text(array(
                'description' => t('Детали события'),
            )),
            'count' => new Type\Integer(array(
                'description' => t('Количество событий за данный день'),
                'default' => 1,
                'allowEmpty' => false,
            )),

        ));
        
        $this->addIndex(array('dateof'), self::INDEX_KEY);
        $this->addIndex(array('site_id', 'dateof', 'type'), self::INDEX_UNIQUE);
    }

    public function getDetails()
    {
        return @unserialize($this['details']);
    }


    public function setDetails($object)
    {
        $this['details'] = serialize($object);
    }


}