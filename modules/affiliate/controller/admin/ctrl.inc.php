<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Controller\Admin;

use Affiliate\Model\AffiliateApi;
use Affiliate\Model\Orm\Affiliate;
use Main\Model\GeoIpApi;
use RS\Controller\Admin\Crud;
use RS\Controller\Admin\Helper\CrudCollection;
use RS\Controller\Result\Standard;
use RS\Html\Tree;
use RS\Html\Toolbar;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Table\Type as TableType;
use RS\AccessControl\Rights;
use RS\AccessControl\DefaultModuleRights;

/**
 * Контроллер Управление списком магазинов сети
 */
class Ctrl extends Crud
{
    /** @var AffiliateApi */
    protected $api;

    public function __construct()
    {
        //Устанавливаем, с каким API будет работать CRUD контроллер
        parent::__construct(new AffiliateApi());
        $this->setTreeApi($this->api);
    }

    public function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Филиалы в городах'));
        $helper->setTopHelp($this->view->fetch('admin_help.tpl'));
        $helper->setTopToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Add($this->router->getAdminUrl('add'), t('добавить филиал')),
                new ToolbarButton\Button($this->router->getAdminUrl('sortAlphabetically'), t('Отсортировать по алфавиту'), array(
                    'attr' => array(
                        'class' => 'crud-get',
                        'data-confirm-text' => t('Отсортировать регионы и филиалы по алфавиту?'),
                    ),
                )),
            ),
        )));
        $helper->addCsvButton('affiliate-affiliate');

        $helper->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $helper->viewAsTree();
        $helper->setTree($this->getIndexTreeElement());

        return $helper;
    }

    /**
     * Возвращает объект с настройками отображения дерева
     * Перегружается у наследника
     *
     * @return Tree\Element
     */
    protected function getIndexTreeElement()
    {
        $tools = new TableType\Actions('id', array(
            new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '@id')), null, array(
                'attr' => array(
                    '@data-id' => '@id'
                )
            )),
            new TableType\Action\DropDown(array(
                'clone' => array(
                    'title' => t('Клонировать'),
                    'attr' => array(
                        'class' => 'crud-add',
                        '@href' => $this->router->getAdminPattern('clone', array(':id' => '@id')),
                    )
                ),
                'add_child' => array(
                    'title' => t('Добавить дочерний элемент'),
                    'attr' => array(
                        '@href' => $this->router->getAdminPattern('add', array(':pid' => '@id')),
                        'class' => 'crud-add'
                    )
                ),
                'set_default' => array(
                    'title' => t('Установить по умолчанию'),
                    'attr' => array(
                        '@href' => $this->router->getAdminPattern('setDefault', array(':id' => '@id')),
                        'class' => 'crud-get'
                    )
                ),
                'contact_page' => array(
                    'title' => t('Показать контакты на сайте'),
                    'attr' => array(
                        '@href' => $this->router->getUrlPattern('affiliate-front-contacts', array(':affiliate' => '@alias')),
                        'target' => '_blank'
                    )
                ),
            )),
        ));

        //Формируем список действий для дочерних элементов
        $sub_tools = clone $tools;
        $actions = $sub_tools->getActions();
        /** @var TableType\Action\DropDown $action_drop_down */
        $action_drop_down = $actions[1];
        $action_drop_down->removeItem('add_child'); //Исключаем пункт "Добавить дочерний элемент"
        $this->api->setSubTools($sub_tools);  //Устанавливаем список действий для дочерних элементов

        $tree = new Tree\Element(array(
            'maxLevels' => 2,
            'disabledField' => 'public',
            'disabledValue' => '0',
            'activeField' => 'id',
            'sortIdField' => 'id',
            'hideFullValue' => true,
            'sortable' => true,
            'sortUrl' => $this->router->getAdminUrl('treeMove'),
            'mainColumn' => new TableType\Usertpl('title', t('Название'), '%affiliate%/tree_column.tpl'),
            'tools' => $tools,
        ));

        return $tree;
    }

    /**
     * Сортирует филиалы по алфавиту
     *
     * @return Standard
     */
    public function actionSortAlphabetically()
    {
        $api = new AffiliateApi();
        $api->sortAffiliatesAlphabetically();

        return $this->result->setSuccess(true)->addMessage(t('Регионы и филиалы отсортированы в алфавитновм поядке'));
    }

    public function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $parent = $this->url->get('pid', TYPE_INTEGER, null);
        $obj = $this->api->getElement();

        if ($parent) {
            $obj['parent_id'] = $parent;
        }

        $title = $obj['id'] ? t('Редактировать филиал {title}') : t('Добавить филиал');
        $this->getHelper()->setTopTitle($title);

        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }

    public function actionMove()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_UPDATE)) {
            return $this->result->setSuccess(false);
        }

        $id = $this->url->request('id', TYPE_INTEGER);
        $affiliate = new Affiliate($id);
        if (!$affiliate['id']) $this->e404();

        $affiliate['is_default'] = 1;
        $this->result->setSuccess($affiliate->update());
        if (!$this->result->isSuccess()) {
            $this->result->addEMessage($affiliate->getErrorsStr());
        }
        return $this->result;
    }

    /**
     * Возвращает определенный по GEOip город
     *
     * @return Standard
     */
    public function actionAjaxCheckGeoDetection()
    {
        $geoIp = new GeoIpApi();

        $ip = $_SERVER['REMOTE_ADDR'];

        $this->view->assign(array(
            'geo_coordinates' => $geoIp->getCoordByIp($ip, false),
            'geo_city' => $geoIp->getCityByIp($ip, false),
            'affiliate' => AffiliateApi::getAffiliateByIp($ip),
            'ip' => $ip
        ));

        $helper = new CrudCollection($this);
        $helper->setTopTitle(t('Проверка геолокации'));
        $helper->viewAsAny();
        $helper->setForm($this->view->fetch('%affiliate%/check_geo_detection.tpl'));

        return $this->result->setTemplate($helper->getTemplate());
    }
}
