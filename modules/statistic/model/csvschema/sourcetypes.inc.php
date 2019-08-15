<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\CsvSchema;
use \RS\Csv\Preset;

/**
* Схема экспорта/импорта брендов в CSV
*/
class SourceTypes extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Statistic\Model\Orm\SourceType(),
            'excludeFields' => array(
                'id', 'site_id', 'params', 'parent_id'
            ),
            //'savedRequest' => \Statistic\Model\SourceTypesApi::getSavedRequest('Statistic\Controller\Admin\SourceTypesCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
            'multisite' => true,
            'searchFields' => array('title', 'parent_id')
        )), array(
            new Preset\LinkedTable(array(
                'ormObject' => new \Statistic\Model\Orm\SourceTypeDir(),
                'fields' => array('title'),
                'titles' => array('title' => t('Категория')),
                'idField' => 'id',
                'multisite' => true,
                'linkForeignField' => 'parent_id',
                'linkPresetId' => 0,
                'linkDefaultValue' => 0
            )),
            new Preset\SerializedArray(array(
                'linkPresetId' => 0,
                'linkForeignField' => 'params',
                'title' => t('Дополнительные параметры запроса')
            )),
        ));
    }
}