<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

use RS\Module\AbstractModel\EntityList;
use Shop\Model\Orm\DeliveryDir;

class DeliveryDirApi extends EntityList
{
    public $uniq;

    public function __construct()
    {
        parent::__construct(new DeliveryDir(), array(
            'multisite' => true,
            'defaultOrder' => 'sortn',
            'nameField' => 'title',
            'sortField' => 'sortn'
        ));
    }

    /**
     * Возвращает плоский список категорий
     *
     * @return array
     */
    public static function selectList()
    {
        $_this = new self();
        return array(0 => t('Без группы')) + $_this->getSelectList(0);
    }
}
