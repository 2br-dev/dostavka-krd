<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;

use RS\Config\Loader;
use Shop\Model\Orm\Address;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Order;
use Users\Model\Orm\User;

/**
 * Класс YDelivery нужен для согласования доставки RS и Яндекс
 */
class YDelivery
{
    /**
     * @var Address
     */
    public $address;
    /**
     * @var Order
     */
    public $order;

    protected $config;
    protected $is_booked;

    /**
     * Инитим конструктор заказом в формате RS и адресом в формате RS
     * YDelivery constructor.
     * @param Order $order
     * @param Address $address
     */
    public function __construct(Order $order, Address $address, $is_booked = false)
    {
        $this->order = $order;
        $this->address = $address;
        $this->config = Loader::byModule($this);
        $this->is_booked = $is_booked;
    }

    /**
     * Получем список длступных вариантов доставки от RS, в формате json Яндекса
     * @return json список доставок в json
     */
    public function getJsonObject()
    {
        $obj = $this->getYDelivery($this->getAcceptedDeliveriesList());
        return json_encode($obj);
    }

    /**
     * Получем список длступных вариантов доставки от RS, в формате array Яндекса
     * @return array|bool
     */
    public function getArray()
    {

        return $this->getYDelivery($this->getAcceptedDeliveriesList());
    }

    /**
     * Возвращает $this
     * @return $this
     */
    public function getObject()
    {
        return $this;
    }

    private function isPostDelivery($delivery)
    {
        return in_array($delivery['class'], array('russianpost', 'ems'));
    }

    /**
     * Возвращает преобразованные список Доставок
     * @param array $deliveries_accepted список возможных варианитов доставки
     * @return array список Доставок в формате Яндекса
     * @throws Exception
     */
    public function getYDelivery($deliveries_accepted = array())
    {
        $res = array();
        foreach ($deliveries_accepted as $delivery) {

            if ($this->isPostDelivery($delivery)) {
                $type = "POST";
            } elseif ($delivery->getTypeObject()->isMyselfDelivery()) {
                $type = "PICKUP";
            } else {
                $type = "DELIVERY";
            }

            $price = $this->getDeliveryCost($delivery);

            if ($price === false) {
                //Если расчет прошел некорректно
                Log::write('Error while calculating delivery cost ('.$delivery['id'].'): '.$delivery->getTypeObject()->getErrorsStr());
            } else {
                //Пытаемся получить ПВЗ у способа доставки
                Log::write('[in] Get PVZ list for delivery '.$delivery['id'].' '.$delivery['title']);

                $pvz = $delivery->getTypeObject()->getPvzList($this->order, $this->address);
                if ($pvz === false) {
                    Log::write('[in] PVZ not supported');
                } else {
                    Log::write('[in] found '.count($pvz).' PVZ');
                }

                foreach((array)$pvz as $one_pvz) {

                    if ($one_pvz == false) {
                        $id = $delivery->id;
                        $service_name = $delivery->title;
                    } else {
                        $id = $delivery->id.'x'.$one_pvz->getCode();
                        $service_name = ($one_pvz->getCity() ? $one_pvz->getCity().', ' : '').$one_pvz->getAddress();
                    }

                    $from_days = $this->getPickFromDays($delivery);

                    $item = array(
                        'id' => $id,
                        'serviceName' => mb_substr($service_name, 0, 49),
                        'price' => $price,
                        'type' => $type,
                        'dates' => array(
                            'fromDate' => $this->getPlusDate($from_days),
                        ));

                    $to_days = $this->getPlusDate($this->getPickToDays($delivery));
                    if ($to_days != $item['dates']['fromDate']) {
                        $item['dates']['toDate'] = $this->getPlusDate($this->getPickToDays($delivery));
                    }

                    if ($type == 'PICKUP') {
                        //Если выбран самовывоз, то вернем список складов, из которых возможен самовывоз
                        $warehouse_api = new \Catalog\Model\WareHouseApi();
                        //TODO реализовать более сложную выборку складов, связанную с наличием товаров
                        $warehouse_api->setFilter('yandex_market_point_id', '', '!=');
                        $warehouses = $warehouse_api->getList();
                        $outlets = array();
                        foreach($warehouses as $warehouse) {
                            $outlets[] = array('id' => (int)$warehouse['yandex_market_point_id']);
                        }
                        $item['outlets'] = $outlets;
                        $item['dates']['reservedUntil'] = $this->getPlusDate($from_days + $this->getReserveDays());
                    }

                    $res[] = $item;
                }
            }
        }

        return $res;
    }


    /**
     * @param Delivery $delivery
     * @param Order $order
     * @param Address $address
     * @return null|\Shop\Model\DeliveryType\Helper\DeliveryPeriod возвращает период доставки
     */
    public function getDeliveryPeriod(Delivery $delivery, Order $order, Address $address)
    {
        $period_object = $delivery->getTypeObject()->getDeliveryPeriod($order, $address, $delivery);
        return $period_object;
    }

    /**
     * Получить "от X дней" данные для доставки
     * @return int
     */
    private function getPickFromDays($delivery)
    {
        //Получаем минимальное и максимальное количество дней доставки
        $period = $this->getDeliveryPeriod($delivery, $this->order, $this->address);
        if ($period && $period->getDayMin() != '') {
            $days = $period->getDayMin();
        } else {
            $days = $this->config->min_pickup_days; //Значение по умолчанию
        }

        return $days;
    }

    /**
     * Получить "до X дней" данные для доставки
     * @return int
     */
    private function getPickToDays($delivery)
    {
        //Получаем минимальное и максимальное количество дней доставки
        $period = $this->getDeliveryPeriod($delivery, $this->order, $this->address);
        if ($period && $period->getDayMax() != '' && $period->getDayMin() != $period->getDayMax()) {
            $days = $period->getDayMax();
        } else {
            $days = $this->config->max_pickup_days;
        }

        return $days;
    }

    /**
     * Получить сколько дней резерв, данные для доставки
     * @return int
     */
    private function getReserveDays()
    {
        return $this->config->reserve_until_days;
    }


    /**
     * Хелпер, возвращает дату + int@param дней
     * @param $days_count int
     * @return string сегодня + x дней
     */
    protected function getPlusDate($days_count)
    {
        $date = strtotime("+$days_count day");
        return date('d-m-Y', $date);
    }

    /**
     * Получаем стоимость доставки
     * @param Delivery $delivery
     * @return \Shop\Model\DeliveryType\AbstractType
     * @throws Exception
     */
    public function getDeliveryCost(Delivery $delivery)
    {
        if (empty($this->address)) throw new Exception("Empty address");
        return (float)$delivery->getDeliveryCost($this->order, $this->address);
    }

    /**
     * Получить список активных доставок, разрешенных для YandexMarketCpa
     * и подходящих под условия, установленные у доставок в RS
     * @return array or false
     */
    public function getAcceptedDeliveriesList()
    {
        $user = new User(); //Всегда физическое лицо, при заказе на Маркете. Так как is_company = null

        $delivery_api = new \Shop\Model\DeliveryApi();
        $delivery_api->setFilter('is_use_yandex_market_cpa', 1);
        $accepted_deliveries = $delivery_api->getCheckoutDeliveryList($user, $this->order, false);

        if ($accepted_deliveries) {
            return $accepted_deliveries;
        }
        return array();
    }

    /**
     * Получить варианты доставок
     * @return array
     * @throws \rs\Exception
     */
    private function getDeliveryTypes()
    {
        $delivery_api = new \Shop\Model\DeliveryApi();
        $delivery_api->setFilter('public', 1);
        $delivery_api->setFilter('user_type', array('all'), 'in');
        return $delivery_api->getTypes();
    }
}