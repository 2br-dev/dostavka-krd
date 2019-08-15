<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module\AbstractModel\TreeList;

use RS\Db\Exception as DbException;
use RS\Exception as RSException;
use RS\Module\AbstractModel\TreeList;
use RS\Orm\Exception as OrmException;
use RS\Orm\Request as OrmRequest;

/**
 * Итератор древовидного списка с orm-узлами
 */
class TreeListOrmIterator extends AbstractTreeListIterator
{
    /** @var TreeList */
    protected $api;
    /** @var int */
    protected $parent_id;
    /** @var TreeListOrmPreLoader */
    protected $pre_loader;

    /**
     * TreeListIterator constructor.
     *
     * @param TreeList $api - api для выборки объектов
     * @param int $parent_id - id родительского узла
     */
    public function __construct(TreeList $api, $parent_id = 0)
    {
        $this->setApi($api);
        $this->setParentId($parent_id);
    }

    /**
     * Возвращает список дочерних элементов
     *
     * @return TreeListOrmNode[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    protected function getSelfItems()
    {
        if ($this->getPreLoader() && $this->getPreLoader()->hasNodesByParentId($this->getParentId())) {
            $items = $this->getPreLoader()->getNodesByParentId($this->getParentId());
        } else {
            $items = array();
            $api = $this->getApi();

            $list = $this->getClonedQueryObj()->where(array($api->getParentField() => $this->getParentId()))->objects(null, $api->getIdField());

            $childs_counts = array();
            if ($list) {
                $childs_counts = OrmRequest::make()
                    ->select("{$api->getParentField()}, count({$api->getIdField()}) count")
                    ->from($api->getElement())
                    ->whereIn($api->getParentField(), array_keys($list))
                    ->groupby($api->getParentField())
                    ->exec()->fetchSelected($api->getParentField(), 'count');
            }

            foreach ($list as $id => $item) {
                $node = new TreeListOrmNode($item, $api);
                $childs_count = (isset($childs_counts[$id])) ? $childs_counts[$id] : 0;
                $node->setChildsCount($childs_count);
                if ($childs_count == 0) {
                    $node->setChilds(new TreeListEmptyIterator());
                }
                $items[] = $node;
            }
        }
        return $items;
    }

    /**
     * Возвращает копию api для сохранения установленных фильтров
     *
     * @return OrmRequest
     */
    protected function getClonedQueryObj()
    {
        return clone $this->getApi()->queryObj();
    }

    /**
     * Возвращает объект предворительной загрузки
     *
     * @return TreeListOrmPreLoader|null
     */
    public function getPreLoader()
    {
        return $this->pre_loader;
    }

    /**
     * Устанавливает объект предворительной загрузки
     *
     * @param TreeListOrmPreLoader $pre_loader
     * @return void
     */
    public function setPreLoader(TreeListOrmPreLoader $pre_loader)
    {
        $this->pre_loader = $pre_loader;
    }

    /**
     * Возвращает объект api
     *
     * @return TreeList
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Устанавливает объект api
     *
     * @param TreeList $api - объект api
     * @return void
     */
    protected function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * Возвращает id родительского узла
     *
     * @return int
     */
    protected function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Устанавливает id родительского узла
     *
     * @param int $parent_id - id родительского узла
     * @return void
     */
    protected function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;
    }
}
