<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Statistic\Config;

use RS\Module\AbstractModel\TreeList\AbstractTreeListIterator;
use RS\Orm\ConfigObject;
use RS\Orm\Type;

class File extends ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            'consider_orders_status' => new Type\ArrayList(array(
                'runtime' => false,
                'description' => t('Учитывать в отчетах заказы в следующих статусах (удерживая CTRL можно выбрать несколько)'),
                'tree' => array(array('\Shop\Model\UserStatusApi', 'staticTreeList'), 0, array('0' => t('- все статусы -'))),
                'attr' => array(array(
                    AbstractTreeListIterator::ATTRIBUTE_MULTIPLE => true,
                )),
            ))
        ));
    }
}
