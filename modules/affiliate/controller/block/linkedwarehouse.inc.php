<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Affiliate\Controller\Block;

use Affiliate\Model\Orm\Affiliate;
use Catalog\Model\WareHouseApi;
use RS\Controller\Result\Standard as ResultStandard;
use RS\Controller\StandartBlock;
use RS\Orm\Exception as OrmException;

/**
 * Блок - связанные склады(магазины)
 */
class LinkedWarehouse extends StandartBlock
{
    protected static $controller_title = 'Связанные склады(магазины)';       //Краткое название контроллера
    protected static $controller_description = 'Отображает связанные с филиалом склады на странице контактов филиала';  //Описание контроллера

    protected $default_params = [
        'indexTemplate' => 'blocks/linkedwarehouse/linked_warehouse.tpl',
    ];

    /**
     * @return ResultStandard
     * @throws OrmException
     */
    function actionIndex()
    {
        $affiliate = $this->router->getCurrentRoute()->getExtra('affiliate');

        if ($affiliate instanceof Affiliate) {
            $warehouse_api = new WareHouseApi();
            $warehouse_api->setFilter(array(
                'public' => 1,
                'affiliate_id' => $affiliate['id']
            ));
            $warehouses = $warehouse_api->getList();
        } else {
            $warehouses = array();
        }

        $this->view->assign(array(
            'warehouses' => $warehouses,
            'affiliate' => $affiliate
        ));

        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }
}
