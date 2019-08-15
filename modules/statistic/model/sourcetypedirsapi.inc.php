<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model;

use RS\Module\AbstractModel\EntityList;

/**
 * API функции для работы с группами типов источников
 */
class SourceTypeDirsApi extends EntityList
{
    public $uniq;

    function __construct()
    {
        parent::__construct(new Orm\SourceTypeDir(), array(
            'name_field' => 'title',
            'id_field' => 'id',
            'sort_field' => 'title',
            'multisite' => true,
            'defaultOrder' => 'title'
        ));
    }
}
