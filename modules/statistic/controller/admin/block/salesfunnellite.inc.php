<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Block;

class SalesFunnelLite extends SalesFunnel
{
    function __construct($param = array())
    {
        $param['widget'] = true;
        parent::__construct($param);
    }
    
    function actionIndex()
    {
        return parent::actionIndex()->setTemplate('blocks/sales_funnel_lite.tpl');
    }
    
}
