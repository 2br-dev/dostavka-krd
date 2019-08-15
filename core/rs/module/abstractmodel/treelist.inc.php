<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Module\AbstractModel;

use RS\Db\Exception as DbException;
use RS\Event\Exception as EventException;
use RS\Exception as RSException;
use RS\Orm\Exception as OrmException;
use RS\Html\Filter\Control as FilterControl;
use RS\Module\AbstractModel\TreeList\AbstractTreeListNode;
use RS\Module\AbstractModel\TreeList\TreeListFakeNode;
use RS\Module\AbstractModel\TreeList\TreeListOrmIterator;
use RS\Module\AbstractModel\TreeList\TreeListOrmPreLoader;
use RS\Orm\AbstractObject;
use RS\Orm\Request as OrmRequest;

/**
 * Класс для работы с древовидными списками. (с полной загрузкой списка в массив)
 */
abstract class TreeList extends EntityList
{
    protected $parent_field;
    protected $enable_tree_preload = true;

    /**
     * Возвращает экземпляр текущего класса
     *
     * @param string $key - идентификатор экземпляра класса
     * @return static
     */
    public static function getInstance($key = 'default')
    {
        static $instance = [];

        if (!isset($instance[$key])) {
            $instance[$key] = new static(); // у наследников конструктор без параметров
        }
        $instance[$key]->clearFilter();
        return $instance[$key];
    }

    /**
     * Устанавливает поле, в котором хранится ссылка на ID родителя записи
     *
     * @param string $field - поле ORM Объекта
     * @return TreeList
     */
    function setParentField($field)
    {
        $this->parent_field = $field;
        return $this;
    }

    /**
     * Возвращает поле, в котором хранится ссылка на ID родителя записи
     *
     * @return string
     */
    function getParentField()
    {
        return $this->parent_field;
    }

    /**
     * Возвращает список элементов, составляющих путь к элементу от корня
     *
     * @param int|string $id - id текущего элемента
     * @return AbstractObject[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public static function getPathToFirst($id)
    {
        $_this = static::getInstance();

        $tree = OrmRequest::make()
            ->from($_this->getElement())
            ->exec()->fetchSelected($_this->id_field, $_this->getParentField());

        if (!isset($tree[$id])) {
            return array();
        }

        $path_ids = array();
        while (isset($tree[$id])) {
            $path_ids[] = $id;
            $id = $tree[$id];
        }
        $imploded_path_ids = "'" . implode("','", array_reverse($path_ids)) . "'";

        $path = OrmRequest::make()
            ->from($_this->getElement())
            ->whereIn($_this->id_field, $path_ids)
            ->orderby("field(`$_this->id_field`, $imploded_path_ids)")
            ->objects(null, $_this->id_field);

        return $path;
    }

    /**
     * Дополняет список идентификаторами родительских элементов
     *
     * @param int|string|int[]|string[]
     * @return int[]|string[]
     * @throws DbException
     * @throws RSException
     */
    public static function getParentIds($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $_this = static::getInstance();

        $tree = OrmRequest::make()
            ->from($_this->getElement())
            ->exec()->fetchSelected($_this->id_field, $_this->getParentField());

        $result = $ids;
        foreach ($ids as $id) {
            while (isset($tree[$id])) {
                if (in_array($id, $result)) {
                    break;
                } else {
                    $result[] = $id;
                    $id = $tree[$id];
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает элемент по Псевдониму
     *
     * @param string $alias - псевдоним
     * @param int|string $parent - ID родителя, если уникальность псевдонима в рамках родительского элемента
     * @return AbstractObject|false
     * @throws OrmException
     * @throws DbException
     */
    public function getByAlias($alias, $parent = null)
    {
        if (!isset($this->alias_field) || ($parent !== null && !$this->getParentField())) {
            return false;
        }

        $filter = array(
            $this->alias_field => $alias,
        );
        if ($parent !== null) {
            $filter[$this->getParentField()] = $parent;
        }
        $q = clone $this->queryObj();
        return $q->where($filter)->object();
    }

    /**
     * Статический вызов метода getTreeList()
     *
     * @param int $parent_id - id родительского узла
     * @param string[] $first_elements - значения, которые нужно добавить в начало списка
     * @return TreeListOrmIterator
     * @throws RSException
     */
    public static function staticTreeList($parent_id = 0, $first_elements = array())
    {
        $_this = static::getInstance();
        return $_this->getTreeList($parent_id, $_this->getFakeNodesFromStringArray($first_elements));
    }

    /**
     * Возвращает список фейковых узлов на основе списка названий
     *
     * @param string[] $items - список названий
     * @return TreeListFakeNode[]
     */
    protected function getFakeNodesFromStringArray($items)
    {
        $result = array();
        if ($items) {
            foreach ($items as $id => $name) {
                $result[] = new TreeListFakeNode(array(
                    TreeListFakeNode::DEFAULT_FIELD_ID => $id,
                    TreeListFakeNode::DEFAULT_FIELD_NAME => $name,
                ));
            }
        }
        return $result;
    }

    /**
     * Возвращает дерево элементов
     *
     * @param int $parent_id - id родительского узла
     * @param AbstractTreeListNode[] $first_elements - узлы, которые нужно добавить в начало списка
     * @return TreeListOrmIterator
     * @throws RSException
     */
    public function getTreeList($parent_id = 0, array $first_elements = array())
    {
        $api = clone $this;
        $iterator = new TreeListOrmIterator($api, $parent_id);
        if ($this->isEnableTreePreload()) {
            $iterator->setPreLoader(new TreeListOrmPreLoader($api));
        }
        $iterator->setFirstElements($first_elements);
        return $iterator;
    }

    /**
     * @deprecated (19.03) - не используется после переработки древовидных списков
     * Возвращает список всех категорий в одноуровневом массиве с соответствующим уровню вложенности количеством отступов
     *
     * @param int $parent_id - id корневого элемента дерева
     * @param string[] $first - значения, которые нужно добавить в начало списка
     * @return string[]
     * @throws DbException
     */
    public function getSelectList($parent_id = 0, array $first = array())
    {
        $tree = $this->queryObj()
            ->select($this->id_field, $this->name_field, $this->getParentField())
            ->exec()->fetchSelected($this->getParentField(), null, true);

        $result = $this->recursiveConvertTreeToSelectList($tree, $parent_id);
        if (!empty($first)) {
            $result = $first + $result;
        }
        return $result;
    }

    /**
     * @deprecated (19.03) - не используется после переработки древовидных списков
     * Рекурсивно превращает древовидную структуру в список для select-а
     *
     * @param array $tree - список
     * @param $parent_id - id корневого элемента дерева
     * @param int $level - технический параметр, уровень вложенности
     * @return string[]
     */
    protected function recursiveConvertTreeToSelectList(array $tree, $parent_id, $level = 0)
    {
        $result = array();
        if (!empty($tree[$parent_id])) {
            foreach ($tree[$parent_id] as $item) {
                $result[$item[$this->getIdField()]] = str_repeat('&nbsp;', $level * 4) . $item[$this->name_field];
                $result = $result + $this->recursiveConvertTreeToSelectList($tree, $item[$this->getIdField()], $level + 1);
            }
        }
        return $result;
    }

    /**
     * @deprecated (19.03) - не используется после переработки древовидных списков
     * Аналог getSelectList, только для статичского вызова
     *
     * @param int $parent_id = id корневого элемента дерева
     * @param array $first = значения, которые нужно добавить в начало списка
     * @return array
     * @throws DbException
     */
    static function staticSelectList($parent_id = 0, $first = array())
    {
        $_this = static::getInstance();
        return $_this->getSelectList($parent_id, (array)$first);
    }

    /**
     * @deprecated (19.05) - дублирует getChildsId()
     * Дополняет список $list идентификаторами всех дочерних элементов
     *
     * @param string[]|int[] $list - список ID элементов, для которых необходимо найти дочерние ID
     * @return string[]|int[]
     * @throws DbException
     * @throws RSException
     */
    public static function FindSubFolder(array $list)
    {
        return self::getChildsId($list);
    }

    /**
     * Рекурсивно собирает идентификаторы дочерних элементов узла дерева
     *
     * @param array $tree - массив вида id => array(child_ids)
     * @param string|int $id - идентификатор узла
     * @return string[]|int[]
     */
    protected static function recursiveGetChildIdsFromTree(array $tree, $id)
    {
        $result = array($id);
        if (isset($tree[$id])) {
            foreach ($tree[$id] as $sub_id) {
                $result = array_merge($result, self::recursiveGetChildIdsFromTree($tree, $sub_id));
            }
        }
        return $result;
    }

    /**
     * Дополняет список идентификаторами всех дочерних элементов
     *
     * @param int|string|int[]|string[] $ids - список ID элементов, для которых необходимо найти дочерние ID
     * @return int[]|string[]
     * @throws DbException
     * @throws RSException
     */
    public static function getChildsId($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $_this = static::getInstance();

        $tree = OrmRequest::make()
            ->from($_this->getElement())
            ->exec()->fetchSelected($_this->getParentField(), $_this->id_field, true);

        $result = array();
        foreach ($ids as $id) {
            $result = array_merge($result, self::recursiveGetChildIdsFromTree($tree, $id));
        }

        return $result;
    }

    /**
     * Удаляет список объектов по id, включая дочерние элементы
     *
     * @param array $ids - массив ID объектов
     * @return bool
     * @throws DbException
     * @throws RSException
     */
    public function del(array $ids)
    {
        if ($this->noWriteRights($this->getElement()->getRightDelete())) return false;

        $subIdList = self::getChildsId($ids);
        return parent::del($subIdList);
    }

    /**
     * @deprecated (03.19) - метод устарел, вместо него следует использовать getPathToFirst()
     *
     * @param mixed $id
     * @return AbstractObject[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public function queryParents($id)
    {
        return $this->getPathToFirst($id);
    }

    /**
     * Возвращает список непосредственных детей элемента $id используя запросы к БД
     *
     * @param mixed $parent_id - ID родителя
     * @param string $order - сортировка
     * @return array
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function queryGetChilds($parent_id, $order = null)
    {
        if (!isset($order)) $order = $this->default_order;

        return OrmRequest::make()->select('*')->from($this->obj_instance)
            ->where(array($this->parent_field => $parent_id))
            ->orderby($order)
            ->objects($this->obj);
    }

    /**
     * Перемещает элемент from на место элемента to. Если flag = 'up', то до элемента to, иначе после
     *
     * @param int $from - id элемента, который переносится
     * @param int $to - id ближайшего элемента, возле которого должен располагаться элемент
     * @param string $flag - up или down - флаг выше или ниже элемента $to должен располагаться элемент $from
     * @param OrmRequest $extra_expr - объект с установленными уточняющими условиями, для выборки объектов сортировки
     * @param int $new_parent_id - новый ID родительского элемента
     * @return bool
     * @throws DbException
     * @throws RSException
     * @throws EventException
     */
    public function moveElement($from, $to, $flag, OrmRequest $extra_expr = null, $new_parent_id = null)
    {
        if ($this->noWriteRights($this->getElement()->getRightUpdate())) return false;

        //Если требуется перенос элемента к другому родителю
        //Сначала переносим элемент в конец колонки назначения, затем выполняем обычную сортировку
        $from_obj = $this->getOneItem($from);
        if ($new_parent_id !== null && $from_obj[$this->parent_field] != $new_parent_id) {
            $from_obj[$this->parent_field] = $new_parent_id;
            $from_obj[$this->sort_field] = OrmRequest::make()
                    ->select('MAX(sortn) as maxid')
                    ->from($from_obj)
                    ->where(array(
                        $this->parent_field => $new_parent_id
                    ))->exec()->getOneField('maxid', 0) + 1;
            $from_obj->update();
        }

        if (!$extra_expr) {
            $extra_expr = OrmRequest::make()->where(array($this->parent_field => $from_obj[$this->parent_field]));
            if ($this->isMultisite()) {
                $extra_expr->where(array($this->site_id_field => $from_obj[$this->site_id_field]));
            }
        }
        return parent::moveElement($from, $to, $flag, $extra_expr);
    }

    /**
     * Проверяет возможно ли множественное изменение с параметрами, переданными в POST
     *
     * @param AbstractObject $element - orm объект
     * @param array $post - данные из POST
     * @param array $ids - список id редактируемых объектов
     * @return bool
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public function multiEditCheck($element, $post, $ids)
    {
        if (isset($post[$this->getParentField()])) {
            $path_ids = $this->getPathToFirst($post[$this->getParentField()]);
            foreach ($ids as $n => $id) {
                if (isset($path_ids[$id])) {
                    $element->addError(t('Неверно указан родительский элемент'), 'parent');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Устанавливает фильтры, от компонента \RS\Html\Filter\Control
     *
     * @param FilterControl $filter_control - объект фильтра
     * @return EntityList
     * @throws DbException
     */
    function addFilterControl(FilterControl $filter_control)
    {
        $sqlFilter = $filter_control->getSqlWhere();

        if (!empty($sqlFilter)) {
            $cloned_query = clone $this->queryObj();
            $tree_ids = array(0);
            if ($ids = $cloned_query->where($sqlFilter)->exec()->fetchSelected(null, 'id')) {
                $tree_ids = $ids;

                do {
                    $cloned_query = clone $this->queryObj();
                    $ids = $cloned_query->whereIn('id', $ids)->groupby('parent')->exec()->fetchSelected(null, 'parent');
                    $tree_ids = array_merge($tree_ids, $ids);
                } while ($ids && $ids != array(0));

                $tree_ids = array_unique($tree_ids);
            }

            $this->filter_active = true;
            $this->queryObj()->whereIn('id', $tree_ids);
        }
        $filter_control->modificateQuery($this->queryObj());
        return $this;
    }

    /**
     * Возвращает разрешена ли предварительная загрузка дерева элементов
     *
     * @return bool
     */
    public function isEnableTreePreload()
    {
        return $this->enable_tree_preload;
    }

    /**
     * Устанавливает разрешение на предварительную загрузку дерева элементов
     *
     * @param bool $enable_tree_preload - значение
     * @return void
     */
    public function setEnableTreePreload($enable_tree_preload)
    {
        $this->enable_tree_preload = $enable_tree_preload;
    }
}
