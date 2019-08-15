<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

use RS\Config\Loader as ConfigLoader;
use RS\Event\Manager as EventManager;
use RS\Exception;
use RS\Module\AbstractModel\EntityList;
use RS\Orm\Request as OrmRequest;
use RS\Site\Manager as SiteManager;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\DeliveryDir;
use Shop\Model\Orm\Order;
use Users\Model\Orm\User;
use Users\Model\Orm\UserInGroup;

/**
 * API функции для работы со способами доставки для текущего сайта
 */
class DeliveryApi extends EntityList
{
    protected static $types;

    function __construct()
    {
        parent::__construct(new Delivery(),
            array(
                'nameField' => 'title',
                'multisite' => true,
                'defaultOrder' => 'sortn',
                'sortField' => 'sortn'
            ));
    }

    /**
     * Возвращает Имеющиеся в системе обработчики типов доставок.
     *
     * @return DeliveryType\AbstractType[]
     * @throws Exception
     */
    public static function getTypes()
    {
        if (self::$types === null) {
            $event_result = EventManager::fire('delivery.gettypes', array());
            $list = $event_result->getResult();
            self::$types = array();
            foreach ($list as $delivery_type_object) {
                if (!($delivery_type_object instanceof DeliveryType\AbstractType)) {
                    throw new Exception(t('Тип доставки должен быть наследником \Shop\Model\DeliveryType\AbstractType'));
                }
                self::$types[$delivery_type_object->getShortName()] = $delivery_type_object;
            }
        }

        return self::$types;
    }

    /**
     * Возвращает массив ключ => название типа доставки
     *
     * @return string[]
     */
    public static function getTypesAssoc()
    {
        $result = array();
        foreach (self::getTypes() as $key => $object) {
            $result[$key] = $object->getTitle();
        }
        return $result;
    }

    /**
     * Получение списка доставок для типа оплаты
     *
     * @return array
     * @throws \RS\Orm\Exception
     */
    public static function getListForOrder()
    {
        $groups = array(0 => array('title' => t('Без группы'))) +
            OrmRequest::make()
                ->from(new DeliveryDir())
                ->orderby('sortn')
                ->where(array(
                    'site_id' => SiteManager::getSiteId()
                ))
                ->objects(null, 'id');

        $_this = new self();
        $deliveries = $_this->clearFilter()
            ->setOrder('sortn')
            ->getList();

        $list = array();
        //Составим двухуровневый список
        if (!empty($deliveries)) {
            foreach ($deliveries as $delivery) {
                $title = $groups[$delivery['parent_id']]['title'] ?: t('Без группы');
                $list[$title][$delivery['id']] = $delivery;
            }
        }
        return $list;
    }

    /**
     * Получение списка доставок для типа оплаты
     *
     */
    public static function getListForPayment()
    {
        $list = parent::staticSelectList();
        $list = array('0' => t('-Все-')) + $list;
        return $list;
    }

    /**
     * Возвращает объект типа доставки по идентификатору
     *
     * @param string $name - идентификатор типа доставкиъ
     * @return DeliveryType\AbstractType
     */
    public static function getTypeByShortName($name)
    {
        $_this = new self();
        $list = $_this->getTypes();
        return isset($list[$name]) ? $list[$name] : new DeliveryType\Stub($name);
    }

    public static function getZonesList()
    {
        return array(0 => t(' - все - ')) + ZoneApi::staticSelectList();
    }

    /**
     * Устанавливает фильтр по магистральным поясам
     *
     * @param array $zone - массив с магистральными поясами
     */
    public function setZoneFilter($zone)
    {
        $zone = array_merge(array(0), (array)$zone);
        $q = $this->queryObj();
        $q->join(new Orm\DeliveryXZone(), 'DZ.delivery_id = A.id AND DZ.zone_id IN (' . implode(',', $zone) . ')', 'DZ');
    }

    /**
     * Возвращает массив классов отвечающих за самовывоз в системе
     *
     * @return string[]
     */
    public static function getPickupPointClasses()
    {
        $classes = array();
        foreach (self::getTypes() as $short_name => $delivery_type) {
            if ($delivery_type->isMyselfDelivery()) {
                $classes[] = $short_name;
            }
        }
        return $classes;
    }

    /**
     * Возвращает список доступных доставок, указанного класса
     *
     * @param array $classes - список классов
     * @param bool $inverse - если true - будут выбраны доставки не входящие в указанный список классов
     * @param \Shop\Model\Orm\Order $order - если указан объект заказа - вернутся доставки, подходящие под данный заказ
     * @return Delivery[]
     */
    static function getDeliveriesByClasses($classes, $inverse = false, $order = null)
    {
        $_this = new self();
        $type = $inverse ? 'notin' : 'in';

        //Посмотрим есть ли доставки с типом самовывоз
        $_this->setFilter('public', 1)
            ->setFilter('class', $classes, $type);

        if ($order && $order->getCart()) { //Если есть объект заказа, то применим к нему дополниельные фильтры
            $cartdata = $order->getCart()->getCartData(false);
            //Проверим условие минимальной цены
            $_this->setFilter(array(
                array(
                    'min_price' => 0,
                    '|min_price:<=' => $cartdata['total_without_delivery_unformatted'],
                )
            ));
            //Проверим условие максимальной цены
            $_this->setFilter(array(
                array(
                    'max_price' => 0,
                    '|max_price:>' => $cartdata['total_without_delivery_unformatted'],
                )
            ));
            //Проверим условие минимального количества товаров
            $_this->setFilter(array(
                array(
                    'min_cnt' => 0,
                    '|min_cnt:<=' => $cartdata['items_count'],
                )
            ));
        }
        /** @var Delivery[] $list */
        $list = $_this->getList();
        return $list;
    }

    /**
     * Возвращает список пунктов самовывоза в системе.
     * Если передать объект заказа, то вернутся пункты подходящие под данный заказ
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return Delivery[]
     */
    public static function getPickUpPoints($order = null)
    {
        return self::getDeliveriesByClasses(self::getPickupPointClasses(), false, $order);
    }

    /**
     * Проверяет есть пункты самовывоза в системе. Проверяет наличие таких доставок, а затем пунктов самовывоза, если на находит доставок этого типа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     *
     * @return boolean
     */
    public static function isHavePickUpPoints($order = null)
    {
        $delivery_points = self::getPickUpPoints($order);
        return (!empty($delivery_points)) ? true : false;
    }

    /**
     * Проверяет наличие доступных в системе доставок по адресу
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     *
     * @return boolean
     */
    public static function isHaveToAddressDelivery($order = null)
    {
        $delivery_points = self::getDeliveriesByClasses(self::getPickupPointClasses(), true, $order);;
        return (!empty($delivery_points)) ? true : false;
    }

    /**
     * Возвращает список пользователей-курьеров
     *
     * @return array
     */
    public static function getCourierList()
    {
        $config = ConfigLoader::byModule(__CLASS__);
        $courier_group = $config['courier_user_group'];

        if ($courier_group) {
            return OrmRequest::make()
                ->select('U.*')
                ->from(new User(), 'U')
                ->join(new UserInGroup(), 'G.user = U.id', 'G')
                ->where(array(
                    'G.group' => $courier_group
                ))->objects(null, 'id');
        }
        return array();
    }

    /**
     * Возвращает ассоциативный массив для отображения списка курьеров
     *
     * @param array $root - корневой элемент
     * @return array
     */
    public static function getCourierSelectList($root = array('- Не выбрано -'))
    {
        $couriers = self::getCourierList();

        $result = array();
        foreach ($couriers as $courier) {
            $result[$courier['id']] = $courier->getFio();
        }
        return $root + $result;
    }

    /**
     * Возвращает доставки, которые необходимо отобразить на
     * этапе оформления заказа
     *
     * @param \Users\Model\Orm\User $user - объект пользователя
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param bool $check_public - возвращать только публичные доставки
     * @param bool $strict_to_address_filter - не включать самовывоз к доставкам до адреса
     * @return \Shop\Model\Orm\Delivery[]
     */
    function getCheckoutDeliveryList(User $user, Order $order, $check_public = true, $strict_to_address_filter = false)
    {
        $my_type = $user['is_company'] ? 'company' : 'user';

        if ($check_public) {
            $this->setFilter('public', 1);
        }
        $this->setFilter('user_type', array('all', $my_type), 'in');

        if (!$order['only_pickup_points']) { //Если не только самовывозом
            //Получим все зоны
            $zone_api = new ZoneApi();
            $address = $order->getAddress();
            $zones = $zone_api->getZonesByRegionId($address['region_id'], $address['country_id'], $address['city_id']);

            $this->setZoneFilter($zones);

            if ($strict_to_address_filter) {
                $this->setFilter('class', $this::getPickupPointClasses(), 'notin');
            }
        } else { //Если только пункты самовывоза
            $this->setFilter('class', $this::getPickupPointClasses(), 'in');
        }

        $cart = $order->getCart();

        if ($cart) {
            $cartdata = $cart->getCartData(false);
            //Проверим условие минимальной цены
            $this->setFilter(array(
                array(
                    'min_price' => 0,
                    '|min_price:<=' => $cartdata['total_without_delivery_unformatted'],
                )
            ));
            //Проверим условие максимальной цены
            $this->setFilter(array(
                array(
                    'max_price' => 0,
                    '|max_price:>' => $cartdata['total_without_delivery_unformatted'],
                )
            ));
            //Проверим условие минимального веса
            $this->setFilter(array(
                array(
                    'min_weight' => 0,
                    '|min_weight:<=' => $cartdata['total_weight'],
                )
            ));
            //Проверим условие максимального веса
            $this->setFilter(array(
                array(
                    'max_weight' => 0,
                    '|max_weight:>' => $cartdata['total_weight'],
                )
            ));
            //Проверим условие минимального количества товаров
            $this->setFilter(array(
                array(
                    'min_cnt' => 0,
                    '|min_cnt:<=' => $cartdata['items_count'],
                )
            ));
        }

        $this->queryObj()->groupby('id');
        $delivery_list = $this->getAssocList('id');

        // TODO описать событие 'checkout.delivery.list' в документации
        // Событие для модификации списка доставок
        $result = EventManager::fire('checkout.delivery.list', array(
            'list' => $delivery_list,
            'order' => $order,
            'user' => $user,
        ));
        list($delivery_list) = $result->extract();

        return $delivery_list;
    }
}
