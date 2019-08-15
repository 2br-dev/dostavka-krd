<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Banners\Controller\Admin;

use Banners\Model\BannerApi;
use Banners\Model\Orm\Banner;
use Banners\Model\ZoneApi as BannerZoneApi;
use RS\Controller\Admin\Crud;
use RS\Html\Category;
use RS\Html\Table\Type as TableType;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Toolbar;
use RS\Html\Table;
use RS\AccessControl\Rights;
use RS\AccessControl\DefaultModuleRights;
use RS\Router\Manager as RouterManager;

class Ctrl extends Crud
{
    public $zone;

    function __construct()
    {
        parent::__construct(new BannerApi());
        $this->setCategoryApi(new BannerZoneApi(), t('зону'));
    }

    function actionIndex()
    {
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->zone > 0 && !$this->getCategoryApi()->getById($this->zone)) $this->zone = 0;
        if ($this->zone > 0) $this->api->setFilter('zone_id', $this->zone);
        $this->getHelper()->setTopTitle(t('Баннеры'));

        return parent::actionIndex();
    }

    function helperIndex()
    {
        $collection = parent::helperIndex();

        $this->zone = $this->url->request('zone', TYPE_STRING);
        //Параметры таблицы
        $collection->setTopHelp(t('Используйте баннеры, чтобы в яркой форме донести до ваших пользователей важную информацию.
                                    Баннеры - это картинки, которые могут размещаться в баннерных зонах. Баннерная зона - это группа банеров.
                                    На сайте можно размещать баннерные зоны в удобных местах с помощью раздела <i>Веб-сайт &rarr; Конструктора сайта</i>.
                                    Устанавливая различный вес баннерам, можно управлять их сортировкой или вероятностью их отображения в определенных случаях.'));
        $collection->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить баннер'))));
        $collection['topToolbar']->addItem(new ToolbarButton\Dropdown(array(
            array(
                'title' => t('Импорт/Экспорт'),
                'attr' => array(
                    'class' => 'button',
                    'onclick' => "JavaScript:\$(this).parent().rsDropdownButton('toggle')"
                )
            ),
            array(
                'title' => t('Экспорт зон в CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('exportCsv', array('schema' => 'banners-zone', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Экспорт баннеров в CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('exportCsv', array('schema' => 'banners-banner', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт зон из CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('importCsv', array('schema' => 'banners-zone', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            ),
            array(
                'title' => t('Импорт баннеров из CSV'),
                'attr' => array(
                    'href' => RouterManager::obj()->getAdminUrl('importCsv', array('schema' => 'banners-banner', 'referer' => $this->url->selfUri()), 'main-csv'),
                    'class' => 'crud-add'
                )
            )
        )), 'import');

        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => '20'), 'TdAttr' => array('align' => 'center'))),
                new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Yesno('public', t('Публичный'), array('toggleUrl' => $this->router->getAdminPattern('ajaxTogglePublic', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('link', t('Ссылка')),
                new TableType\Text('weight', t('Вес'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('клонировать баннер'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                            )
                        ),
                    ))
                ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));

        $collection->setCategory(new Category\Element(array(
            'sortIdField' => 'id',
            'activeField' => 'id',
            'activeValue' => $this->zone,
            'rootItem' => array(
                'id' => 0,
                'title' => t('Все'),
                'noOtherColumns' => true,
                'noCheckbox' => true,
                'noDraggable' => true,
                'noRedMarker' => true
            ),
            'noExpandCollapseButton' => true,
            'sortable' => false,
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':zone' => '@id')))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('categoryEdit', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    )
                )),
                new TableType\Action\DropDown(array(
                    array(
                        'title' => t('клонировать зону'),
                        'attr' => array(
                            'class' => 'crud-add',
                            '@href' => $this->router->getAdminPattern('categoryClone', array(':id' => '~field~')),
                        )
                    ),
                ))
            )),
            'headButtons' => array(
                array(
                    'attr' => array(
                        'title' => t('Создать зону'),
                        'href' => $this->router->getAdminUrl('categoryAdd'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        )), $this->getCategoryApi());

        $collection->setCategoryBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Multiedit($this->router->getAdminUrl('categoryMultiEdit')),
                new ToolbarButton\Delete(null, null, array(
                    'attr' => array('data-url' => $this->router->getAdminUrl('categoryDel'))
                )),
            ),
        )));

        $collection->setBottomToolbar($this->buttons(array('multiedit', 'delete')));

        $collection->viewAsTableCategory();
        return $collection;
    }

    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $zone_id = $this->url->request('zone', TYPE_INTEGER);
        /** @var Banner $obj */
        $obj = $this->api->getElement();

        if ($primaryKey === null) {
            if ($zone_id) {
                $obj['xzone'] = array($zone_id);
            }
        } else {
            $obj->fillZones();
        }
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }

    function actionAjaxTogglePublic()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_UPDATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }

        $id = $this->url->get('id', TYPE_STRING);

        $banner = $this->api->getOneItem($id);
        if ($banner) {
            $banner['public'] = !$banner['public'];
            $banner->update();
        }
        return $this->result->setSuccess(true);
    }
}
