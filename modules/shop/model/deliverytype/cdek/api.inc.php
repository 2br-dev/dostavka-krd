<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
/**
 * Created by PhpStorm.
 * User: Пользователь
 * Date: 08.02.2019
 * Time: 15:11
 */

namespace Shop\Model\DeliveryType\Cdek;

class Api
{
    const
        API_URL = "https://integration.cdek.ru/", //Основной URL
        API_URL_CALCULATE = "http://api.cdek.ru/calculator/calculate_price_by_json.php", //URL для калькуляции доставки

        API_CALCULATE_VERSION = "1.0", //Версия API для подсчёта стоимости доставки
        DEVELOPER_KEY = "522d9ea0ad70744c58fd8d9ffae01fc1";// СДЭК попросил добавить дополнительный атрибут к запросу  28.09.2017

    static protected
        $inst = null;

    protected
        $config,
        $log_file,
        $write_log,
        $timeout;

    private static $cache_api_requests = array();

    static public function getInstance()
    {
        if (self::$inst === null) {
            self::$inst = new self(true, 20);
        }
        return self::$inst;
    }

    function __construct($write_log, $timeout = 20)
    {
        $this->config = \RS\Config\Loader::byModule($this);
        $this->log_file = \RS\Helper\Log::file(\Setup::$PATH . \Setup::$STORAGE_DIR . '/logs/delivery_API_CDEK.log');
        $this->log_file->enableDate();
        $this->log_file->setMaxLength(3145728);
        $this->write_log = $write_log;
        $this->timeout = $timeout;

    }

    /**
     * Запрос к серверу рассчета стоимости доставки. Ответ сервера кешируется
     *
     * @param string $script - скрипт
     * @param array $params - массив параметров
     * @param string $method - POST или GET
     * @return mixed
     */
    private function apiRequest($script, $params = array(), $method = "POST")
    {
        if (true) {
            $this->log_file->newLine(1);
            $this->log_file->append('Делаю запрос на обработку');
            $this->log_file->append('Скрипт: ' . $script);
            $this->log_file->append("Параметры: " . var_export($params, true));
            $this->log_file->append("Метод: " . $method);
        }

        if (!empty($params)) { //Если параметры переданы
            ksort($params);
        }
        $cache_key = md5(serialize($params) . $method);

        if (!isset(self::$cache_api_requests[$cache_key])) {

            // Проверка на установленный timeout
            if ($this->timeout < 1 || $this->timeout == null) $this->timeout = 20;

            $requst_array = array(
                'http' => array(
                    'ignore_errors' => true, //Игнорируем ошибки(статусы ошибок) в заголовках, т.к. может быть 500 ошибка, но контент есть
                    'method' => $method,
                    'timeout' => $this->timeout
                )
            );

            if (stripos($method, 'post') !== false) {
                $requst_array['http']['content'] = http_build_query($params);

                $ctx = stream_context_create($requst_array);
                $url = $this->getApiHost() . $script;
            } else {
                $url_params = !empty($params) ? '?' . http_build_query($params) : "";
                $url = $this->getApiHost() . $script . $url_params;
                $ctx = stream_context_create($requst_array);
            }
            $response = @file_get_contents($url, null, $ctx);
            self::$cache_api_requests[$cache_key] = $response;
        }
        if (true) {
            $this->log_file->append('Ответ на запрос: ');
            $this->log_file->append(var_export((self::$cache_api_requests[$cache_key]),true));
        }
        return self::$cache_api_requests[$cache_key];
    }

    /**
     * Получает хост для api
     */
    private function getApiHost()
    {
        return self::API_URL;
    }

    function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    function getTimeout()
    {
        return $this->timeout;
    }

    function setWriteLog($write_log)
    {
        $this->write_log = $write_log;
    }

    function getWriteLog()
    {
        return $this->write_log;
    }

    //Получение списка ПВЗ	https://integration.cdek.ru/pvzlist/v1/xml
    function getPvzList($params, $method = 'GET')
    {
        return $this->apiRequest('pvzlist/v1/xml',$params,$method);
    }

    //Заказ от ИМ https://integration.cdek.ru/new_orders.php
    function createNewOrder($xml)
    {
        return $this->apiRequest("new_orders.php",array(
            'xml_request' => $xml
        ));
    }

    //Заказ на доставку https://integration.cdek.ru/addDelivery https://integration.cdek.ru/addDeliveryRaw (для передачи контента в теле запроса)
    function createAddDelivery()
    {

    }

    //Изменение заказа http://integration.cdek.ru/update http://integration.cdek.ru/updateRaw (для передачи контента в теле запроса)
    function updateOrder($xml)
    {

    }

    //Удаление заказа https://integration.cdek.ru/delete_orders.php
    function deleteOrder($xml)
    {
        return $this->apiRequest("delete_orders.php",array(
            'xml_request' => $xml
        ));
    }

    //Печать квитанции к заказу	https://integration.cdek.ru/orders_print.php
    function getOrdersPrintDocument($xml)
    {

    }

    //Регистрация заявки на  вызова курьера https://integration.cdek.ru/call_courier.php
    function createOrderCallCourier($xml)
    {
        return $this->apiRequest("call_courier.php",array(
            'xml_request' => $xml
        ));
    }

    //Регистрация информации о результате прозвона https://integration.cdek.ru/new_schedule.php


    //Печать ШК-мест https://integration.cdek.ru/ordersPackagesPrint https://integration.cdek.ru/ordersPackagesPrintRaw (для передачи контента в теле запроса)
    function createOrdersPackagesPrint($xml)
    {

    }

    //Создание преалерта https://integration.cdek.ru/addPreAlert https://integration.cdek.ru/addPreAlertRaw (для передачи контента в теле запроса)


    //Отчет "Статусы заказов"	https://integration.cdek.ru/status_report_h.php
    function getOrderStatusReport($xml)
    {
        return $this->apiRequest("status_report_h.php",array(
            'xml_request' => $xml
        ));
    }

    //Отчет "Информация по заказам"	https://integration.cdek.ru/info_report.php
    function getOrderInfoRequest($xml)
    {
        return $this->apiRequest("info_report.php",array(
            'xml_request' => $xml
        ));
    }

    //Список субъектов РФ integration.cdek.ru/v1/location/regions
    function getLocationRegions($params, $method = 'GET', $decode = true)
    {
        if($decode){
            return json_decode($this->apiRequest('v1/location/regions/json',$params,$method));
        }else{
            return $this->apiRequest('v1/location/regions/json',$params,$method);
        }

    }

    //Список городов integration.cdek.ru/v1/location/cities
    function getLocationsCities($params, $method = 'GET', $decode = true)
    {
        if($decode){
            return json_decode($this->apiRequest('v1/location/cities/json',$params,$method));
        }else{
            return $this->apiRequest('v1/location/cities/json',$params,$method);
        }
    }
}