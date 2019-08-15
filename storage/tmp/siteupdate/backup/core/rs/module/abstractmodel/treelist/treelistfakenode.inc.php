<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module\AbstractModel\TreeList;

/**
 * Несуществующий узел древовидного списка
 */
class TreeListFakeNode extends AbstractTreeListNode
{
    /**
     * Возвращает итератор со списком дочерних элементов
     *
     * @return TreeListEmptyIterator
     */
    public function getSelfChilds()
    {
        return new TreeListEmptyIterator();
    }
}
