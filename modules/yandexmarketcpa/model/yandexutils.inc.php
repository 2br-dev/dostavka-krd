<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;

use Shop\Model\Orm\Payment;
use Shop\Model\Orm\UserStatus;
use Shop\Model\UserStatusApi;
use \YandexMarketCpa\Model\YDelivery;
use \YandexMarketCpa\Model\YItems;

/**
 * Класс содержит методы для работы с Заказами на Яндексе
 */
class YandexUtils extends \RS\Module\AbstractModel\BaseModel
{
    /**
     * @var \Catalog\Model\Api
     */
    public $apiProduct;
    /**
     * @var \Shop\Model\Orm\Order
     */
    public $order;
    /**
     * @var \Shop\Model\OrderApi
     */
    public $order_api;
    /**
     * @var \Shop\Model\Orm\Address
     */
    public $address;
    /**
     * @var mixed
     */
    private $yandex_cart_object;

    private $log;

    private $site_id;

    /**
     * Yutils constructor.
     */
    public function __construct()
    {
        $this->apiProduct = new \Catalog\Model\Api();
        $this->order = \Shop\Model\Orm\Order::currentOrder();
        $this->order_api = new \Shop\Model\OrderApi();
        $this->yandex_cart_object = $this->parseJsonRequest();
        $this->site_id = \RS\Site\Manager::getSiteId();
        $this->config = \RS\Config\Loader::byModule($this);
    }

    /**
     * Создать заказ
     *
     * @param array $items ид итемсов
     * @param array $userInfo инфа о пользователе
     * @param array $addr адрес в формате rs
     */
    public function createOrder($items = array(), $userInfo = array(), $addr = array(), $virtual = true)
    {
        $this->order->clear();
        $cart = \Shop\Model\Cart::preOrderCart($this->order);
        $cart->clean();
        foreach ($items as $id => $data) {
            $cart->addProduct($data['productId'], $data['count'], $data['offerId']);
        }
        $this->order->linkSessionCart($cart);
        $this->order->setCurrency(\Catalog\Model\CurrencyApi::getCurrentCurrency());
        $this->order['ip'] = $_SERVER['REMOTE_ADDR'];

        if (isset($this->yandex_cart_object['order']['delivery']['outlet'])) {
            $warehouse = \Catalog\Model\Orm\WareHouse::loadByWhere(array(
                'yandex_market_point_id' => $this->yandex_cart_object['order']['delivery']['outlet']
            ));
            if ($warehouse['id']) {
                $this->order['warehouse'] = $warehouse['id'];
            }
        }

        $this->order['user_type'] == 'noregister';

        $this->address = new \Shop\Model\Orm\Address();
        $this->address->getFromArray($addr);

        if ($this->address->insert()) {
            $this->order['use_addr'] = $this->address['id'];
            $this->order->address = $this->address;
        }

        //Создаем заказ всегда в статусе - в резерве
        $status_id = YandexReference::findRsStatusByYandexId(YandexReference::YANDEX_STATUS_RESERVED);
        $this->order['status'] = $status_id;
        if (isset($this->yandex_cart_object['order']['notes'])) {
            $this->order['comments'] = $this->yandex_cart_object['order']['notes'];
        }

        $this->order['is_payed'] = 0;
        if (!empty($this->yandex_cart_object['order']['id'])) {
            // $this->order['order_num'] = $this->yandex_cart_object['order']['id'];
            $this->order['id_yandex_market_cpa_order'] = $this->yandex_cart_object['order']['id'];
        }
        if (!empty($this->yandex_cart_object['order'])) {
            $payment = $this->getPaymentMethod();
        }
        if (!empty($payment) and $payment !== false) {
            $this->order['payment'] = $payment;
        }
        if (!$virtual) {
            //$this->order['delivery'] = $this->getOrderDeliveryId();

            //Заказ создается без доставки.
            //Доставка будет записана на этапе обновления заказа, когда будет известен пользователь
            if (!$this->order->insert()) {
                Log::write('Error creating order. Reason:' . $this->order->getErrorsStr());
                return false;
            }
        }
        return true;
    }


    /**
     * Получаем id метода доставки из заказа
     * @return int
     */
    private function getOrderDeliveryId()
    {
        return $this->yandex_cart_object['order']['delivery']['id'];
    }

    /**
     * Получаем сумму доставки из заказа
     * @return int
     */
    private function getOrderDeliveryPrice()
    {
        return $this->yandex_cart_object['order']['delivery']['price'];
    }

    /**
     * Возвращает субъект федерации
     *
     * @param $y_region
     * @return string | null
     */
    private function getSubjectFederation($y_region)
    {
        if ($y_region['type'] == 'SUBJECT_FEDERATION') {
            return $y_region['name'];
        } elseif (isset($y_region['parent'])) {
            return $this->getSubjectFederation($y_region['parent']);
        }
    }

    /**
     * Возвращает город
     *
     * @param array $y_region
     * @return string | null
     */
    private function getCity($y_region)
    {
        if ($y_region['type'] == 'CITY') {
            return $y_region['name'];
        } elseif (isset($y_region['parent'])) {
            return $this->getCity($y_region['parent']);
        }
    }


    /**
     * Преобразование адреса из формата Y в формат RS по индексу
     * @param array $y_address массив с полями адреса в формате Яндекса (может быть пустой массив)
     * @param array $y_region массив с полями региона в формате Яндекса - обязательный аргумент
     * @return array Массив с полями адреса в формате RS
     * @throws \RS\Orm\Exception
     */
    public function parseAddress($y_region, $y_address = array())
    {
        $y_address = $y_address ?: array();
        //Устанавливаем значения по умолчанию

        if ($y_address && isset($y_address['postcode'])) {
            //Приоритетно ищем город по индексу
            $city = \Shop\Model\Orm\Region::loadByWhere(array(
                'site_id' => $this->site_id,
                'zipcode' => $y_address['postcode'],
                'is_city' => 1
            ));
        }

        if (!isset($city) || !$city['id']) {
            //Ищем город по названию
            $city = \Shop\Model\Orm\Region::loadByWhere(array(
                'site_id' => $this->site_id,
                'title' => $this->getCity($y_region),
                'is_city' => 1
            ));
        }

        //Если город найден, то строим путь до страны опираясь на найденный город
        if ($city['id']) {
            $region = $city->getParent();
            $country = $city->getParent()->getParent();
        } else {

            Log::write('[in] City not found');

            if ($this->config->ignore_city_unexists) {
                //Создаем адрес, который не связан со справочниками ReadyScript
                //По такому заказу будет невозможно определить зону.
                //Это допустимо, если доставки не зависят от зон
                $city['title'] = $y_address['city'];

                $region = array(
                    'id' => '',
                    'title' => $this->getSubjectFederation($y_region)
                );
                $country = array(
                    'id' => '',
                    'title' => $y_address['country']
                );
            } else {
                new Exception('City not found');
            }
        }

        $address = array(
            'country' => $country['title'],
            'country_id' => $country['id'],

            'region' => $region['title'],
            'region_id' => $region['id'],

            'city' => $city['title'],
            'city_id' => $city['id'],

            'zipcode' => @$y_address['postcode'] ?: $city['zipcode'],
            'address' => @$y_address['street'],

            'house' => @$y_address['house'],
            'block' => @$y_address['block'],
            'apartment' => @$y_address['apartment'],
            'entrance' => @$y_address['entrance'],
            'entryphone' => @$y_address['entryphone'],
            'floor' => @$y_address['floor'],
            'subway' => @$y_address['subway'],
        );

        return $address;
    }

    /**
     * получаем реквест яндекса
     * @return array Массив с данными от Яндекса
     */
    public function parseJsonRequest()
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }

    /**
     * Получить адрес в орфмате RS
     * @return array Массив с адресом от Яндекса
     * @throws \Yandexmarketcpa\model\Exception
     */
    public function getAddress($scope = 'cart')
    {
        if (!empty($this->yandex_cart_object[$scope])) {
            $y_address = isset($this->yandex_cart_object[$scope]['delivery']['address']) ?
                $this->yandex_cart_object[$scope]['delivery']['address'] : array();
            $y_region = $this->yandex_cart_object[$scope]['delivery']['region'];
        } else {
            throw  new  Exception('empty cart object');
        }

        return $this->parseAddress($y_region, $y_address);
    }

    /**
     * Создать корзину
     * @param array $items массив товар => цена
     * @return object Корзина в формате RS
     */
    public function createCart($items = array())
    {
        $cart = \Shop\Model\Cart::currentCart();
        foreach ($items as $id => $amount) {
            $cart->addProduct($id, $amount);
        }
        return $cart;
    }

    /**
     * Устанавливает валюту, указанную в запросе от Яндекса в качестве текущей валюты
     */
    public function setCurrency($scope)
    {
        $curr_id = $this->yandex_cart_object[$scope]['currency'];
        \Catalog\Model\CurrencyApi::setCurrentCurrency($curr_id, false);
    }

    /**
     * Возвращает true, если заказ был сделан по программе Забронировать на Яндексе
     *
     * @return bool
     */
    public function isBooked()
    {
        return !empty($this->yandex_cart_object['cart']['isBooked']);
    }

    /**
     * Создать объект ответа на ответ запроса корзины в формате Y
     * @return array Корзина в формате Яндекса
     */
    public function buildYCart()
    {
        $this->setCurrency('cart');
        $yItems = new YItems('cart', $this->yandex_cart_object);
        $this->createOrder($yItems->getParsedItems('cart'), array(), $this->getAddress('cart'), true);
        $yDelivery = new YDelivery($this->order, $this->address, $this->isBooked());

        $delivery_options = $yDelivery->getArray();

        $res = array(
            "cart" => array(
                "deliveryOptions" => $delivery_options,
                'items' => $yItems->getItemsArray(),
                'paymentMethods' => $this->getPaymentMethods($delivery_options)
            )
        );

        return $res;
    }

    public function getOrderAddress()
    {
        if (!empty($this->yandex_cart_object['order'])) {

            return (string)implode(',', $this->yandex_cart_object['order']['delivery']['address']);
        } else return false;
    }

    /**
     * Ответ Яндексу о том что заказ принят в обработку и все ок
     * @param bool $accepted если тру то принят если не принят то false
     * @return array Ack ответ яндексу
     * @throws Exception
     */
    public function buildYAccept()
    {
        $this->setCurrency('order');
        $yItems = new YItems('order', $this->yandex_cart_object);
        $accepted = $this->createOrder($yItems->getParsedItems('order'), array(), $this->getAddress('order'), false);

        if ($accepted) {
            $res = array(
                "order" => array(
                    "accepted" => true,
                    'id' => (string)$this->order->id
                )
            );
        } else {
            $res = array(
                "order" => array(
                    "accepted" => false,
                    'reason' => 'OUT_OF_DATE'
                )
            );
        }

        return $res;
    }


    /**
     * Получаем из конфигов маодуля какие методы оплаты возможны,
     * если все ок отдаем их вместе с корзиной яндексу
     *
     * @return array Методы оплаты
     * @throws Exception
     */
    private function getPaymentMethods($delivery_options)
    {
        $payment_methods = array();

        if ($this->canUseWithDelivery($this->config['payment_cash_on_delivery'], $delivery_options) ) {
            $payment_methods[] = 'CASH_ON_DELIVERY';
        }

        if ($this->canUseWithDelivery($this->config['payment_card_on_delivery'], $delivery_options)) {
            $payment_methods[] = 'CARD_ON_DELIVERY';
        }

        if ($this->canUseWithDelivery($this->config['payment_generic'], $delivery_options)) {
            $payment_methods[] = 'YANDEX';
        }

        if (count($payment_methods) > 0) {
            return $payment_methods;
        } else {
            throw new Exception('Declaration of yandex payment methods not found in config');
        }

    }

    /**
     * Возвращает true, если оплата задана и оплату можно использовать совместно с доставкой
     *
     * @param integer $payment_id
     * @param array $delivery_options
     * @return bool
     */
    private function canUseWithDelivery($payment_id, $delivery_options)
    {
        if (empty($payment_id)) return false;

        $payment = new Payment($payment_id);
        foreach($delivery_options as $delivery) {
            if (!$payment->delivery
                || in_array(0, (array)$payment->delivery)  //Если разрешено со всеми доставками
                || in_array($delivery['id'], (array)$payment->delivery)) {
                //Если доставка присутствует в списке
                return true;
            }
        }
        return false;
    }

    /**
     * Получить список  методов оплаты от Яндекса
     * @return возвращает false если нет доступных методов или список id доступных для оплаты методов
     * @throws Exception
     */
    private function getPaymentMethod()
    {
        $config = \RS\Config\Loader::byModule($this);
        $pm = isset($this->yandex_cart_object['order']['paymentMethod']) ?
            $this->yandex_cart_object['order']['paymentMethod'] : false;

        if ($pm === 'CARD_ON_DELIVERY') {
            return $config['payment_card_on_delivery'];
        } elseif ($pm === 'CASH_ON_DELIVERY') {
            return $config['payment_cash_on_delivery'];
        } elseif ($pm === 'YANDEX') {
            return $config['payment_generic'];
        } else {
            return false;
        }
    }


    /**
     * Парсим данные покупателя, полученные от яндекса
     * @return array Пользовательские данные
     */
    private function getBuyer()
    {
        if (!empty($this->yandex_cart_object['order']['buyer'])) {
            return $this->yandex_cart_object['order']['buyer'];
        } else return false;
    }

    /**
     * Парсим тип оплаты из order Яндекса
     * @return string метод оплаты
     */
    private function getPaymentType()
    {
        return $this->yandex_cart_object['order']['paymentType'];
    }


    /**
     * Получаем статус заказа от Яндекса
     * @return array
     */
    public function getOrderStatus()
    {
        return $this->yandex_cart_object['order']['status'];
    }

    /**
     * Получеме id заказа от Яндекса
     * @return int id заказа от Яндекса
     */
    public function getYOrderId()
    {
        return $this->yandex_cart_object['order']['id'];
    }

    /**
     * Возвращает id юзера либо найденого,либо созданного
     * @return id юзера
     */
    public function getOrCreateUserId()
    {
        $user_info = $this->getBuyer();
        if ($user_info === false) {
            return false;
        }

        //Пытаемся найти пользователя
        $user = \RS\Orm\Request::make()
            ->from(new \Users\Model\Orm\User)
            ->where(array(
                'e_mail' => $user_info['email'],
                'phone' => $user_info['phone']
            ), null, 'AND', 'OR')
            ->object();

        if (!$user) {
            //Создаем пользователя
            $user = new \Users\Model\Orm\User();
            $user->no_validate_userfields = true;
            $user->setCheckFields(array()); //Отключаем валидацию обязательных полей

            if (!empty($user_info['firstName'])) {
                $user['name'] = $user_info['firstName'];
            }
            if (!empty($user_info['lastName'])) {
                $user['surname'] = $user_info['lastName'];
            }
            if (!empty($user_info['middleName'])) {
                $user['midname'] = $user_info['middleName'];
            }
            $user['login'] = $user_info['email'];
            $user['e_mail'] = $user_info['email'];
            $user['changepass'] = 1;
            $user['openpass'] = \RS\Helper\Tools::generatePassword(8);

            if ($user->insert()) {
                Log::write('User created. ID:'.$user['id']);
            } else {
                Log::write('User create fail. Error:'.$user->getErrorsStr());
            }
        } else {
            Log::write('User found. ID:'.$user['id']);
        }

        //Обновляем телефон, если он ранее не был задан

        if (!$user['phone'] && $user_info['phone']) {
            $user['phone'] = $user_info['phone'];
            $user->setCheckFields(array()); //Отключим чекеры
            $user->no_validate_userfields = true;
            $user->update();
        }

        return $user['id'];
    }

    /**
     * Прикрепляем юзера к закзу
     * @param $order_id  id заказа
     * @param $user_id  id юзера
     * @return bool заехало или нет
     * @throws \RS\Orm\Exception
     */
    public function updateOrder($y_order_id, $y_status_id, $user_id)
    {
        $order = \Shop\Model\Orm\Order::loadByWhere(array(
            'id_yandex_market_cpa_order' => $y_order_id
        ));

        if ($order['id']) {
            $order->is_yandex_initiated = true;
            $order->user_id = $user_id;
            $order->status = YandexReference::findRsStatusByYandexId($y_status_id);

            //Привязываем адрес к пользователю, если это не было сделано ранее.
            $this->linkAddressToUser($order, $user_id);

            if (!$order['delivery']) {
                //Привязываем доставку
                if (!$this->parseAndFillDelivery($order)) {
                    return $this->addError(t('Не удалось привязать доставку к заказу'));
                }
            }

            $result = $order->update();
            if (!$result) {
                Log::write('Error while updating: ' . $order->getErrorsStr());
                $this->importErrors($order->exportErrors());
                return false;
            }

            return true;
        }

        return $this->addError(t('Заказ %0 не найден', array($y_order_id)));
    }

    /**
     * Парсит и заполняет доставку у заказа
     *
     * @param $order
     */
    public function parseAndFillDelivery($order)
    {

        $id = $this->getOrderDeliveryId();

        if (preg_match('/^(\d+)x(.*)$/u', $id, $match)) {
            $delivery_id = $match[1];
            $pvz_id = $match[2];
        } else {
            $delivery_id = $id;
            $pvz_id = false;
        }

        $delivery = new \Shop\Model\Orm\Delivery($id);

        if ($delivery['id']) {
            Log::write('Found delivery '.$delivery['id'].' '.$delivery['title']);
            if ($pvz_id) {

                Log::write('Find PVZ...');
                $pvz_list = $delivery->getTypeObject()->getPvzList($order);

                if (!$pvz_list) { //Если false или пустой список
                    return $this->addError(t('Не удалось загрузить список ПВЗ'));
                }

                $found_pvz = false;
                foreach($pvz_list as $pvz) {
                    if ($pvz->getCode() == $pvz_id) {
                        $found_pvz = $pvz;
                        break;
                    }
                }

                if (!$found_pvz) {
                    Log::write('PVZ with ID '.$pvz_id.' not found');
                    return $this->addError(t('Не удалось найти ПВЗ с ID %0', array($pvz_id)));
                }

                //Записываем информацию, необходимую для привязки доставки с ПВЗ
                $delivery_extra = $pvz->getDeliveryExtraJson();
                $order->addExtraKeyPair('delivery_extra', array('value' => $delivery_extra));

                //Даем команду на регистрацию заказа в службе доставки
                $order['delivery_new_query'] = true;
                Log::write('PVZ found, ID:'.$pvz->getCode());
            }

            $order['delivery'] = $delivery['id'];
            $order['user_delivery_cost'] = $this->getOrderDeliveryPrice(); //Не рассчитываем повторно стоимость доставки

            $order->update();
            $order->getCart()->saveOrderData(); //Для формирования записей о доставке
        }

        return true;
    }

    public function linkAddressToUser($order, $user_id)
    {
        $address = $order->getAddress();
        if (!$address['user_id'])
        {
            //Адрес нуждается в привязке к пользователю
            //Пытаемся найти такой же адрес у пользователя

            $where = $address->getValues();
            unset($where['id']);

            $where['user_id'] = $user_id;
            $where['deleted'] = 0;

            foreach($where as $k => $condition) {
                if ($condition === '') {
                    unset($where[$k]);
                }
            }

            $exists_address = \Shop\Model\Orm\Address::loadByWhere($where);

            if ($exists_address['id']) {
                $order->use_addr = $exists_address['id'];
                $address->delete(); //Удаляем старый
                Log::write('[in] Order linked to other address ' . $exists_address['id']);
            } else {
                $address['user_id'] = $user_id;
                $address->update();
                Log::write('[in] Address ' . $address['id'] . ' linked to user ' . $user_id);
            }
        }
    }

    /**
     * Парсим товары из корзины заказа
     * @return array список предложений для Яндекса
     */
    public function getOrderItems()
    {
        $yItems = new YItems('order', $this->yandex_cart_object);
        return $yItems->getParsedItems('order');
    }


    /**
     * Изменить статус заказа в Яндекс Маркете
     * @param $order_id номер заказа
     * @param $statusYFormat string в формате Яндекса
     * @return mixed
     * @throws Exception
     */
    public function ChangeYOrderStatus($order_id, $statusYFormat, $substatus = null)
    {
        $config = \RS\Config\Loader::byModule($this);
        if (!empty($config['campaign_id'])) {
            $campagin_id = $config['campaign_id'];
        } else {
            new Exception('Номер компании ЯндексМаркета не задан в конфиге');
        }

        $url = "https://api.partner.market.yandex.ru/v2/campaigns/$campagin_id/orders/$order_id/status.json";
        $data = array("order" => array("status" => $statusYFormat));
        if ($statusYFormat == "CANCELLED" and !is_null($substatus)) {
            $data = array("order" => array("status" => $statusYFormat, "substatus" => $substatus));

        }
        return $this->makeYRequest($url, $data, 'PUT');
    }


    /**
     * Отправить на самопроверку Яндекс
     * @return mixed вернет ответ от Яндекса
     * @throws Exception
     */
    public function requestYandexSelftest()
    {
        $config = \RS\Config\Loader::byModule($this);
        if (!empty($config['campaign_id'])) {
            $campagin_id = $config['campaign_id'];
        } else {
            new Exception('Номер компании ЯндексМаркета не задан в конфиге');
        }

        $url = "https://api.partner.market.yandex.ru/v2/campaigns/$campagin_id/quality/self-check.json";
        return $this->makeYRequest($url, array(), 'POST');
    }

    /**
     * Метод запроса к YandexMarket
     * @param $url  адрес отправки данных
     * @param $data payload
     * @return mixed
     */
    public function makeYRequest($url, $data, $method = "POST")
    {

        $config = \RS\Config\Loader::byModule($this);
        if (!empty($config['ytoken'])) {
            $ytoken = $config['ytoken'];
        } else {
            new Exception('Токен oAuth ЯндексМаркета не задан в конфиге');
        }

        $yAppId = $config->getYandexAppId();

        $url .= "?oauth_token=$ytoken&oauth_client_id=$yAppId"; //Добавляем токен и id для авторизации на сервисе яндекса
        $ch = curl_init($url);
        $payload = json_encode($data);
        if ($method == "PUT") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Content-Length: ' . strlen($payload)));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        Log::write('--------------');
        Log::write('[out] Make request: ' . $url);
        Log::write('[out] Send params: ' . var_export($payload, true)); //$payload);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Log::write('[out] Response code: ' . $httpcode);
        Log::write('[out] Response body: ' . $result);
        Log::write('--------------');

        return $result;
    }

    public static function getYandexCpaBaseUrl()
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);
        return \Setup::$FOLDER . "/ymarket{$config->secret_part_url}";
    }


    /**
     * Получаем URL для начала процедуры получения токена
     * @return string ссылка для запроса на получение токена
     * @throws Exception
     */
    public function getYandexTokeUrl()
    {
        $config = \RS\Config\Loader::byModule($this);

        if (!empty($config['yAppId'])) {
            $yAppId = $config['yAppId'];
        } else {
            new Exception(' не задан в конфиге');
        }
        $url = "https://oauth.yandex.ru/authorize?response_type=code&client_id=$yAppId";

        return $url;
    }


    /**
     * Отправить запрос на изменение условий доставки
     * @param $yandex_order_id  id заказа yandex
     * @return bool|mixed
     * @throws Exception
     */
    public function requestChangeDelivery($yandex_order_id)
    {
        if (!empty($this->config['campaign_id'])) {
            $campagin_id = $this->config['campaign_id'];
        } else {
            new Exception('Номер компании ЯндексМаркета не задан в конфиге');
        }

        /**
         * @var $order \Shop\Model\Orm\Order
         */
        $order = \Shop\Model\Orm\Order::loadByWhere(array(
            'id_yandex_market_cpa_order' => $yandex_order_id
        ));

        if ($order['id']) {
            $deliveryClass = new YDelivery($order, $order->getAddress(), false);

            $delivery = array();
            $delivery[] = $order->getDelivery();
            if (!$delivery[0]['id']) {
                return false;
            }

            $address = $order->getAddress();
            $y_address = array();
            if (!empty($address['zipcode'])) $y_address['postcode'] = $address['zipcode'];
            if (!empty($address['country'])) $y_address['country'] = $address['country'];
            if (!empty($address['city'])) $y_address['city'] = $address['city'];
            if (!empty($address['subway'])) $y_address['subway'] = $address['subway'];
            if (!empty($address['address'])) $y_address['street'] = $address['address'];
            if (!empty($address['house'])) $y_address['house'] = $address['house'];
            if (!empty($address['entrance'])) $y_address['entrance'] = $address['entrance'];
            if (!empty($address['entryphone'])) $y_address['entryphone'] = $address['entryphone'];
            if (!empty($address['floor'])) $y_address['floor'] = $address['floor'];
            if (!empty($address['apartment'])) $y_address['apartment'] = $address['apartment'];

            $del = $deliveryClass->getYDelivery($delivery);
            $data = array('delivery' => array(
                $del[0], $y_address));
            $data['delivery']['price'] = $order['user_delivery_cost'];
            $url = "https://api.partner.market.yandex.ru/v2/campaigns/$campagin_id/orders/$yandex_order_id/delivery.json";

            return $this->makeYRequest($url, $data, 'PUT');
        }
        return false;
    }

}