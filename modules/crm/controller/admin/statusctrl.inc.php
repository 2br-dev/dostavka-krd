<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Controller\Admin;

use Crm\Model\Orm\Status;
use Crm\Model\StatusApi;
use RS\Controller\Admin\Crud;
use RS\Html\Category;
use RS\Html\Table\Type as TableType;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Filter;
use RS\Html\Table;

/**
 * Контроллер управления статусами объектов CRM
 */
class StatusCtrl extends Crud
{
    protected $dir;

    function __construct()
    {
        parent::__construct(new StatusApi());
        $this->setCategoryApi(new StatusApi());

        $dir_keys = array_keys(Status::getObjectTypeAliases());
        $this->dir = $this->url->convert($this->url->request('dir', TYPE_STRING, $dir_keys[0]), $dir_keys);
    }

    /**
     * Формирует хелпер для отображения списка статусов
     *
     * @return \RS\Controller\Admin\Helper\CrudCollection
     */
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Статусы'));

        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить статус'))));

        $helper->setTopHelp(t('Управляйте статусами объектов CRM на этой странице.'));
        $helper->setBottomToolbar($this->buttons(array('delete')));
        $helper->viewAsTableCategory();

        $helper->addCsvButton('crm-status');

        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC, 'CurrentSort' => SORTABLE_ASC, 'ThAttr' => array('width' => '20'))),

                new TableType\Usertpl('title', t('Название'), '%crm%/admin/table/status_color.tpl', array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $this->dir)))),
                new TableType\Text('alias', t('Идентификатор'), array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $this->dir)))),

                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~', 'dir' => $this->dir)), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )
        )));

        $helper->setCategoryListFunction('getObjectTypesCategoryList');
        $helper->setCategory(new Category\Element(array(
            'noCheckbox' => true,
            'activeField' => 'id',
            'activeValue' => $this->dir,
            'sortable' => false,
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id', 'c' => $this->url->get('c', TYPE_ARRAY))))),
            'headButtons' => array(
                array(
                    'text' => t('Объекты'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                ),
            ),
        )));

        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('Items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                    )
                    ))
                ))),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias()),
            'AddParam' => array('hiddenfields' => array('dir' => $this->dir)),
            'Caption' => t('Поиск по статусам')
        )));

        return $helper;
    }

    /**
     * Отображает список статусов
     *
     * @return mixed
     */
    function actionIndex()
    {
        $this->api->setFilter('object_type_alias', $this->dir);
        return parent::actionIndex();
    }

    /**
     * Добавляет статус
     *
     * @param null $primaryKeyValue
     * @param bool $returnOnSuccess
     * @param null $helper
     * @return bool|\RS\Controller\Result\Standard
     */
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        /** @var @var Status $element */
        $status = $this->api->getElement();
        $status['object_type_alias'] = $this->dir;
        $this->user_post_data = array(
            'object_type_alias' => $this->dir
        );

        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }

    /**
     * Перемещает элемент
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionMove()
    {
        $from = $this->url->request('from', TYPE_INTEGER);
        $to = $this->url->request('to', TYPE_INTEGER);
        $direction = $this->url->request('flag', TYPE_STRING);
        return $this->result->setSuccess($this->api->moveElement($from, $to, $direction));
    }
}
