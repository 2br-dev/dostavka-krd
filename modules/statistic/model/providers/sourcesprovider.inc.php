<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\Providers;

use RS\Html\Paginator\Element;
use RS\Html\Table\Element as TableElement;
use RS\Html\Table\Type as TableType;
use RS\Html\Filter;
use Shop\Model\Orm\Order;
use Statistic\Model\Orm\Source;
use Statistic\Model\Orm\SourceType;
use Users\Model\Orm\User;
use Statistic\Model\UnitColumn;

class SourcesProvider extends AbstractDataProvider
{

    /**
     * Количество элементов, отобржаемых в круговой диаграмме.
     * К ним добавляется лишь еще один элемент - "Остальное"
     *
     * @var int
     */
    private $items_in_plot = 6;


    /**
     * Возвращает запрос для отображения данных
     *
     * @return \RS\Orm\Request
     */
    private function makeQueryObj()
    {
        $req = \RS\Orm\Request::make()
                    ->from(new SourceType(), 'SOURCE_TYPE')
                    ->select('
                        SOURCE_TYPE.title as display_name, 
                        COUNT(SOURCE.id) as summary
                    ')
                    ->leftjoin(new Source(), 'SOURCE.source_type = SOURCE_TYPE.id', 'SOURCE')
                    ->join(new User(), 'USER.source_id = SOURCE.id', 'USER')
                    ->where("SOURCE.dateof >= '#date_from'", array('date_from' => $this->date_from))
                    ->where("SOURCE.dateof <= '#date_to'", array('date_to' => $this->date_to.' 23:59:59'))
                    ->where(array(
                        'SOURCE.site_id' => $this->site_id,
                    ))
                    ->groupby('SOURCE_TYPE.title')
                    ->orderby('summary desc');

        $this->setAdditionalFilters($req);
        
        return $req;
    }

    /**
     * Возвращает массив данных для оторажения долей
     *
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function getPlotData()
    {

        $req = $this->makeQueryObj();
        $req->limit($this->items_in_plot);
        $rows = $req->exec()->fetchAll();

        $items = array_map(function ($row) {
            return array(
                'data' => (int)$row['summary'],
                'label' => $row['display_name']
            );
        }, $rows);

        $total_count = $this->getListTotalCount();
        
        $data = array();
        foreach($items as $item) {
            $data[] = $item['data'];
        }
        
        $displayed_total_summary = array_sum($data);

        // Считаем "Остальные"
        if ($total_count && $total_count - $this->items_in_plot > 0)
        {
            $items[] = array(
                'data' => (int)$this->getTotalSummary() - (int)$displayed_total_summary,
                'label' => t('Остальные'),
            );
        }

        return $items;
    }
    
    /**
    * Возвращает единицу измерения данных в диаграмме
    * 
    * @return string
    */
    public function getPlotUnit()
    {
        return \Catalog\Model\CurrencyApi::getBaseCurrency()->stitle;
    }

    /**
     * Возвращает общую сумму всех оплаченных заказов (за данный период)
     * Используется для рассчета "Остальных" в круговой диаграмме
     *
     * @return float
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    private function getTotalSummary()
    {
        $req = \RS\Orm\Request::make()->from(new Source(), 'SOURCE');
        $req->select('COUNT(SOURCE.id) as total');
        $req->join(new User(), 'USER.source_id = SOURCE.id', 'USER');
        $req->where("SOURCE.dateof >= '#date_from'", array('date_from' => $this->date_from));
        $req->where("SOURCE.dateof <= '#date_to'", array('date_to' => $this->date_to.' 23:59:59'));
        $req->where(array(
            'SOURCE.site_id' => $this->site_id
        ));
        
        $this->filterOrderByStatus($req, 'ORDERS');
        $result = $req->exec()->fetchSelected(null, 'total');
        return $result[0];
    }

    /**
     * Возвращает данных отфильтрованные по странице
     *
     * @param Element $paginator - объект пагинатора
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function getListData(Element $paginator)
    {
        $req = $this->makeQueryObj();
        $req->limit($paginator->page_size);
        $req->offset(($paginator->page - 1) * $paginator->page_size);
        $data = $req->exec()->fetchAll();
        return $data;
    }

    /**
     * Возвращает общее количество результата запроса
     *
     * @return int
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function getListTotalCount()
    {
        $req = $this->makeQueryObj();
        $count = $req->exec()->rowCount();
        return $count;
    }

    /**
     * Возвращает структуру для отображения таблицы
     *
     * @return TableElement
     */
    public function getTableStructure()
    {
        $router = \RS\Router\Manager::obj();
        $table = new TableElement(array(
            'Columns' => array(
                new TableType\Text('display_name', t('Источник')),
                new UnitColumn('summary', t('Количество')),
            )
        ));
        return $table;
    }

    /**
     * Возвращает структуру фильтров для таблицы
     *
     * @return Filter\Control
     */
    public function getFilterStructure()
    {
        $this->filters = new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('items' => array(
                        new \Statistic\Model\HtmlFilterType\UserFilter('user_id', t('Зарегистрированный покупатель'), array( 'PrefixField' => 'USERS')),
                        new \Statistic\Model\HtmlFilterType\UserFilterText('user_fio', t('Покупатель без регистрации'), array( 'SearchType' => '%like%')),
                        new \Statistic\Model\HtmlFilterType\SumFilter('summary', t('Общая сумма'), array('Attr' => array('class' => 'w60'), 'showType' => true)),
                        new \Statistic\Model\HtmlFilterType\SumFilter('profit', t('Общий доход'), array('Attr' => array('class' => 'w60'), 'showType' => true)),
                    )))
                )
            )),
            'Caption' => t('Поиск'),
            'UpdateContainer' => '#updatableDashboard'
        ));
        return $this->filters;
    }
}