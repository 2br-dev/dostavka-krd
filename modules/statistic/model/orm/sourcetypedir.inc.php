<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\Orm;
use \RS\Orm\Type;

/**
 * Класс категория типа источника от которого пришел пользователь
 * @package Statistic\Model\Orm
 */
class SourceTypeDir extends \RS\Orm\OrmObject
{
    protected static
        $table = 'statistic_user_source_type_dir';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'description' => t('Название источника'),
            )),
        ));
    }
}