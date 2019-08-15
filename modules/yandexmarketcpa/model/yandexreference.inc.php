<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;
use Shop\Model\UserStatusApi;

/**
 * Здесь будут располагаться справочники
 */
class YandexReference
{
    const
        //Статусы в ReadyScript
        STATUS_DELIVERY = 'delivery',
        STATUS_PICKUP = 'pickup',
        STATUS_RESERVED = 'reserved',

        //Статусы в Заказах на Маркете
        YANDEX_STATUS_CANCELLED = 'CANCELLED',
        YANDEX_STATUS_DELIVERED = 'DELIVERED',
        YANDEX_STATUS_PROCESSING = 'PROCESSING',
        YANDEX_STATUS_UNPAID = 'UNPAID',
        YANDEX_STATUS_DELIVERY = 'DELIVERY',
        YANDEX_STATUS_PICKUP = 'PICKUP',
        YANDEX_STATUS_RESERVED = 'RESERVED',

        //Подстатусы - причина отмены заказа
        SUBSTATUS_NO_SELECT = 'NO_SELECT',
        SUBSTATUS_PROCESSING_EXPIRED = 'PROCESSING_EXPIRED',
        SUBSTATUS_REPLACING_ORDER = 'REPLACING_ORDER',
        SUBSTATUS_RESERVATION_EXPIRED = 'RESERVATION_EXPIRED',
        SUBSTATUS_SHOP_FAILED = 'SHOP_FAILED',
        SUBSTATUS_USER_CHANGED_MIND = 'USER_CHANGED_MIND',
        SUBSTATUS_USER_NOT_PAID = 'USER_NOT_PAID',
        SUBSTATUS_USER_REFUSED_DELIVERY = 'USER_REFUSED_DELIVERY',
        SUBSTATUS_USER_REFUSED_PRODUCT = 'USER_REFUSED_PRODUCT',
        SUBSTATUS_USER_REFUSED_QUALITY = 'USER_REFUSED_QUALITY',
        SUBSTATUS_USER_UNREACHABLE = 'USER_UNREACHABLE';


    /**
     * Возвращает названия всех возможных под-статусов
     *
     * @return array
     */
    public static function getSubstatusTitles()
    {
        return array(
            self::SUBSTATUS_NO_SELECT => t('- Не выбрано -'),
            self::SUBSTATUS_PROCESSING_EXPIRED => t('Магазин не обработал заказ вовремя'),
            self::SUBSTATUS_REPLACING_ORDER => t('Покупатель изменяет состав заказа'),
            self::SUBSTATUS_RESERVATION_EXPIRED => t('Покупатель не завершил оформление зарезервированного заказа вовремя'),
            self::SUBSTATUS_SHOP_FAILED => t('Магазин не может выполнить заказ'),
            self::SUBSTATUS_USER_CHANGED_MIND => t('Покупатель отменил заказ по собственным причинам'),
            self::SUBSTATUS_USER_NOT_PAID => t('Покупатель не оплатил заказ'),
            self::SUBSTATUS_USER_REFUSED_DELIVERY => t('Покупателя не устраивают условия доставки'),
            self::SUBSTATUS_USER_REFUSED_PRODUCT => t('Покупателю не подошел товар'),
            self::SUBSTATUS_USER_REFUSED_QUALITY => t('Покупателя не устраивает качество товара'),
            self::SUBSTATUS_USER_UNREACHABLE => t('Не удалось связаться с покупателем')
        );
    }

    /**
     * Возвращает возможные значения новых статусов, исходя из старого статуса
     *
     * @param integer $old_status_id ID старого статуса
     * @return array | bool(false)
     */
    public static function getStatusMap($old_status_id)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);

        $status_reserved_ids = (array)$config->status_reserved_reverse;
        $status_processing_ids = (array)$config->status_processing_reverse;
        $status_delivery_ids = (array)$config->status_delivery_reverse;
        $status_delivered_ids = (array)$config->status_delivered_reverse;
        $status_cancelled_ids = (array)$config->status_cancelled_reverse;
        $status_pickup_ids = (array)$config->status_pickup_reverse;

        $map =
            self::makeArrayList($status_reserved_ids, array_merge(
                $status_reserved_ids,
                $status_cancelled_ids
            ))
            + self::makeArrayList($status_processing_ids, array_merge(
                $status_delivery_ids,
                $status_cancelled_ids
            ))
            + self::makeArrayList($status_delivery_ids, array_merge(
                $status_pickup_ids,
                $status_delivered_ids,
                $status_cancelled_ids
            ));

        if (isset($map[$old_status_id])) {
            return $map[$old_status_id];
        }

        return false;
    }

    /**
     * Формирует массив, в ключах которого будут значения массива $array, а в значениях один и тот же $value
     *
     * @param $array
     * @param $value
     * @return array
     */
    protected static function makeArrayList($array, $value)
    {
        $result = array();
        foreach($array as $array_value) {
            if ($array_value == 0) continue;
            $result[$array_value] = $value;
        }
        return $result;
    }

    /**
     * Возвращает возможные значения подстатусов
     *
     * @param integer $old_status_id ID старого статуса
     * @param integer $new_status_id ID нового статуса
     * @return array | bool(false)
     */
    public static function getSubStatusMap($old_status_id, $new_status_id)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);

        $status_reserved_ids = $config->status_reserved_reverse;
        $status_processing_ids = $config->status_processing_reverse;
        $status_delivery_ids = $config->status_delivery_reverse;
        $status_cancelled_ids = $config->status_cancelled_reverse;
        $status_pickup_ids = $config->status_pickup_reverse;

        $list1 = array_merge(
            $config->substatus_replacing_order_reverse,
            $config->substatus_shop_failed_reverse,
            $config->substatus_user_changed_mind_reverse,
            $config->substatus_user_refused_delivery_reverse,
            $config->substatus_user_refused_product_reverse,
            $config->substatus_user_unreachable_reverse
        );

        $list2 = array_merge(
            $config->substatus_shop_failed_reverse,
            $config->substatus_user_changed_mind_reverse,
            $config->substatus_user_refused_delivery_reverse,
            $config->substatus_user_refused_product_reverse,
            $config->substatus_user_refused_quality_reverse,
            $config->substatus_user_unreachable_reverse
        );

        $map =
            self::makeArrayList($status_reserved_ids,
                self::makeArrayList($status_cancelled_ids, $list1)
            )+

            self::makeArrayList($status_processing_ids,
                self::makeArrayList($status_cancelled_ids, $list1)
            )+

            self::makeArrayList($status_delivery_ids,
                self::makeArrayList($status_cancelled_ids, $list2)
            )+

            self::makeArrayList($status_pickup_ids,
                self::makeArrayList($status_cancelled_ids, $list2)
            );

        if (isset($map[$old_status_id][$new_status_id])) {
            return $map[$old_status_id][$new_status_id];
        }

        return false;
    }

    /**
     * Возвращает идентификатор статуса в Яндексе по ID статуса в RS
     *
     * @param integer $order_status_id - ID статуса в RS
     * @return string
     */
    public static function findYandexStatusById($order_status_id)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);

        if (in_array($order_status_id, (array)$config['status_cancelled_reverse'])) {
            return self::YANDEX_STATUS_CANCELLED;
        }
        if (in_array($order_status_id, (array)$config['status_delivered_reverse'])) {
            return self::YANDEX_STATUS_DELIVERED;
        }
        if (in_array($order_status_id, (array)$config['status_processing_reverse'])) {
            return self::YANDEX_STATUS_PROCESSING;
        }
        if (in_array($order_status_id, (array)$config['status_unpaid_reverse'])) {
            return self::YANDEX_STATUS_UNPAID;
        }
        if (in_array($order_status_id, (array)$config['status_delivery_reverse'])) {
            return self::YANDEX_STATUS_DELIVERY;
        }
        if (in_array($order_status_id, $config['status_pickup_reverse'])) {
            return self::YANDEX_STATUS_PICKUP;
        }
        if (in_array($order_status_id, $config['status_reserved_reverse'])) {
            return self::YANDEX_STATUS_RESERVED;
        }
    }

    /**
     * Возвращает статус в ReadyScript с учетом настроек модуля
     *
     * @param string $yandex_status
     * @return integer | null
     */
    public static function findRsStatusByYandexId($yandex_status)
    {
        $status = strtolower($yandex_status);
        $config = \RS\Config\Loader::byModule(__CLASS__);

        if (isset($config['status_'.$status])) {
            return $config['status_'.$status];
        }
    }

    /**
     * Возвращает список названий статусов по их ID
     *
     * @param array $ids массив с ID статусов
     * @return string[]
     */
    public static function getStatusTitlesByIds($ids)
    {
        $result = array();
        foreach($ids as $id) {
            $status = new \Shop\Model\Orm\UserStatus($id);
            $result[$id] = $status['id'] ? $status['title'] : self::findYandexStatusById($id);
        }

        return $result;
    }

    /**
     * Возвращает список названий субстатусов по их ID
     *
     * @param array $ids массив с ID субстатусов
     * @return string[]
     */
    public static function getSubStatusTitlesByIds($ids)
    {
        $result = array();
        foreach($ids as $id) {
            $substatus = new \Shop\Model\Orm\SubStatus($id);
            $result[$id] = $substatus['id'] ? $substatus['title'] : self::findYandexSubStatusById($id);
        }

        return $result;
    }

    /**
     * Возвращает идентификатор статуса в Яндексе по ID причины отмены заказа в RS
     *
     * @param integer $order_status_id - ID статуса в RS
     * @return string
     */
    public static function findYandexSubStatusById($order_substatus_id)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);

        $reference = array(
            self::SUBSTATUS_REPLACING_ORDER,
            self::SUBSTATUS_RESERVATION_EXPIRED,
            self::SUBSTATUS_SHOP_FAILED,
            self::SUBSTATUS_USER_CHANGED_MIND,
            self::SUBSTATUS_USER_NOT_PAID,
            self::SUBSTATUS_USER_REFUSED_DELIVERY,
            self::SUBSTATUS_USER_REFUSED_PRODUCT,
            self::SUBSTATUS_USER_REFUSED_QUALITY,
            self::SUBSTATUS_USER_UNREACHABLE
        );

        foreach($reference as $key) {
            if (in_array($order_substatus_id, (array)$config['substatus_'.strtolower($key).'_reverse'])) {
                return $key;
            }
        }
    }


}