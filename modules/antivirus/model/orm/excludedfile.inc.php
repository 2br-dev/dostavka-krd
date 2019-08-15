<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model\Orm;
use \RS\Orm\Type;

class ExcludedFile extends \RS\Orm\OrmObject
{
    const COMPONENT_INTEGRITY = 'integrity';
    const COMPONENT_ANTIVIRUS = 'antivirus';
    
    protected static
        $table = 'antivirus_excluded_files';
        
    function _init()
    {
        parent::_init()->append(array(
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата добавления'),
            )),
            'component' => new Type\Varchar(array(
                'description' => t('Компонент'),
                'attr' => array(array('size' => 1)),
                'list' => array(array('Antivirus\Model\Orm\ExcludedFile', 'getComponentList')),
                'meVisible' => false
            )),
            'file' => new Type\Varchar(array(
                'maxLength' => 2048,
                'description' => t('Путь к файлу'),
                'hint' => t('Путь должен быть задан относительно корня сайта, например, modules/export/config/file.inc.php')
            )),
        ));
        
        $this->addIndex(array('file'), self::INDEX_KEY);
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
        );
    }    

}