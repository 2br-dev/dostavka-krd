<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Block;

use Statistic\Model\Components\DatePeriodSelector;
use Statistic\Model\Providers\AbstractDataProvider;

/**
 * Абстрактный класс для формирования круговой диаграммы
 */
abstract class AbstractPlotWithList extends \RS\Controller\Admin\Block
{

    protected $id;
    protected $title        = '';
    protected $page_size    = 20;
    protected $action_var   = 'pwl';
    /**
     * @var AbstractDataProvider
     */
    protected $data_provider;


    /**
     * Инициализация блока
     */
    public function init()
    {
        parent::init();
        $this->id = $this->getUrlName();
    }

    /**
    * Отображет круговую диаграмму со списком
    */
    function actionIndex()
    {
        $page      = $this->url->request($this->id . '_page', TYPE_INTEGER, 1);
        $page_size = $this->url->request($this->id . '_page_size', TYPE_INTEGER, $this->page_size);
        $section   = $this->url->request('section', TYPE_STRING, null); //Текущая секция
        $f         = $this->url->request('f', TYPE_ARRAY, null); //Фильтры

        $cookie_expire = time()+60*60*24*730;
        $this->app->headers->addCookie($this->id . '_page_size', $page_size, $cookie_expire, '/');

        $dps = new DatePeriodSelector($this);
        
        $this->data_provider->setDateFrom($dps->date_from);
        $this->data_provider->setDateTo($dps->date_to);

        //Пагинация
        $total = $this->data_provider->getListTotalCount();

        $url_pattern = $this->router->getAdminPattern(null, array(
            'section' => $section,
            'f' => $f,
            ":{$this->id}_page" => '%PAGE%',
            $this->id . '_page_size' => $page_size,
            $this->id . '_filter_date_from' => $dps->date_from, 
            $this->id . '_filter_date_to' => $dps->date_to
        ), 'statistic-dashboard');


        $paginator = new \RS\Html\Paginator\Element($total, $url_pattern);
        $paginator->setTotal($total);
        $paginator->setPageKey($this->id . '_page');
        $paginator->setPageSizeKey($this->id . '_page_size');
        $paginator->setUpdateContainer('#updatableDashboard');
        
        $paginator->setPageSize($page_size);        
        $paginator->setPage($page);

        $this->view->assign(array(
            'paginator' => $paginator,
            'json_data' => json_encode($this->data_provider->getPlotData()),
            'json_unit' => json_encode($this->data_provider->getPlotUnit()),
            'block_id'  => $this->id,
            'title'  => t($this->title),
            'data_provider' => $this->data_provider,
            'period_selector' => $dps,
            'total' => $total
        ));

        return $this->result->setTemplate('blocks/plot_with_list.tpl');
    }


}
