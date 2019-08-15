<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Widget;
use \RS\Application\Block\Template;

class SalesFunnel extends \RS\Controller\Admin\Widget
{
    protected $info_title = 'Воронка заказов';
    protected $info_description = 'Наглядная статистика по прохождению этапов оформления заказа покупателями';

    function actionIndex()
    {
        return $this->result
                ->setHtml( Template::insert('Statistic\Controller\Admin\Block\SalesFunnelLite') );
    }
}

