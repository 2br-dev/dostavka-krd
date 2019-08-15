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
 * Класс связь с источником от которого пришел пользователь
 * @package Statistic\Model\Orm
 */
class Source extends \RS\Orm\OrmObject
{
    protected static
        $table = 'statistic_source';
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'partner_id' => new Type\Integer(array(
                'description' => t('Партнёрский сайт'),
                'index' => true
            )),
            'source_type' => new Type\Integer(array(
                'description' => t('Идентификатор типа источника на сайте'),
            )),
            'referer_site' => new Type\Varchar(array(
                'description' => t('Сайт источник из поля реферер'),
            )),
            'referer_source' => new Type\Text(array(
                'description' => t('Полный источник из поля реферер'),
            )),
            'landing_page' => new Type\Varchar(array(
                'description' => t('Страница первого посещения'),
            )),
            //Параметры UTM меток
            'utm_source' => new Type\Varchar(array(
                'description' => t('Рекламная система UTM_SOURCE'),
            )),
            'utm_medium' => new Type\Varchar(array(
                'description' => t('Тип трафика UTM_MEDIUM'),
            )),
            'utm_campaign' => new Type\Varchar(array(
                'description' => t('Рекламная кампания UTM_COMPAING'),
            )),
            'utm_term' => new Type\Varchar(array(
                'description' => t('Ключевое слово UTM_TERM'),
            )),
            'utm_content' => new Type\Varchar(array(
                'description' => t('Различия UTM_CONTENT'),
            )),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата события'),
                'index' => true
            )),
        ));
    }

    /**
     * Действия перед созданием источника
     *
     * @param string $save_flag - insert или update
     * @return false|null|void
     */
    function beforeWrite($save_flag)
    {
        if ($save_flag == self::INSERT_FLAG){
            $source_type_api = new \Statistic\Model\SourceTypesApi();
            $source_type_api->setSourceTypeToSource($this);
        }
    }

    /**
     * Возвращает объект типа источника
     *
     * @return SourceType
     */
    function getType()
    {
        $source_type = new SourceType($this['source_type']);
        if (!$source_type['id']){ //Если тип не назначен
            $source_type['title'] = t('Не определён');
        }
        return $source_type;
    }


}