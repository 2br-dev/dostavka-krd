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
 * Выбор периода даты и времени в графике с выпадающим списком группировки по отречкам времени. Для волнового графика.
 */
class DatePeriodSelectorForWaves extends DatePeriodSelector
{
    public $date_group; //Группировка
    public $wave;       //Текущая волна

    public $preset_groups = array(); //Массив диапозонов данных
    public $preset_waves  = array(); //Массив волн
    public $current_preset_wave_title  = '';
    public $current_preset_group_title = '';




    /**
     * Конструктор класса
     * @param AbstractController $controller - контроллер
     * @param null|array $default_filter - массив с истановленными фильтрами
     */
    function __construct(AbstractController $controller, $default_filter = null)
    {
        parent::__construct($controller, $default_filter);
        $this->preset_waves = $controller->waves;

        if($default_filter === null) //Если фильтры не установлены
        {
            //Диапазон по умолчанию - месяц
            $default_filter = array(
                'date_group'  => 'day'
            );
        }

        //Пресет диапозонов группировки данных
        $this->preset_groups = array(
            array(
                'id' => 'day',
                'label' => t('По дням')
            ),
            array(
                'id' => 'week',
                'label' => t('По неделям')
            ),
            array(
                'id' => 'monthname',
                'label' => t('По месяцам')
            ),
            array(
                'id' => 'year',
                'label' => t('По годам')
            )
        );

        $this->date_group = $this->url->request($this->url_id . '_filter_date_group', TYPE_STRING, $default_filter['date_group']);

        // Определение активного пресета
        foreach($this->preset_groups as &$one)
        {
            $one['active'] = ($one['id'] == $this->date_group);
            if ($one['active']) {
                $this->current_preset_group_title = $one['label'];
            }
        }

        $this->wave = $this->url->request($this->url_id . '_filter_wave', TYPE_STRING, $default_filter['wave']);

        // Определение активной волны
        foreach($this->preset_waves as &$one)
        {
            $one['active'] = ($one['id'] == $this->wave);
            if ($one['active']) {
                $this->current_preset_wave_title = $one['label'];
            }
        }

        $cookie_expire = time()+60*60*24*730; //Время хранения куки

        //Запишем в куки
        $this->app->headers->addCookie( $this->url_id. '_filter_date_group', $this->date_group, $cookie_expire, '/');
        $this->app->headers->addCookie( $this->url_id. '_filter_wave', $this->wave, $cookie_expire, '/');
    }
}