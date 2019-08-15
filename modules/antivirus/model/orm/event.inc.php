<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model\Orm;
use \RS\Orm\Type;

class Event extends \RS\Orm\OrmObject
{
    const COMPONENT_INTEGRITY = 'integrity';
    const COMPONENT_ANTIVIRUS = 'antivirus';
    const COMPONENT_PROACTIVE = 'proactive';

    const TYPE_INFO     = 'info';
    const TYPE_PROBLEM  = 'problem';
    const TYPE_FIXED    = 'fixed';

    protected static
        $table = 'antivirus_events';
        
    function _init()
    {
        parent::_init()->append(array(
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата события'),
            )),
            'component' => new Type\Varchar(array(
                'description' => t('Компонент'),
                'attr' => array(array('size' => 1)),
                'list' => array(array(__CLASS__, 'getComponentList')),
                'meVisible' => false
            )),
            'type' => new Type\Varchar(array(
                'description' => t('Тип события'),
                'attr' => array(array('size' => 1)),
                'list' => array(array(__CLASS__, 'getTypeList')),
                'meVisible' => false
            )),
            'file' => new Type\Varchar(array(
                'maxLength' => 2048,
                'description' => t('Путь к файлу'),
            )),
            'details' => new Type\Mediumblob(array(
                'description' => t('Детали проблемы/уязвимости'),
            )),
            'viewed' => new Type\Integer(array(
                'description' => t('Флаг просмотра события администратором'),
                'maxLength' => 1,
                'allowEmpty' => false,
            )),

        ));
        
        //$this->addIndex(array('site_id', 'route_id'), self::INDEX_UNIQUE);
    }


    /**
     * Возвращает список идентификаторов компонентов
     * @return array
     */
    public static function getComponentList()
    {

        return array(
            self::COMPONENT_INTEGRITY => t('Проверка целостности'),
            self::COMPONENT_ANTIVIRUS => t('Антивирус'),
            self::COMPONENT_PROACTIVE => t('Проактивная защита'),
        );
    }

    /**
     * Возвращает список типов событий
     * @return array
     */
    public static function getTypeList()
    {

        return array(
            self::TYPE_INFO     => t('Информация'),
            self::TYPE_PROBLEM  => t('Проблема'),
            self::TYPE_FIXED    => t('Исправлено'),
        );
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