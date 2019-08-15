<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Config;

use Export\Model\ExportType\Yandex\Yandex;
use RS\Config\Loader;
use RS\Orm\AbstractObject;
use \RS\Orm\Type as OrmType;
use Shop\Model\Orm\UserStatus;
use YandexMarketCpa\Model\HolidayMapper;
use YandexMarketCpa\Model\Initialize;
use YandexMarketCpa\Model\PaymentType\YandexCardOnDelivery;
use YandexMarketCpa\Model\PaymentType\YandexCashOnDelivery;
use YandexMarketCpa\Model\PaymentType\YandexGeneric;
use YandexMarketCpa\Model\YandexReference;
use YandexMarketCpa\Model\YandexUtils;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('getroute')
            ->bind('controller.exec.shop-admin-orderctrl.index')
            ->bind('controller.exec.shop-admin-deliveryctrl.index')
            ->bind('orm.init.catalog-warehouse')
            ->bind('orm.init.shop-delivery')
            ->bind('orm.init.shop-order')
            ->bind('orm.beforewrite.shop-order')
            ->bind('payment.gettypes')
            ->bind('orm.afterwrite.shop-order')
            ->bind('delivery.period.correct');

        if (\Setup::$INSTALLED) {
            $this->bind('orm.afterwrite.site-site', $this, 'onSiteCreate');
        }
    }

    /**
     * Корректируем сроки доставки, если в Яндекс.Маркете задана карта выходных дней
     */
    public static function deliveryPeriodCorrect($params)
    {
        $delivery = $params['delivery'];
        $address = $params['address'];
        $period = $params['period'];
        if ($address && $period && $delivery && $delivery['is_map_holiday']) {
            $holiday_mapper = new HolidayMapper();
            $params['period'] = $holiday_mapper->mapPeriod($period, $address);
            return $params;
        }
    }

    /**
     * Добавляем возможность фильтровать по флагу - эта доставка доступна для Заказов на Яндексе
     * Добавляем в таблицу возможно отобразить данный флаг
     */
    public static function controllerExecShopAdminDeliveryCtrlIndex($helper)
    {
        /**
         * @var $table \RS\Html\Table\Element
         */
        $table = $helper['table']->getTable();
        $column = new \RS\Html\Table\Type\StrYesno('is_use_yandex_market_cpa', t('Я.Маркет'), array('Hidden' => true, 'Sortable' => SORTABLE_BOTH));
        $table->addColumn($column, -1);

    }

    /**
     * Добавляем возможность фильтровать по флагу - это заказ от яндекса
     * Добавляем в таблицу возможно отобразить данный флаг
     */
    public static function controllerExecShopAdminOrderCtrlIndex($helper)
    {
        /**
         * @var $table \RS\Html\Table\Element
         */
        $table = $helper['table']->getTable();
        $column = new \RS\Html\Table\Type\Text('id_yandex_market_cpa_order', t('ID заказа на Маркете'), array('Hidden' => true, 'Sortable' => SORTABLE_BOTH));
        $table->addColumn($column, -1);

        /**
         * @var $filter \RS\Html\Filter\Control
         */
        $filter = $helper['filter'];
        $lines = $filter->getContainer()->getLines();
        $lines[0]->addItem(new \RS\Html\Filter\Type\Text('id_yandex_market_cpa_order', 'ID заказа на маркете'));
    }


    /**
     * Возвращает маршруты данного модуля
     */
    public static function getRoute(array $routes)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);

        $routes[] = new \RS\Router\Route(
            'yandexmarketcpa-front-base', //ID маршрута
            "/ymarket", //Обрабатываемые URL
            null,
            t('Данные с маркета'), //Название маршрута для админ. панели
            true
        );

        //Добавляем маршрут в систему
        $routes[] = new \RS\Router\Route(
            'yandexmarketcpa-front-cart', //ID маршрута
            "/ymarket{$config->secret_part_url}/cart", //Обрабатываемые URL
            null,
            t('Данные с маркета') //Название маршрута для админ. панели
        );

        $routes[] = new \RS\Router\Route(
            'yandexmarketcpa-front-order', //ID маршрута
            "/ymarket{$config->secret_part_url}/order/{Act}", //Обрабатываемые URL
            null,
            t('Заказ на маркете') //Название маршрута для админ. панели
        );

        return $routes;
    }

    /**
     * Расширяем объект склада, добавлен id Точки продаж
     *
     * @param $warehouse
     */
    public static function ormInitCatalogWarehouse($warehouse)
    {
        $warehouse->getPropertyIterator()->append(array(
            t('Яндекс Маркет'),
            'yandex_market_point_id' => new OrmType\Varchar(array(
                'description' => t('ID точки продаж в Яндекс.Маркет'),
            ))
        ));
    }

    /**
     * Расширяем объект заказа
     *
     * @param $order
     */
    public static function ormInitShopOrder($order)
    {
        $order->getPropertyIterator()->append(array(
            t('Яндекс.Маркет'),
            'id_yandex_market_cpa_order' => new OrmType\Bigint(array(
                'description' => t('ID заказа в Яндекс.маркете'),
                'visible' => true,
                'infoVisible' => true,
                'meVisible' => false,
            ))
        ));
    }

    /**
     * Проверяем возможность переключения статуса и субстатуса
     * при сохранении заказа
     */
    public static function ormBeforewriteShopOrder($params, $event)
    {
        $order = $params['orm'];
        if ($order['id_yandex_market_cpa_order']
            && $params['flag'] == AbstractObject::UPDATE_FLAG
            && !$order->is_yandex_initiated) {

            $config = Loader::byModule(__CLASS__);
            //Проверим карту смены статусов
            if (!$config->disable_status_graph) {
                if ($order->this_before_write['status'] != $order['status']) {

                    $allow_statuses = YandexReference::getStatusMap($order->this_before_write['status']);
                    if ($allow_statuses !== false && !in_array($order['status'], $allow_statuses)) {

                        $allow_statuses_title = implode(t(' или '), YandexReference::getStatusTitlesByIds($allow_statuses));
                        $order->addError(t("Невозможно установить статус '%new' после '%old'. Ожидается '%allow'. Проверьте граф возможных изменений статусов в справке Яндекса.", array(
                            'new' => $order->getStatus()->title,
                            'old' => $order->this_before_write->getStatus()->title,
                            'allow' => $allow_statuses_title
                        )), 'status');
                    }
                }

                //Проверим карту смены подстатусов
                $allow_substatuses = YandexReference::getSubStatusMap($order->this_before_write['status'], $order['status']);
                if ($allow_substatuses !== false && !in_array($order['substatus'], $allow_substatuses)) {

                    $allow_statuses_title = implode(t(' или '), YandexReference::getSubStatusTitlesByIds($allow_substatuses));
                    $order->addError(t("Некорректная причина отмены заказа. Ожидается '%allow'.", array(
                        'allow' => $allow_statuses_title
                    )), 'status');
                }

                if ($order->hasError()) {
                    $event->stopPropagation(); //Прекращаем сохранение заказа
                }
            }
        }
    }

    /**
     * Уведомляем Яндекс о смене статуса заказа
     *
     * @param $params
     * @param $event
     */
    public static function ormAfterwriteShopOrder($params, $event)
    {
        $order = $params['orm'];

        //Если просиходит обновление заказа, созданного через Заказы на Яндексе
        // и статус действительно изменился администратором вручную
        if ($params['flag'] == AbstractObject::UPDATE_FLAG
            && $order['id_yandex_market_cpa_order']
            && !$order->is_yandex_initiated) {

            $yutils = new YandexUtils();

            if ($order->this_before_write['status'] != $order['status']) {
                $status = YandexReference::findYandexStatusById($order['status']);
                $substatus = YandexReference::findYandexSubStatusById($order['substatus']);

                if (!empty($substatus)) {
                    $res = $yutils->ChangeYOrderStatus($order->id_yandex_market_cpa_order, $status, $substatus);
                } else {
                    $res = $yutils->ChangeYOrderStatus($order->id_yandex_market_cpa_order, $status);
                }
            }

            //Если изменен способ доставки или стоимость доставки
            if ((int)$order->this_before_write['delivery'] != (int)$order['delivery']
                || (float)$order->this_before_write['user_delivery_cost'] != (float)$order['user_delivery_cost'] )
            {
                //try {
                    //Сообщаем яндексу о смене способа доставки
                    $yutils->requestChangeDelivery($order['id_yandex_market_cpa_order']);
                //} catch (\RS\Exception $e) {}
            }
        }
    }


    /**
     * Расширяем объект доставки
     *
     * @param $delivery
     */
    public static function ormInitShopDelivery($delivery)
    {
        $delivery->getPropertyIterator()->append(array(
            t('Яндекс.Маркет'),
            'is_use_yandex_market_cpa' => new OrmType\Integer(array(
                'maxLength' => 1,
                'description' => t('Использовать для доставки с Я.Маркета'),
                'hint' => t('Данный способ доставки будет предложен пользователям, которые оформляют Заказ на Маркете'),
                'checkboxView' => array(1,0)
            )),
            'is_map_holiday' => new OrmType\Integer(array(
                'description' => t('Проверять, чтобы дата доставки не выпадала на выходные и праздничные дни, согласно настройкам кампании Яндекс.Маркета'),
                'hint' => t('В случае, если доставка выпадет на выходной день дата будет перенесена на ближайший рабочий день. В случае, если данная опция включена, дата доставки будет корректировать на всем сайте во всех расчетах даты доставки в локальном регионе (адрес доставки должен быть в локальном регионе). Локальный регион будет подгружен из настроек Яндекс.Маркета'),
                'checkboxView' => array(1,0)
            ))
        ));
    }

    /**
     * Привносим свои расчетные классы оплаты для модуля
     *
     * @param $list
     * @return array
     */
    public static function paymentGettypes($list)
    {
        $list[] = new YandexGeneric();
        $list[] = new YandexCardOnDelivery();
        $list[] = new YandexCashOnDelivery();
        return $list;
    }

    /**
     * Инициализируем статусы, необходимые для работы модуля
     */
    public static function onSiteCreate($params)
    {
        $site = $params['orm'];

        $init_helper = new Initialize($site['id']);
        $init_helper->initializeSite();
    }
}