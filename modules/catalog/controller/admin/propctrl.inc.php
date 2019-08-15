<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Admin;

use Catalog\Model\Orm\Property\Dir as PropertyDir;
use Catalog\Model\Orm\Property\Item as PropertyItem;
use Catalog\Model\Orm\Property\ItemValue as PropertyItemValue;
use Catalog\Model\PropertyApi;
use Catalog\Model\PropertyDirApi;
use Catalog\Model\PropertyValueApi;
use RS\Controller\Admin\Crud;
use RS\Html\Category;
use RS\Html\Table\Type as TableType;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Filter;
use RS\Html\Toolbar;
use RS\Html\Table;
use RS\Router\Manager as RouterManager;

class PropCtrl extends Crud
{
    protected $form_tpl = 'form_prop.tpl';
    protected $me_form_tpl = 'me_prop.tpl';
    protected $action_var = 'do';
    protected $dir;
    /** @var PropertyApi */
    protected $api;

    public function __construct()
    {
        parent::__construct(new PropertyApi());
        $this->setCategoryApi(new PropertyDirApi(), t('группу характеристик'));
    }

    public function addResource()
    {
        $this->app->addCss($this->mod_css . 'property.css', null, BP_ROOT);
    }

    public function actionIndex()
    {
        if (!$this->getCategoryApi()->getOneItem($this->dir)) {
            $this->dir = 0; //Если категории не существует, то выбираем пункт "Все"
        }
        $this->api->setFilter('parent_id', $this->dir);

        return parent::actionIndex();
    }

    public function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Характеристики'));
        $this->dir = $this->url->request('dir', TYPE_INTEGER);
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить характеристику'))));
        //$helper->addCsvButton('catalog-property');
        $helper['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт'),
                'attr' => array(
                    'class' => 'button',
                    'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                )
            ),
            array(
                'title' => t('Экспорт характеристик в CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-property', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт значений характеристик в CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('exportCsv', array('schema' => 'catalog-propertyvalue', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт характеристик из CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-property', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт значений характеристик из CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('importCsv', array('schema' => 'catalog-propertyvalue', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            )
        )), 'import');

        $helper->setTopHelp($this->view->fetch('help/propctrl_index.tpl'));
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->viewAsTableCategory();

        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC, 'CurrentSort' => SORTABLE_ASC, 'ThAttr' => array('width' => '20'))),
                new TableType\Text('title', t('Название'), array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $this->dir)))),
                new TableType\Text('unit', t('Ед. изм')),
                new TableType\Text('type', t('Тип')),
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~', 'dir' => $this->dir)), null,
                        array(
                            'attr' => array(
                                '@data-id' => '@id'
                            )
                        )
                    ),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('Клонировать характеристику'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('удалить'),
                            'class' => 'crud-get',
                            'attr' => array(
                                '@href' => $this->router->getAdminPattern('del', array(':chk[]' => '@id')),
                            )
                        ),
                    ))),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )
        )));

        $helper->setCategory(new Category\Element(array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'disabledField' => 'hidden',
            'disabledValue' => '1',
            'activeValue' => $this->dir,
            'rootItem' => array(
                'id' => 0,
                'title' => t('Без группы'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'sortable' => true,
            'sortUrl' => $this->router->getAdminUrl('categoryMove'),
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id', 'c' => $this->url->get('c', TYPE_ARRAY))))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('categoryEdit', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    )
                )),
                new TableType\Action\DropDown(array(
                    array(
                        'title' => t('Клонировать категорию'),
                        'attr' => array(
                            'class' => 'crud-add',
                            '@href' => $this->router->getAdminPattern('CategoryClone', array(':id' => '~field~')),
                        )
                    ),
                )),
            )),
            'headButtons' => array(
                array(
                    'text' => t('Название группы'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                ),
                array(
                    'attr' => array(
                        'title' => t('Создать категорию'),
                        'href' => $this->router->getAdminUrl('categoryAdd'),
                        'class' => 'add crud-add'
                    )
                ),
            ),
        )), $this->getCategoryApi());

        $helper->setCategoryBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Delete(null, null, array('attr' =>
                    array('data-url' => $this->router->getAdminUrl('categoryDel'))
                )),
            )
        )));

        $helper->setCategoryFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('Items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                    )))
                ),
            )),
            'ToAllItems' => array('FieldPrefix' => $this->getCategoryApi()->defAlias()),
            'filterVar' => 'c',
            'Caption' => t('Поиск по группам')
        )));

        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('Items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                        new Filter\Type\Select('type', t('Тип'), array('' => t('Любой')) + PropertyItem::getAllowTypeValues()),
                    )))
                ))),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias()),
            'AddParam' => array('hiddenfields' => array('dir' => $this->dir)),
            'Caption' => t('Поиск по характеристикам')
        )));

        return $helper;
    }

    public function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $dir = $this->url->request('dir', TYPE_INTEGER);
        if ($primaryKey === null) {
            $elem = $this->api->getElement();
            $elem['parent_id'] = $dir;
            $elem->setTemporaryId();
        }

        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать характеристику {title}') : t('Добавить характеристику'));

        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }

    /**
     * Возвращает список категорий характеристик
     */
    public function actionAjaxGetPropertyList()
    {
        $dir_api = new PropertyDirApi();
        $groups = array(0 => array('title' => t('Без группы'))) + $dir_api->getListAsResource()->fetchSelected('id');

        $propapi = new PropertyApi();
        $propapi->setOrder('parent_id, sortn');
        $proplist = $propapi->getListAsResource()->fetchAll();

        $types = PropertyItem::getAllowTypeData();

        $result = array(
            'properties_sorted' => $proplist,
            'groups' => $groups,
            'types' => $types
        );

        return json_encode($result);
    }

    /**
     * Возвращает информацию о возможных значениях характеристики
     */
    public function actionAjaxGetPropertyValueList()
    {
        $prop_id = $this->url->request('prop_id', TYPE_INTEGER);

        $property_value_api = new PropertyValueApi();
        $property_value_api->setFilter('prop_id', $prop_id);
        $property_value_api->setFilter('value', '', '!=');

        $values = $property_value_api->getListAsResource()->fetchAll();

        return $this->result->addSection(array(
            'property_values' => $values
        ));
    }

    /**
     * Добавляет одно значение характеристики
     */
    public function actionAjaxAddPropertyValue()
    {
        $data = array(
            'prop_id' => $this->url->post('prop_id', TYPE_INTEGER),
            'value' => $this->url->post('value', TYPE_STRING)
        );

        $item_value = PropertyItemValue::loadByWhere($data);

        if ($item_value['id']) {
            return $this->result->addEMessage(t('Такое значение уже присутствует в списке'));
        }

        $item_value->getFromArray($data);
        $this->result->setSuccess($item_value->save(null, array(), $data));

        if ($this->result->isSuccess()) {
            $this->result->addSection('item_value', $item_value->getValues());
        } else {
            $this->result->addEMessage($item_value->getErrorsStr());
        }

        return $this->result;
    }

    /**
     * Создает или обноляет характеристику
     */
    public function actionAjaxCreateOrUpdateProperty()
    {
        $item = $this->url->request('item', TYPE_ARRAY);

        $prop_api = new PropertyApi();
        $this->result->setSuccess($result = $prop_api->createOrUpdate($item));

        if ($result) {
            /** @var PropertyDir $group */
            $group = $result['group'];
            /** @var PropertyItem $property */
            $property = $result['property'];

            $this->view->assign('group', array(
                'group' => $group,
            ));
            $this->view->assign(array(
                'properties' => array($property),
                'owner_type' => $item['owner_type']
            ));

            $this->result->addSection('group', $group->getValues());
            $this->result->addSection('prop', $property->getValues());
            $this->result->addSection('group_html', $this->view->fetch('property_group_product.tpl'));
            $this->result->addSection('property_html', $this->view->fetch('property_product.tpl'));
        } else {
            $this->result->setErrors($prop_api->getElement()->getDisplayErrors());
        }
        return $this->result->getOutput();
    }

    /**
     * Удаляет значение характеристики
     */
    public function actionAjaxRemovePropertyValue()
    {
        $value_id = $this->url->request('id', TYPE_INTEGER);
        $item_value = new PropertyItemValue($value_id);

        return $this->result->setSuccess($item_value->delete());
    }

    /**
     * Возвращает список подготовленных свойств для вставки на страницу
     */
    public function actionAjaxGetSomeProperties()
    {
        $ids = $this->url->request('ids', TYPE_ARRAY);

        $prop_api = new PropertyApi();
        $list = $prop_api->getPropertiesAndGroup($ids);

        $result = array();
        foreach ($list['groups'] as $group_id => $group) {
            /** @var PropertyDir $group */
            if (isset($list['properties'][$group_id])) {

                $this->view->assign(array(
                    'group' => array('group' => $group),
                    'owner_type' => 'group'
                ));
                $group_html = $this->view->fetch('property_group_product.tpl');

                foreach ($list['properties'][$group_id] as $property) {
                    /** @var PropertyItem $property */
                    $property['is_my'] = true;
                    $this->view->assign(array(
                        'properties' => array($property)
                    ));

                    $property_html = $this->view->fetch('property_product.tpl');

                    $result[] = array(
                        'group' => $group->getValues(),
                        'prop' => $property->getValues(),
                        'group_html' => $group_html,
                        'property_html' => $property_html
                    );
                }
            }
        }
        $this->result->addSection('result', $result);
        return $this->result->getOutput();
    }
}
