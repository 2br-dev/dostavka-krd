<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;

use RS\Event\Manager as EventManager;
use RS\File\Tools as FileTools;
use RS\Helper\CustomView;
use RS\Module\Item as ModuleItem;
use RS\View\Engine as ViewEngine;
use Shop\Model\Orm\Address;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Order;
use Shop\Model\ZoneApi as ShopZoneApi;
use RS\Helper\Log;

/**
* Абстрактный класс типа доставки. Тип доставки содержит в себе функции для расчета стоимости
* в зависимости от различных параметров заказа.
*/
abstract class AbstractType
{
    protected $opt = array();
    
    private $errors = array();  // Ошибки доставки 

    public function loadOptions(array $opt = null)
    {
        $this->opt = $opt;
    }
    
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    abstract public function getTitle();
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    abstract public function getDescription();
    
    /**
    * Возвращает идентификатор данного типа доставки. (только англ. буквы)
    * 
    * @return string
    */
    abstract public function getShortName();


    /**
     * Возвращает дополнительную информацию о доставке (например сроки достави, но можно возвращать еще что-то)
     *
     * @param \Shop\Model\Orm\Order $order
     * @param \Shop\Model\Orm\Address $address - Адрес доставки
     * @param \Shop\Model\Orm\Delivery $delivery - Объект доставки
     *
     * @return string
     * @throws \RS\Event\Exception
     */
    public function getDeliveryExtraText(Order $order, Address $address = null, Delivery $delivery = null)
    {
        if ($period = $this->getDeliveryPeriod($order, $address, $delivery)) {
            $deliveryclass = $delivery->getTypeObject();                         //для работы в других классах доставки необходимо добавить опции
            $to_block = $deliveryclass->getOption('day_apply_delivery_to_block');//	day_apply_delivery и day_apply_delivery_to_block (сейчас только у СДЭКа)

            if($to_block == 1){
                $days_max = $period->getDayMax();
                $day_apply_delivery = $deliveryclass->getOption('day_apply_delivery');
                $days_max = (int)$days_max + (int)$day_apply_delivery;
                $period->setDayMax($days_max);
            }

            return $period->getPeriodAsText();
        }
        return '';
    }

    /**
    * Возвращает текст, в случае если доставка невозможна. false - в случае если доставка возможна
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @return mixed
    */
    public function somethingWrong(Order $order, Address $address = null)
    {
        return false;
    }
    
    /**
    * Функция срабатывает после создания заказа
    * 
    * @param \Shop\Model\Orm\Order $order     - объект заказа
    * @param \Shop\Model\Orm\Address $address - Объект адреса
    * @return void
    */
    public function onOrderCreate(Order $order, Address $address = null)
    {}

    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    public function getFormObject()
    {
        return null;
    }

    /**
     * Возвращает дополнительный HTML для админ части в заказе
     *
     * @return string
     */
    public function getAdminHTML(Order $order)
    {
        return "";
    }
    
    /**
    * Возвращает true, если тип доставки предполагает самовывоз
    * 
    * @return bool
    */
    public function isMyselfDelivery()
    {
        return false;
    }

    /**
     * Действие с запросами к заказу для получения дополнительной информации от доставки
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     */
    public function actionOrderQuery(Order $order)
    {}
    
    /**
    * Производит валидацию текущих данных в свойствах
     *
     * @param \Shop\Model\Orm\Delivery $delivery - объект способа доставки
    */
    public function validate(Delivery $delivery)
    {}

    /**
     * Возвращает значение доп. поля доставки
     *
     * @param string $key - ключ
     * @param mixed $default - значение по умолчанию
     * @return array|mixed|null
     */
    function getOption($key = null, $default = null)
    {
        if ($key == null) return $this->opt;
        return isset($this->opt[$key]) ? $this->opt[$key] : $default;
    }
    
    /**
    * Устанавливает значение доп. поля доставки
    * 
    * @param string $key_or_array
    * @param mixed $value
    */
    function setOption($key_or_array = null, $value = null)
    {
        if (is_array($key_or_array)) {
            $this->opt = $key_or_array + $this->opt;
        } else {
            $this->opt[$key_or_array] = $value;
        }
    }

    /**
     * Возвращает HTML форму данного типа доставки
     *
     * @return string
     * @throws \SmartyException
     */
    function getFormHtml()
    {
        if ($params = $this->getFormObject()) {
            $params->getPropertyIterator()->arrayWrap('data');
            $params->getFromArray((array)$this->opt);
            $params->setFormTemplate(strtolower(str_replace('\\', '_', get_class($this))));
            $module = ModuleItem::nameByObject($this);
            $tpl_folder = \Setup::$PATH.\Setup::$MODULE_FOLDER.'/'.$module.\Setup::$MODULE_TPL_FOLDER;
            
            return $params->getForm(null, null, false, null, '%system%/coreobject/tr_form.tpl', $tpl_folder);
        }
        return '';
    }

    /**
     * Возвращает дополнительный HTML для публичной части
     *
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @param \Shop\Model\Orm\Order|null $order - объект заказа
     * @return string
     */
    function getAddittionalHtml(Delivery $delivery, Order $order = null)
    { 
        return ''; 
    }

    /**
     * Возвращает дополнительный HTML для публичной части для вывода пунктов самовывоза
     *
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @param \Shop\Model\Orm\Order|null $order - объект заказа
     * @param array $pickpoints - массив пунктов самовывоза
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    function getAdditionalHtmlForPickPoints(Delivery $delivery, Order $order = null, $pickpoints = array())
    {
        $view = new ViewEngine();
        $view->assign(array(
            'order'         => $order,
            'extra_info'    => $order->getExtraKeyPair(),
            'delivery'      => $delivery,
            'delivery_type' => $this,
            'pickpoints'    => $pickpoints,
        ) + ModuleItem::getResourceFolders($this));

        return $this->wrapByWidjet($delivery, $order, $view->fetch("%shop%/delivery/pickpoints.tpl"));
    }
    
    /**
    * Возвращает дополнительный HTML для административной части
    * 
    * @return string
    */
    function getAdminAddittionalHtml(Order $order = null)
    { 
        return ''; 
    }

    /**
     * Добавление админского комментария об ошибке работы с доставкой
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param string $text - текст для добавления
     */
    function addToOrderAdminComment($order, $text)
    {
        $order->addExtraInfoLine(t('Ошибки при работе с Доставкой'), $text,null,'errors',Order::EXTRAINFOLINE_TYPE_DELIVERY);
    }

    /**
     * Удаление админского комментария об ошибках в доставке
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     */
    function removeFromOrderDeliveryErrorInfoLine($order)
    {
        $order->removeExtraInfoLine('errors',Order::EXTRAINFOLINE_TYPE_DELIVERY);
    }

    /**
     * Оборачивает в виджет содержимое для вывода
     *
     * @param \Shop\Model\Orm\Delivery $delivery - объект текущей доставки
     * @param \Shop\Model\Orm\Order|null $order - объект текущего заказа
     * @param string $content - содержимое для обёрки
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    public function wrapByWidjet(Delivery $delivery, Order $order = null, $content)
    {
        $view = new ViewEngine();
        $view->assign(array(
            'errors'        => $this->getErrors(),
            'order'           => $order,
            'delivery'        => $delivery,
            'delivery_type'   => $this,
            'wrapped_content' => $content,
        ) + ModuleItem::getResourceFolders($this));

        return $view->fetch("%shop%/delivery/widjet_wrapper.tpl");
    }
    
    /**
    * Возвращает стоимость доставки для заданного заказа. Только число.
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @param bool $use_currency - конвертировать стоимость в валюту заказа
    * @return double
    */
    abstract public function getDeliveryCost(Order $order, Address $address = null, Delivery $delivery, $use_currency = true);
    
    /**
    * Возвращает цену в текстовом формате, т.е. здесь может быть и цена и надпись, например "Бесплатно"
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - объект адреса
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @return string
    */
    public function getDeliveryCostText(Order $order, Address $address = null, Delivery $delivery)
    {
        $cost = $this->getDeliveryCost($order, $address, $delivery);
        $order_currency = $order->getMyCurrency();
        return ($cost > 0) ? CustomView::cost($cost).' '.$order_currency['stitle'] : t('бесплатно');
    }

    /**
     * Рассчитывает структурированную информацию по сроку, который требуется для доставки товара по заданному адресу
     *
     * @param \Shop\Model\Orm\Order $order объект заказа
     * @param \Shop\Model\Orm\Address $address объект адреса
     * @param \Shop\Model\Orm\Delivery $delivery объект доставки
     * @return Helper\DeliveryPeriod | null
     */
    protected function calcDeliveryPeriod(Order $order, Address $address = null, Delivery $delivery = null)
    {
        if (!$delivery) {
            $delivery = $order->getDelivery();
        }
        //Здесь у потомков нужно либо реализовать собственный рассчет сроков доставки,
        //либо вернуть заданные у доставки сроки по-умолчанию

        if (!empty($delivery['delivery_periods'])){
            //Получим все зоны
            $zone_api = new ShopZoneApi();
            $zones    = $zone_api->getZonesByRegionId($address['region_id'], $address['country_id'], $address['city_id']);

            foreach ($delivery['delivery_periods'] as $delivery_period) {
                if ($delivery_period['zone'] == 0 || in_array($delivery_period['zone'], $zones)) {

                    return new Helper\DeliveryPeriod(isset($delivery_period['days_min']) ? $delivery_period['days_min'] : null,
                                                     isset($delivery_period['days_max']) ? $delivery_period['days_max'] : null,
                                                     isset($delivery_period['text']) ? $delivery_period['text'] : null);
                }
            }
        }

        return null;
    }

    /**
     * Возвращает структурированную информацию по сроку, который требуется для доставки товара по заданному адресу.
     * Сторонние модули могут откорректировать информацию о сроках доставки
     *
     * @param \Shop\Model\Orm\Order $order объект заказа
     * @param \Shop\Model\Orm\Address $address объект адреса
     * @param \Shop\Model\Orm\Delivery $delivery объект доставки
     * @return Helper\DeliveryPeriod | null
     * @throws \RS\Event\Exception
     */
    public function getDeliveryPeriod(Order $order, Address $address = null, Delivery $delivery = null)
    {
        $period = $this->calcDeliveryPeriod($order, $address, $delivery);

        $event_result = EventManager::fire('delivery.period.correct', array(
            'delivery_type' => $this,
            'order' => $order,
            'delivery' => $delivery,
            'address' => $address,
            'period' => $period
        ));

        $result = $event_result->getResult();
        return $result['period'];
    }

    /**
     * Применяет наценку или скидку к итоговой цене за доставку
     *
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @param float $cost - цена за доставку
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return int|float
     * @throws \RS\Event\Exception
     */
    public function applyExtraChangeDiscount($delivery, $cost, Order $order = null)
    {
        $plus_cost = 0;
        if (!empty($delivery['extrachange_discount'])){

            switch ($delivery['extrachange_discount_type']) {
                case 0: //Если в еденицах
                    $plus_cost = $delivery['extrachange_discount'];
                    break;
                case 1: //Если в процентах

                    if ($delivery['extrachange_discount_implementation'] == 1){

                        $plus_cost = ceil(($cost * $delivery['extrachange_discount']) / 100);
                        break;

                    }elseif (($delivery['extrachange_discount_implementation'] == 0) && $order){
                        $cart = $order->getCart();
                        $itemscost = $cart->getTotalWithoutDelivery();

                        $plus_cost = ceil(($itemscost * $delivery['extrachange_discount']) / 100);
                        break;

                    }elseif (($delivery['extrachange_discount_implementation'] == 2) && $order){
                        $cart = $order->getCart();
                        $itemscost = $cart->getTotalWithoutDelivery();
                        $total = $itemscost + $cost;
                        $plus_cost = ceil(($total * $delivery['extrachange_discount']) / 100);
                        break;

                    }
            }
       }

        $cost = (float)$cost + $plus_cost;

        //Добавим событие в обработчик
        $eresult = EventManager::fire('deliverytype.applyextrachangediscount', array(
            'cost' => $cost, 
            'delivery' => $delivery,
            'order' => $order
        ));
        $result = $eresult->getResult();
        $cost   = $result['cost'];
        
        return ($cost<0) ? 0 : $cost;
    }

    /**
     * Переводит строку XML в форматированный XML
     *
     * @param string $xml_string - строка XML
     * @return string
     */
    public function toFormatedXML($xml_string)
    {
       $dom = new \DOMDocument('1.0', 'UTF-8');
       $dom->preserveWhiteSpace = false;
       $dom->formatOutput = true;
       $dom->loadXML("\xEF\xBB\xBF" .$xml_string);
       return html_entity_decode($dom->saveXML());
    }
    
    /**
    * Записывает в лог файл информацию
    * 
    * @param string $key - ключевое слово или фраза для записи, будет записана перед информацией в логе
    * @param mixed $value - информация для записи
    */
    public function writeToLog($key, $value = '')
    {
        $log_dir = \Setup::$PATH.\Setup::$STORAGE_DIR.'/logs'; //Папка с логами
        if (!file_exists($log_dir)){
            FileTools::makePath($log_dir);
        }
        $class_name = get_class($this);
        $file = $log_dir."/delivery_".str_replace('\\', '-', $class_name).".log";
        if ($this->log === null) {
            $this->log = Log::file($file);
            $this->log->setMaxLength('104876');
            $this->log->enableDate();
        }
        $text = t("Рассчётный класс:").$class_name."\r\n";
        $text .= $key."\r\n";
        if (!is_string($value)){ //Если не строка, то покажем содержимое
            $text .= var_export($value, true)."\r\n";
        }else{
            $text .= $value."\r\n";
        }

        $this->log->append($text);
    }
    
    /**
    * Возвращает трек номер для отслеживания
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @return boolean
    */
    public function getTrackNumber(Order $order)
    {
        return false;
    }
    
    /**
    * Возвращает ссылку на отслеживание заказа
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * 
    * @return string
    */
    public function getTrackNumberUrl(Order $order)
    {
        return "";
    }
    
    /**
    * Возвращает ошибки в виде строки склеевая символами
    * 
    * @param string $glue - символы для склеивания
    * @return string
    */
    public function getErrorsStr($glue = ", ")
    {
       return implode($glue,$this->errors); 
    }
    
    /**
    * Получает массив ошибок
    * 
    * @return array
    */
    public function getErrors()
    {
       return $this->errors; 
    }
    
    /**
    * Возвращает есть ошибки при работе метода или нет
    * 
    * @return boolean
    */
    public function hasErrors()
    {
       return count($this->errors); 
    }
    
    /**
    * Очищает ошибки доставки
    * 
    */
    public function clearErrors()
    {
       $this->errors = array(); 
    }
    
    /**
    * Добавляет ошибку в массив ошибок
    *  
    * @param string $error_text - текст ошибки
    */
    public function addError($error_text)
    {
        if (!in_array($error_text, $this->errors)){ //Исключим дублирование
            $this->errors[] = $error_text;
        }
    }
    
    /**
    * Возвращает список доступных ПВЗ для переданного заказа
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - объект адреса
    * @return array | bool - если ПВЗ поддерживаются, false - есть ПВЗ не поддерживаются
    */
    public function getPvzList(Order $order, Address $address = null)
    {
        return false;
    }

    /**
     * Возвращает массив фильтров для списка пунктов выдачи
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return \Shop\Model\DeliveryType\Helper\FilterType\AbstractFilter[]
     */
    public function getPvzListFilters(Order $order = null)
    {
        return array();
    }
}
