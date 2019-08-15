<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\Orm;

use \RS\Config\Cms,
    \RS\Orm\Type;

class AccessModuleRight extends \RS\Orm\AbstractObject
{
    const
        /**
        * Величина, обозначающая максимальные права к модулю
        */
        MAX_ACCESS_RIGHTS = 255,
        FULL_MODULE_ACCESS = 'all';
    
    protected static
        $table = 'access_module_right';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'group_alias' => new Type\Varchar(array(
                'description' => t('ID группы'),
                'maxLength' => 50,
            )),
            'module' => new Type\Varchar(array(
                'description' => t('Идентификатор модуля'),
                'maxLength' => 50,
            )),
            'right' => new Type\Varchar(array(
                'description' => t('Идентификатор права'),
                'maxLength' => 150,
            )),
            'access' => new Type\Enum(array(Cms::ACCESS_ALLOW, Cms::ACCESS_DISALLOW), array(
                'description' => t('Уровень доступа'),
                'allowEmpty' => false,
                'default' => Cms::ACCESS_ALLOW,
            )),
        ));
        
        $this->addIndex(array('site_id', 'group_alias', 'module', 'right'), self::INDEX_UNIQUE);
    }
}
