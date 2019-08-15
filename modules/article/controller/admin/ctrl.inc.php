<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Article\Controller\Admin;

use Article\Model\Api as ArticleApi;
use Article\Model\CatApi;
use Article\Model\Orm\Article;
use RS\Application\Application;
use RS\Controller\Admin\Crud;
use RS\Db\Exception as DbException;
use RS\Exception as RSException;
use RS\Html\Table\Type as TableType;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Toolbar;
use RS\Html\Tree;
use RS\Html\Filter;
use RS\Html\Table;
use RS\Orm\Exception as OrmException;

class Ctrl extends Crud
{
    /** @var ArticleApi */
    protected $api;
    protected $dir;

    public function __construct()
    {
        parent::__construct(new ArticleApi());
        $this->setTreeApi(new Catapi(), t('категорию статей'));
    }

    public function actionIndex()
    {
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->dir > 0 && !$this->getTreeApi()->getById($this->dir)) $this->dir = 0;
        if ($this->dir > 0) $this->api->setFilter('parent', $this->dir);
        $this->getHelper()->setTopTitle(t('Статьи по тематикам'));

        return parent::actionIndex();
    }

    public function helperIndex()
    {
        $collection = parent::helperIndex();

        $this->dir = $this->url->request('dir', TYPE_STRING);
        //Параметры таблицы
        $collection->setTopHelp(t('В этом разделе вы можете размещать текстовую информацию (контент). Если у вас на сайте есть раздел «Новости», то все материалы закрепляются в соответствующей рубрике, здесь также можно размещать информацию.'));
        $collection->setTopToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Dropdown(array(
                    array(
                        'title' => t('добавить статью'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('add', array('dir' => $this->dir)),
                            'class' => 'btn-success crud-add',
                        )
                    ),
                    array(
                        'title' => t('добавить категорию статей'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('treeAdd'),
                            'class' => 'crud-add',
                        )
                    )
                )),
            )
        )));
        $collection->addCsvButton('article-article');
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('short_content', t('Краткий текст'), array('hidden' => true)),
                new TableType\Datetime('dateof', t('Размещено'), array('hidden' => true, 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('id', '№', array('ThAttr' => array('width' => '50'), 'TdAttr' => array('class' => 'cell-sgray'), 'Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id',
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('клонировать'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('показать статью на сайте'),
                            'attr' => array(
                                'target' => '_blank',
                                '@href' => function ($row) {
                                    /** @var Article $row */
                                    return $row->getUrl();
                                }
                            )
                        )
                    ))
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
            )
        )));

        //Параметры фильтра
        $collection->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('items' => array(
                        new Filter\Type\Text('id', '№', array('Attr' => array('size' => 4))),
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => 'like%')),

                        new Filter\Type\Date('dateof', t('Дата'), array('ShowType' => true)),
                    )))
                ),
                'SecContainers' => array(
                    new Filter\Seccontainer(array(
                        'Lines' => array(
                            new Filter\Line(array('items' => array(
                                new Filter\Type\Text('alias', t('Псевдоним'), array('SearchType' => 'like%'))
                            )))
                        )
                    ))
                )
            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));

        $collection->setTree($this->getIndexTreeElement(), $this->getTreeApi());

        $collection->setTreeBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Multiedit($this->router->getAdminUrl('treeMultiEdit')),
                new ToolbarButton\Delete(null, null, array(
                    'attr' => array('data-url' => $this->router->getAdminUrl('treeDel'))
                )),
            ),
        )));

        $collection->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $collection->viewAsTableTree();
        return $collection;
    }

    /**
     * Возвращает объект с настройками отображения дерева
     *
     * @return Tree\Element
     * @throws DbException
     * @throws RSException
     * @throws OrmException
     */
    protected function getIndexTreeElement()
    {
        $tree = new Tree\Element(array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $this->dir,
            'pathToFirst' => $this->getTreeApi()->getPathToFirst($this->dir),
            'rootItem' => array(
                'id' => 0,
                'title' => t('Все'),
                '_class' => 'root noDraggable',
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noFullValue' => true,
            ),
            'sortable' => true,
            'sortUrl' => $this->router->getAdminUrl('treeMove'),
            'mainColumn' => new TableType\Text('title', t('Название'), array(
                'linkAttr' => array('class' => 'call-update'),
                'href' => $this->router->getAdminPattern(false, array(':dir' => '@id')),
            )),
            'tools' => new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('treeEdit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id',
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('добавить дочернюю категорию'),
                            'attr' => array(
                                '@href' => $this->router->getAdminPattern('treeAdd', array(':pid' => '~field~')),
                                'class' => 'crud-add',
                            )
                        ),
                        array(
                            'title' => t('клонировать'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('treeClone', array(':id' => '~field~', ':pid' => '@parent')),
                            )
                        ),
                        array(
                            'title' => t('показать на сайте'),
                            'attr' => array(
                                'target' => '_blank',
                                '@href' => function ($row) {
                                    if ($row['id'] > 0) {
                                        /** @var Article $row */
                                        return $row->getUrl();
                                    }
                                    return null;
                                }
                            )
                        ),
                        array(
                            'title' => t('удалить'),
                            'attr' => array(
                                '@href' => $this->router->getAdminPattern('treeDel', array(':chk[]' => '~field~')),
                                'class' => 'crud-remove-one',
                            ),
                        ),
                    )))
            ),
            'headButtons' => array(
                array(
                    'attr' => array(
                        'title' => t('Создать категорию'),
                        'href' => $this->router->getAdminUrl('treeAdd'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        ));
        return $tree;
    }

    public function actionTreeAdd($primaryKey = null)
    {
        if ($primaryKey === null) {
            $pid = $this->url->request('pid', TYPE_STRING, '');
            $this->getTreeApi()->getElement()->offsetSet('parent', $pid);
        }

        return parent::actionAdd($primaryKey);
    }

    public function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $parent = $this->url->request('dir', TYPE_INTEGER);
        $obj = $this->api->getElement();

        if ($primaryKey === null) {
            if ($parent) {
                $obj['parent'] = $parent;
            }
            if (!isset($primaryKey)) {
                $obj['dateof'] = date('Y-m-d H:i:s');
            }
            $obj->setTemporaryId();
        }

        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать статью {title}') : t('Добавить статью'));
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }

    /**
     * Редактирование статьи
     *
     */
    public function actionEdit()
    {
        $byalias = $this->url->get('byalias', TYPE_STRING, false);
        if (!empty($byalias)) {
            $article = $this->api->getByAlias($byalias);
            Application::getInstance()->redirect($this->router->getAdminUrl('edit', array('id' => $article['id'])));
        }
        return parent::actionEdit();
    }

    /**
     * Метод для клонирования
     *
     */
    public function actionClone()
    {
        $this->setHelper($this->helperAdd());
        $id = $this->url->get('id', TYPE_INTEGER);

        $elem = $this->api->getElement();

        if ($elem->load($id)) {
            $clone_id = null;
            if (!$this->url->isPost()) {
                $clone = $elem->cloneSelf();
                $this->api->setElement($clone);
                $clone_id = $clone['id'];
            }
            unset($elem['id']);
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
}
