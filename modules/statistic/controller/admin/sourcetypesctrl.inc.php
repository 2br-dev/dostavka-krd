<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin;

use RS\Controller\Admin\Crud;
use RS\Html\Category;
use RS\Html\Table\Type as TableType;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Filter;
use RS\Html\Toolbar;
use RS\Html\Table;
use Statistic\Model\SourceTypeDirsApi;
use Statistic\Model\SourceTypesApi;

/**
 * Класс контроллера типов источников
 * @package Statistic\Controller\Admin
 */
class SourceTypesCtrl extends Crud
{

    /**
     * @var \Statistic\Model\SourceTypeDirsApi $dir_api
     */
    protected $dir_api;
    protected $dir; //Выбранная категория
    /**
     * @var \Statistic\Model\SourceTypesApi $api
     */
    protected $api;

    function __construct()
    {
        parent::__construct(new SourceTypesApi());
        $this->setCategoryApi(new SourceTypeDirsApi(), t('тип источников'));
    }

    function actionIndex()
    {
        if (!$this->getCategoryApi()->getOneItem($this->dir)) {
            $this->dir = 0; //Если категории не существует, то выбираем пункт "Все"
        }
        $this->api->setFilter('parent_id', $this->dir);

        return parent::actionIndex();
    }

    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Типы источников пользователей'));
        $this->dir = $this->url->request('dir', TYPE_INTEGER);
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить тип'))));

        $helper['topToolbar']->addItem(new ToolbarButton\Button(
            $this->router->getAdminUrl('ajaxUpdateSourceTypes', null, 'statistic-tools'),
            t('Обновить источники'),
            array(
                'attr' => array(
                    'class' => 'crud-get'
                )
            )
        ));

        $helper->setTopHelp(t('Здесь указываются правила, по которым опознаётся источник приходящего пользователя. Когда пользователь впервые попадает на сайт, 
        к нему привязывается информация о том, откуда он перешел и дополнительные пареметры UTM меток, если они есть. Далее, когда происходят события оформления заказа или 
        покупки в одик клик или же отработала форма обратной связи, то сохраненная ранее информация об источнике приписывается к соответствующему объекту. В данном разделе вы можете управлять справочником источников. Для описания источника ReadyScript может анализировать заголовок REFERER, а также UTM метки. 
        Информация о том, какие источники привели к целевому действию можно просмотреть в отчетах статистики.'));
        $helper->addCsvButton('statistic-sourcetypes');
        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->viewAsTableCategory();

        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Text('sortn', t('Вес'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC, 'CurrentSort' => SORTABLE_ASC, 'ThAttr' => array('width' => '20'))),
                new TableType\Text('title', t('Название'), array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $this->dir)))),
                new TableType\Text('referer_site', t('Домен'), array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id', 'dir' => $this->dir)))),
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~', 'dir' => $this->dir)), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('Клонировать'),
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
                    ))
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
            )
        )));

        $helper->setCategory(new Category\Element(array(
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
            'sortUrl' => $this->router->getAdminUrl('categoryMove'),
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id', 'c' => $this->url->get('c', TYPE_ARRAY))))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('categoryEdit', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    ))),
                new TableType\Action\DropDown(array(
                    array(
                        'title' => t('Клонировать категорию'),
                        'attr' => array(
                            'class' => 'crud-add',
                            '@href' => $this->router->getAdminPattern('categoryClone', array(':id' => '~field~')),
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
            ))));

        $helper->setCategoryFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('Items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                    )
                    ))
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
                    )
                    ))
                ))),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias()),
            'AddParam' => array('hiddenfields' => array('dir' => $this->dir)),
            'Caption' => t('Поиск по типам')
        )));


        return $helper;
    }

    /**
     * Добавление типа источника
     *
     * @param null $primaryKey
     * @param bool $returnOnSuccess
     * @param null $helper
     * @return bool|\RS\Controller\Result\Standard
     */
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $dir = $this->url->request('dir', TYPE_INTEGER);
        if ($primaryKey === null) {
            $elem = $this->api->getElement();
            $elem['parent_id'] = $dir;
        }

        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать тип {title}') : t('Добавить тип'));

        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
}
