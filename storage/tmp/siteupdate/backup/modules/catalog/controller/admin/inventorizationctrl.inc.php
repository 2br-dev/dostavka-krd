<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table,
    \RS\Html\Filter;

/**
 *  Контроллер документа инвенторизации
 *
 * Class Inventorization
 * @package Catalog\Controller\Admin
 */
class InventorizationCtrl extends \RS\Controller\Admin\Crud
{
    public $config;

    function __construct()
    {
        $this->config = \RS\Config\Loader::byModule($this);
        parent::__construct(new \Catalog\Model\Inventory\InventorizationApi());
    }

    /**
     * Вызывается перед действием Index и возвращает коллекцию элементов,
     * которые будут находиться на экране.
     */
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Инвентаризации'));
        $config = \RS\Config\Loader::byModule($this);
        $helper->setTopHelp(t('Инвентаризация фиксирует несовпадение количества товара на складе с количеством на сайте. Создает 2 связанных документа: списание и оприходование.'));
        if(!$config['inventory_control_enable']){
            $helper->getTopToolbar()->removeItem('add');
            $helper->removeSection('bottomToolbar');
            $smarty = new \RS\View\Engine();
            $notice = $smarty->fetch('%catalog%/inventory/inventory_disabled_notice.tpl');
            $helper->setBeforeTableContent($notice);
        }
        $helper -> setTable(new Table\Element(array(
                'Columns' => array(
                    new TableType\Checkbox('id', array('showSelectAll' => true)),
                    new TableType\Usertpl('title', t('Документ'), '%catalog%/form/inventory/field_inventorization_title.tpl'),
                    new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'), 'ThAttr' => array('width' => '50'), 'Sortable' => SORTABLE_BOTH)),
                    new TableType\Text('warehouse', t('Склад'), array('Sortable' => SORTABLE_BOTH)),
                    new TableType\StrYesno('applied', t('Проведен'), array('Sortable' => SORTABLE_BOTH)),
                    new TableType\Usertpl('linked_document', t('Связанный документ'), '%catalog%/form/inventory/field_linked_document.tpl'),
                    new TableType\Datetime('date', t('Дата'), array('Sortable' => SORTABLE_BOTH)),
                    new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                            'noajax' => true,
                            'attr' => array(
                                '@data-id' => '@id'
                            ))),
                    ),
                        array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                    ),
                ))
        ));
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' => array(
                    new Filter\Line( array('Items' => array(
                        new Filter\Type\Product('product_id', t('Товар'), array(
                            'ModificateQueryCallback' => function($q, $filter_type) {
                                $product_id = $filter_type->getValue();
                                if($product_id){
                                    $q  ->select('A.id')
                                        ->where("item.product_id = $product_id")
                                        ->join(new \Catalog\Model\Orm\Inventory\InventorizationProducts(), 'A.id = item.document_id', 'item');
                                }
                                return $q;
                            }
                        )),
                    ))),
                ),
                'SecContainer' => new Filter\Seccontainer( array(
                    'Lines' =>  array(
                        new Filter\Line( array('Items' => array(
                            new Filter\Type\Text('A.id', '№'),
                            new Filter\Type\DateRange('date', t('Дата оформления')),
                            new Filter\Type\Select('applied', t('Проведен'), array(''=>t('Неважно'),'1' => t('Да'),'0'=>t('Нет'))),
                            new Filter\Type\Select('warehouse', t('Склад'),  array('' => t('Неважно')) + \Catalog\Model\WareHouseApi::staticSelectList()),
                        )
                        )),
                    )
                ))
            )),
            'Caption' => t('Поиск')
        )));
        return $helper;
    }

    /**
     * Форма добавления документа
     *
     * @param mixed $primaryKeyValue - id редактируемой записи
     * @param boolean $returnOnSuccess - Если true, то будет возвращать === true при успешном сохранении,
     *                                   иначе будет вызов стандартного _successSave метода
     * @param null|\RS\Controller\Admin\Helper\CrudCollection $helper - текуй хелпер
     * @return \RS\Controller\Result\Standard|bool
     */
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $orm = $this->api->getElement();
        if(!$primaryKeyValue){
            $orm['date'] = date('Y-m-d H:i:s');
        }
        $helper = $this->getHelper();
        $helper->setTopTitle($primaryKeyValue ? t('Редактировать документ инвентаризации') : t('Добавить инвентаризацию товаров'));
        if(!$this->config['inventory_control_enable']){
            $helper->removeSection('bottomToolbar');
            $properties = $orm->getPropertyIterator();
            $properties->setPropertyOptions(array(
                'readonly' => true
            ));
            $orm['inventory_disabled'] = true;
        }
        $this->router->getCurrentRoute()->addExtra('type', \Catalog\Model\Orm\Inventory\Inventorization::DOCUMENT_TYPE_INVENTORY);
        if($this->url->isPost()) {
            $refresh_mode = $this->url->request('refresh', TYPE_BOOLEAN);
            $warehouse_id = $this->url->request('warehouse', TYPE_INTEGER, 0);
            $recalculate = $this->url->request('recalculate', TYPE_BOOLEAN, false);
            $items = $this->url->request('items', TYPE_ARRAY, null);
            $products = $this->api->prepareProductsArray($items, $warehouse_id, $recalculate);
            if (!$refresh_mode) {
                $orm['items'] = $items;
                $orm['type'] = $this->url->request('type', TYPE_STRING);
                return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
            } else {
                $this->wrap_output = false;
                $warehouses = \Catalog\Model\WareHouseApi::staticSelectList();
                $this->view->assign(array(
                    'api' => $this->api,
                    'products' => $products,
                    'warehouses' => $warehouses,
                    'is_inventory' => true,
                ));
                return $this->result->setTemplate("%catalog%form/inventory/products_in_table.tpl");
            }
        }
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }

    /**
     *
     *
     * @return int|mixed
     */
    function actionGetNum()
    {
        $this->wrap_output = false;
        $offer_id = $this->url->request('offer_id', TYPE_INTEGER, 0);
        $warehouse = $this->url->request('warehouse', TYPE_INTEGER, 0);
        if($offer_id && $warehouse){
            return \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Xstock())
                ->where(array(
                    'offer_id' => $offer_id,
                    'warehouse_id' => $warehouse,
                ))
                ->exec()
                ->getOneField('stock', 0);
        }
        return 0;
    }

}