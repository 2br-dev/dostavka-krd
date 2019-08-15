<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\Providers;
use RS\Html\Paginator\Element as PaginatorElement;

/**
* Абстрактный класс, предоставляющий данные для статистических отчетов
*/
abstract class AbstractDataProvider
{
    protected $date_from;
    protected $date_to;
    protected $site_id;
    /**
     * @var \RS\Html\Filter\Control $filters
     */
    protected $filters;
    /**
     * @var \RS\Html\Table\Element $table
     */
    protected $table;

    /**
     * AbstractDataProvider constructor.
     */
    function __construct()
    {
        $this->setSiteId(\RS\Site\Manager::getSiteId());
    }

    /**
    * Устанавливает начальную дату для выборки данных
    * 
    * @param string $date - дата в формате ДДДД-ММ-ЧЧ
    * @return void
    */
    public function setDateFrom($date)
    {
        $this->date_from = $date;
    }

    /**
    * Устанавливает конечную дату для выборки данных
    * 
    * @param string $date - дата в формате ДДДД-ММ-ЧЧ
    * @return void
    */
    public function setDateTo($date)
    {
        $this->date_to = $date;
    }
    
    /**
    * Устанавливает сайт, для которого необходимо выбрать данные
    * 
    * @param integer $site_id - ID сайта
    * @return void
    */    
    public function setSiteId($site_id)
    {
        $this->site_id = $site_id;
    }

    /**
    * Возвращает объект таблицы для отображения с загруженными данными
    * 
    * @param \RS\Html\Paginator\Element $paginator - объект пагинатора
    * @return \RS\Html\Table\Element
    */
    public function getTable(PaginatorElement $paginator)
    {
        $this->table = $this->getTableStructure();
        $this->table->setData($this->getListData($paginator));
        
        return $this->table;
    }

    /**
     * Устанавливает фильтры по статусу заказа, назначенные в настройках модуля
     *
     * @param \RS\Orm\Request $q - объект запроса
     * @param string $table_alias - алиас для колонки
     * @return \RS\Orm\Request
     * @throws \RS\Exception
     */
    protected function filterOrderByStatus(\RS\Orm\Request $q, $table_alias = 'O')
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);
        
        if (!in_array(0, (array)$config['consider_orders_status'])) {
            $q->whereIn(($table_alias ? $table_alias.'.' : '').'status', (array)$config['consider_orders_status']);
        }
        return $q;
    }

    /**
     * Устанавливает дополнительные фильтры, установленные пользователем
     *
     * @param \RS\Orm\Request $req - объект запроса, для преобразования
     */
    protected function setAdditionalFilters($req)
    {
        $this->filters->modificateQuery($req);
    }

    /**
    * Возвращает сведения, необходимые для построения диаграммы
    * @return array
    */
    abstract public function getPlotData();
    
    /**
    * Возвращает единицу измерения данных в диаграмме
    * 
    * @return string
    */
    abstract public function getPlotUnit();

    /**
    * Возвращает данные для табличного представления постранично
    * 
    * @param \RS\Html\Paginator\Element $paginator - объект пагинатора
    * @return array
    */
    abstract function getListData(PaginatorElement $paginator);


    /**
    * Возвращает общее количество строк для табличных данных
    * @return integer
    */
    abstract function getListTotalCount();
    
    /**
    * Возвращает объект, содержащий структуру отображаемой таблицы
    * @return \RS\Html\Table\Element
    */
    abstract public function getTableStructure();

    /**
     * Возвращает объект, содержащий структуру фильтров таблицы
     * @return \RS\Html\Table\Element
     */
    abstract public function getFilterStructure();
}