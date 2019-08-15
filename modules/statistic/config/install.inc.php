<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Config;

/**
* Класс отвечает за установку и обновление модуля
*/
class Install extends \RS\Module\AbstractInstall
{
    function install()
    {
        if ($result = parent::install()) {
            //Добавляем виджеты на рабочий стол
            $widget_api = new \Main\Model\Widgets();
            $widget_api->setUserId(1);
            $widget_api->insertWidget('statistic-widget-keyindicators', 2);
            $widget_api->insertWidget('statistic-widget-bestsellers', 2);
            $widget_api->insertWidget('statistic-widget-salesfunnel', 3);
        }

        return $result;
    }


    /**
     * Добавляет демонстрационные данные
     *
     * @param array $params - произвольные параметры.
     * @return boolean|array
     */
    function insertDemoData($params = array())
    {
        return $this->importCsvFiles(array(
            array('\Statistic\Model\CsvSchema\SourceTypes', 'sourcetype'),
        ), 'utf-8', $params);
    }

    /**
     * Возвращает true, если модуль может вставить демонстрационные данные
     *
     * @return bool
     */
    function canInsertDemoData()
    {
        return true;
    }
}
