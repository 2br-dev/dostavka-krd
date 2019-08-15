<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module\AbstractModel\TreeList;

/**
 * Итератор древовидного списка без дочерних узлов
 */
class TreeListEmptyIterator extends AbstractTreeListIterator
{
    /**
     * Возвращает список дочерних элементов
     *
     * @return AbstractTreeListNode[]
     */
    protected function getSelfItems()
    {
        return array();
    }
}
