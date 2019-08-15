<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\Components;
use RS\Controller\AbstractController;

/**
 * Выбор периода даты и времени в графике
 */
class DatePeriodSelector extends \RS\Controller\Admin\Block
{

    public $id;
    public $url_id;
    /**
     * @var \RS\Controller\Admin\Block $controller
     */
    public $controller;
    public $date_from;  //От
    public $date_to;    //До
    public $presets = array();
    public $current_preset_title = '';

    //Параметры по умолчанию
    public $default_params = array(
        'indexTemplate' => '%statistic%/components/date_period_selector.tpl' //Шаблон
    );

    /**
     * Субфункция - получения даты в текстовом формате Y-m-d
     *
     * @param string $string - строковое представление даты
     * @return false|string
     */
    function formatDate($string)
    {
        return date('Y-m-d', strtotime($string));
    }

    function __construct(AbstractController $controller, $default_filter = null)
    {
        parent::__construct();
        /**
         * @var \RS\Controller\Admin\Block $controller
         */
        $this->controller = $controller;
        $this->id         = $this->controller->getUrlName();

        if($default_filter === null || (!isset($default_filter['date_from']) && !isset($default_filter['date_from']))) //Если фильтры не установлены
        {
            //Диапазон по умолчанию - месяц
            $default_filter = array(
                'date_from'  => 'month',
                'date_to'    => 'month'
            );
        }

        //Флаг является ли этот блок виджетом для показа
        $is_widget = $this->controller->getParam('widget', false);

        //Дополнительный идентификатор адреса для обновления
        $this->url_id = ($is_widget ? $this->id : 'common');

        $this->date_from = $this->url->request($this->url_id . '_filter_date_from', TYPE_STRING, $default_filter['date_from']);
        $this->date_to   = $this->url->request($this->url_id . '_filter_date_to', TYPE_STRING, $default_filter['date_to']);

        $cookie_expire = time()+60*60*24*730; //Время хранения куки

        //Запишем в куки
        $this->app->headers
            ->addCookie( $this->url_id. '_filter_date_from', $this->date_from, $cookie_expire, '/')
            ->addCookie( $this->url_id . '_filter_date_to', $this->date_to, $cookie_expire, '/');

        //Пресет диапозонов временного интервала
        $this->presets = array(
            array(
                'id' => 'day',
                'label' => t('День'),
                'from' => $this->formatDate('now'),
                'to' => $this->formatDate('now'),
            ),
            array(
                'id' => 'week',
                'label' => t('Неделя'),
                'from' => $this->formatDate('-6 day'),
                'to' => $this->formatDate('now'),
            ),
            array(
                'id' => 'month',
                'label' => t('Месяц'),
                'from' => $this->formatDate('-1 month + 1 day'),
                'to' => $this->formatDate('now'),
            ),
            array(
                'id' => 'year',
                'label' => t('Год'),
                'from' => $this->formatDate('-1 year + 1 day'),
                'to' => $this->formatDate('now'),
            ),
        );

        // Определение активного пресета
        // Наименование пресета
        $this->current_preset_title = t('Диапазон');

        foreach($this->presets as &$one)
        {
            if ($this->date_from == $one['id']) {
                $this->date_from = $one['from'];
                $this->date_to   = $one['to'];
            }
            
            $one['active'] = (($one['from'] == $this->date_from) && ($one['to'] == $this->date_to));
            if ($one['active']) {
                $this->current_preset_title = $one['label'];
            }
        }
    }

    /**
     * Рендеринг блока
     *
     * @return string
     */
    function render()
    {
        $view = new \RS\View\Engine();
        $view->assign(array(
            'cmp' => $this,
            'controller' => $this->controller,
        ));

        return $view->fetch($this->default_params['indexTemplate']);
    }

    /**
     * Возвращает данную предыдущего ОТ периода
     * @return false|string
     */
    function getPrevDateFrom()
    {
        $ts_from = strtotime($this->date_from);
        $ts_to   = strtotime($this->date_to);
        $delta   = $ts_to - $ts_from;
        $ret     = date('Y-m-d', $ts_from - 86400 - $delta);
        return $ret;
    }

    /**
     * Возвращает данную предыдущего ДО периода
     * @return false|string
     */
    function getPrevDateTo()
    {
        return date('Y-m-d', strtotime($this->date_from.' - 1 day'));
    }
}