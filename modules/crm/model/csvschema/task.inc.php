<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Model\CsvSchema;

use Crm\Model\CsvPreset\CustomFields;
use Crm\Model\CsvPreset\Links;
use Crm\Model\Orm\Status;
use RS\Config\Loader;
use \RS\Csv\Preset;

/**
 * Схема экспорта/импорта задач в CSV
 */
class Task extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        $user_fields_manager = Loader::byModule($this)->getTaskUserFieldsManager();

        parent::__construct(new Preset\Base(array(
            'ormObject' => new \Crm\Model\Orm\Task(),
            'excludeFields' => array(
                'id', 'change_status_group', 'status_id'
            ),
            'savedRequest' => \Crm\Model\TaskApi::getSavedRequest('Crm\Controller\Admin\TaskCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
            'searchFields' => array('task_num')
        )), array(
            new Preset\LinkedTable(array(
                'ormObject' => new Status(),
                'fields' => array('title'),
                'titles' => array('title' => t('Статус')),
                'idField' => 'id',
                'linkForeignField' => 'status_id',
                'linkPresetId' => 0,
                'linkDefaultValue' => 0,
                'beforeRowImport' => function($self) {
                    $self->row['object_type_alias'] = 'crm-task';
                }
            )),
            new Links(array(
                'LinkIdField' => 'id',
                'LinkForeignField' => 'links',
                'LinkSourceType' => \Crm\Model\Orm\Task::getLinkSourceType(),
                'LinkPresetId' => 0,
                'FieldScope' => array(
                    'links-title' => 'export'
                )
            )),
            new CustomFields(array(
                'userFieldsManager' => $user_fields_manager,
                'linkIdField' => 'custom_fields',
                'linkPresetId' => 0
            ))
        ));
    }
}