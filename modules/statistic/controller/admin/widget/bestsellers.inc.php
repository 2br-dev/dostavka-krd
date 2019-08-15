<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Widget;
use \RS\Application\Block\Template;

class Bestsellers extends \RS\Controller\Admin\Widget
{
    protected 
        $info_title = 'Самые продаваемые товары',
        $info_description = 'Отображает диаграмму самых продаваемых товаров';
    
    function actionIndex()
    {
        return $this->result
                ->setHtml( Template::insert('\Statistic\Controller\Admin\Block\BestsellersLite') );
    }
}