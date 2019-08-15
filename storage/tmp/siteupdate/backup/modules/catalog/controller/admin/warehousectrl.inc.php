<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Admin;

use Catalog\Model\WareHouseApi;
use Catalog\Model\WareHouseGroupApi;
use RS\Controller\Admin\Crud;
use RS\Controller\Admin\Helper\CrudCollection;
use RS\Controller\Result\Standard;
use RS\Html\Table\Type as TableType;
use RS\Html\Filter;
use RS\Html\Table;
use RS\Html\Toolbar;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Category;

/**
 * Класс контроллера складов админки
 */
class WareHouseCtrl extends Crud
{
    /** @var WareHouseApi */
    protected $api;
    protected $group;

    public function __construct()
    {
        parent::__construct(new WareHouseApi());
        $this->setCategoryApi(new WareHouseGroupApi(), t('группу складов'));
    }

    /**
     * Отображение списка
     *
     * @return mixed
     */
    public function actionIndex()
    {
        if ($this->group > 0 && !$this->getCategoryApi()->getById($this->group)) {
            $this->group = 0;
        }
        if ($this->group > 0) {
            $this->api->setFilter('group_id', $this->group);
        }
        return parent::actionIndex();
    }

    /**
     * Вызывается перед действием Index и возвращает коллекцию элементов,
     * которые будут находиться на экране.
     *
     * @return CrudCollection
     */
    public function helperIndex()
    {
        $helper = parent::helperIndex();
        $this->group = $this->url->request('group', TYPE_STRING);
        $helper->setTopHelp(t('На этой вкладке вы можете задать список складов, а также настроить их географическое положение, время работы и другие параметры. В некоторых случаях, складами могут выступать ваши магазины(торговые точки). Создав в данном разделе склады, вы сможе указывать остатки каждого товара (во вкладке Комплектации) на любом из ваших складов. Остатки на складах могут отображаться всем пользователям в карточке товара в виде условных рисок.'));
        $helper->setTopTitle(t('Склады'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить'))));
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->addCsvButton('catalog-warehouse');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC, 'CurrentSort' => SORTABLE_ASC, 'ThAttr' => array('width' => '20'))),
                new TableType\Text('title', t('Полное название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH, 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('alias', t('URL имя склада'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\StrYesno('default_house', t('Склад по умолчанию?')),
                new TableType\StrYesno('public', t('Публичный')),
                new TableType\StrYesno('checkout_public', t('Пункт самовывоза')),
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('клонировать склад'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                            ),
                        ),
                        array(
                            'title' => t('установить по умолчанию'),
                            'attr' => array(
                                '@data-url' => $this->router->getAdminPattern('setDefaultWareHouse', array(':id' => '@id')),
                                'class' => 'crud-get'
                            ),
                        ),
                    )),
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            ),
        )));

        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('items' => array(
                        new Filter\Type\Text('title', t('Полное наименование'), array('SearchType' => '%like%')),
                        new Filter\Type\Text('alias', t('URL имя склада'), array('SearchType' => '%like%')),
                    ))),
                ),
            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));

        $helper->setCategory(new Category\Element(array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $this->group,
            'rootItem' => array(
                'id' => 0,
                'title' => t('Все'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'noExpandCollapseButton' => true,
            'sortable' => true,
            'sortUrl' => $this->router->getAdminUrl('categoryMove'),
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':group' => '@id')))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('categoryEdit', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id',
                    ),
                )),
            )),
            'headButtons' => array(
                array(
                    'attr' => array(
                        'title' => t('Создать группу складов'),
                        'href' => $this->router->getAdminUrl('categoryAdd'),
                        'class' => 'add crud-add',
                    ),
                ),
            ),
        )), $this->getCategoryApi());

        $helper->setCategoryBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Delete(null, null, array('attr' => array(
                    'data-url' => $this->router->getAdminUrl('categoryDel'),
                ))),
            ),
        )));

        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));

        $helper->viewAsTableCategory();

        return $helper;
    }

    /**
     * AJAX
     */
    public function actionSetDefaultWareHouse()
    {
        $id = $this->url->request('id', TYPE_INTEGER);
        $this->api->setDefaultWareHouse($id);
        return $this->result->setSuccess(true)->getOutput();
    }

    /**
     * Метод для клонирования
     *
     * @return Standard
     * @throws \RS\Controller\ExceptionPageNotFound
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
            unset($elem['xml_id']);
            $elem['default_house'] = 0;
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
}
