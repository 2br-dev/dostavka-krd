<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Widget;
use \RS\Application\Block\Template;

class KeyIndicators extends \RS\Controller\Admin\Widget
{
    protected 
        $info_title = 'Ключевые показатели',
        $info_description = 'Основные финансовые показатели продуктивности интернет-магазина';

    function actionIndex()
    {
        return $this->result
            ->setHtml( Template::insert('Statistic\Controller\Admin\Block\KeyIndicatorsLite') );
    }
}

