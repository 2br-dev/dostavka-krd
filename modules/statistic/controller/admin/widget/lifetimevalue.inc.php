<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Widget;
use \RS\Application\Block\Template;

class LifeTimeValue extends \RS\Controller\Admin\Widget
{
    protected $info_title = 'Выручка от клиентов';
    protected $info_description = 'Диаграмма самых доходных пользователей';
    
    function actionIndex()
    {
        return $this->result
            ->setHtml( Template::insert('\Statistic\Controller\Admin\Block\LifeTimeValueLite') );
    }
}

