<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module\AbstractModel\TreeList;

/**
 * Несуществующий итератор древовидного списка, заполняется вручную
 */
class TreeListFakeIterator extends AbstractTreeListIterator
{
    /** @var AbstractTreeListNode[] */
    protected $self_items = array();

    public function addItem(AbstractTreeListNode $node)
    {
        $this->self_items[] = $node;
    }

    /**
     * Возвращает список дочерних элементов
     *
     * @return AbstractTreeListNode[]
     */
    protected function getSelfItems()
    {
        return $this->self_items;
    }
}
