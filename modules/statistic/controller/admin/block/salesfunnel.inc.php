<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Block;

use Main\Model\StatisticEvents;
use Statistic\Model\Components\DatePeriodSelector;
use Statistic\Model\StatEventApi;

/**
 * Class Statistic\Controller\Admin\Block\SalesFunnel
 * @package Statistic\Controller\Admin\Block
 */
class SalesFunnel extends \RS\Controller\Admin\Block
{
    protected 
        $id,
        $title = 'Воронка оформления заказа';


    function __construct($params = array())
    {
        parent::__construct($params);
        $this->id = $this->getUrlName();
    }
    
    function actionIndex()
    {
        $dps = new DatePeriodSelector($this);

        $types  = array_keys(StatisticEvents::getTypeList());
        $counts = StatEventApi::getInstance()->getEventCountsInDate($dps->date_from, $dps->date_to, $types);
        $max = empty($counts) ? 1 : max($counts);
        $percents = array();

        foreach($counts as $key=>$value)
        {
            $percents[$key] = round($value * 100 / $max);
        }

        $this->view->assign(array(
            'ctrl' => $this,
            'counts' => $counts,
            'percents' => $percents,
            'titles' => StatisticEvents::getTypeList(),
            'period_selector'  => $dps,
            'title'  => t($this->title),
            'block_id' => $this->id
        ));

        return $this->result->setTemplate( 'blocks/sales_funnel.tpl' );
    }


}

