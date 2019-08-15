<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Config;

use \RS\Orm\Type;
use YandexMarketCpa\Model\YRequestUtils;

/**
 * Конфигурационный файл модуля
 */
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        $status_hint = t("Выберите статус в вашей системе, который наиболее соответствует статусу заказа Яндекс.Маркета. Если заказ был оформлен с помощью сервиса 'Заказы на Яндексе'. При смене статуса заказа в ReadyScript, будет уходить запрос на смену статуса заказа в Яндекс. Удерживая CTRL можно отметить несколько статусов.");

        parent::_init()->append(array(
            t('Основные'),
                    'secret_part_url' => new Type\Varchar(array(
                        'description' => t('Секретная часть URL для внешнего API'),
                        'template' => '%yandexmarketcpa%/secret_part_url.tpl'
                    )),
                    'auth_token' => new Type\Varchar(array(
                        'description' => t('Авторизационный токен'),
                        'hint' => t('Располагается на странице Настройки API заказа на сайте partner.market.yandex.ru'),
                    )),
                    'ytoken' => new Type\Varchar(array(
                        'description' => t('oAuth токен приложения на Яндексе'),
                        'hint' => t('Токен будет получен после ввода авторизационного кода'),
                        'template' => '%yandexmarketcpa%/config_auth_token.tpl'
                    )),
                    'ytoken_expire' => new Type\Varchar(array(
                        'description' => t('Время истечения текущего токена'),
                        'attr' => array(array(
                            'readOnly' => true
                        )),
                    )),
                    'campaign_id' => new Type\Varchar(array(
                        'description' => t('ID кампании в Яндекс.Маркете'),
                        'template' => '%yandexmarketcpa%/campaign.tpl'
                    )),
                    'enable_log' => new Type\Integer(array(
                        'description' => t('Включить логирование'),
                        'hint' => t('Включайте данный флаг только на небольшой период для отладки.'),
                        'checkboxView' => array(1,0)
                    )),
                    'ignore_city_unexists' => new Type\Integer(array(
                        'description' => t('Игнорировать отсутствие города в справочнике ReadyScript'),
                        'hint' => t('Включайте данную опцию, если способы доставок не связаны с регионами покупателя. В случае отсутствия города в справочнике, у заказа будет невозможно определить зону(магистральный пояс)'),
                        'checkboxView' => array(1,0)
                    )),
                    'disable_status_graph' => new Type\Integer(array(
                        'description' => t('Отключить проверку очередности смены статусов'),
                        'hint' => t('Отключайте данную проверку на время самопроверки'),
                        'checkboxView' => array(1,0)
                    )),
                    'reserve_until_days' => new Type\Integer(array(
                        'description' => t('Количество дней, на которое товары могут быть зарезервированы в точке самовывоза'),
                    )),
                    'min_pickup_days' => new Type\Integer(array(
                        'description' => t('Минимальное количество дней доставки по умолчанию'),
                        'hint' => t('Используется, если служба доставки не вернула иное значение')
                    )),
                    'max_pickup_days' => new Type\Integer(array(
                        'description' => t('Максимальное количество дней доставки по умолчанию'),
                        'hint' => t('Используется, если служба доставки не вернула иное значение')
                    )),
            t('Оплаты'),
                    'payment_cash_on_delivery' => new Type\Integer(array(
                        'description' => t('Оплата наличными при получении'),
                        'list' => array(array('\Shop\Model\PaymentApi', 'staticSelectList'), array('0' => t('Не выбрано'))),
                    )),
                    'payment_card_on_delivery' => new Type\Integer(array(
                        'description' => t('Оплата картой при получении'),
                        'list' => array(array('\Shop\Model\PaymentApi', 'staticSelectList'), array('0' => t('Не выбрано'))),
                    )),
                    'payment_generic' => new Type\Integer(array(
                        'description' => t('Оплата в Яндексе'),
                        'list' => array(array('\Shop\Model\PaymentApi', 'staticSelectList'), array('0' => t('Не выбрано'))),
                    )),
            t('Статусы'),
                    'status_cancelled' => new Type\Integer(array(
                        'description' => t('Статус "отменен" (CANCELLED)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_cancelled_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "отменен" (CANCELLED) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),

                    'status_delivery' => new Type\Integer(array(
                        'description' => t('Статус "передан в доставку" (DELIVERY)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_delivery_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "передан в доставку" (DELIVERY) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),

                    'status_delivered' => new Type\Integer(array(
                        'description' => t('Статус "получен покупателем" (DELIVERED)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_delivered_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "получен покупателем" (DELIVERED) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),

                    'status_pickup' => new Type\Integer(array(
                        'description' => t('Статус "доставлен в пункт самовывоза" (PICKUP)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_pickup_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "доставлен в пункт самовывоза" (PICKUP) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),


                    'status_processing' => new Type\Integer(array(
                        'description' => t('Статус "заказ находится в обработке" (PROCESSING)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_processing_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "заказ находится в обработке" (PROCESSING) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),

                    'status_reserved' => new Type\Integer(array(
                        'description' => t('Статус "в резерве" (RESERVED)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_reserved_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "в резерве" (RESERVED) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),

                    'status_unpaid' => new Type\Integer(array(
                        'description' => t('Статус "оформлен, но не оплачен" (UNPAID)'),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'template' => '%yandexmarketcpa%/status.tpl'
                    )),
                    'status_unpaid_reverse' => new Type\ArrayList(array(
                        'description' => t('Статус "оформлен, но не оплачен" (UNPAID) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'hint' => $status_hint,
                        'visible' => false,
                        'runtime' => false
                    )),
            t('Причины отмены заказа'),
                    'substatus_processing_expired' => new Type\Integer(array(
                        'description' => t('Причина "Магазин не обработал заказ вовремя" (PROCESSING_EXPIRED)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_processing_expired_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Магазин не обработал заказ вовремя" (PROCESSING_EXPIRED) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_replacing_order' => new Type\Integer(array(
                        'description' => t('Причина "Покупатель изменяет состав заказа" (REPLACING_ORDER)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_replacing_order_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупатель изменяет состав заказа" (REPLACING_ORDER) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_reservation_expired' => new Type\Integer(array(
                        'description' => t('Причина "Покупатель не завершил оформление зарезервированного заказа вовремя" (RESERVATION_EXPIRED)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_reservation_expired_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупатель не завершил оформление зарезервированного заказа вовремя" (RESERVATION_EXPIRED) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_shop_failed' => new Type\Integer(array(
                        'description' => t('Причина "Магазин не может выполнить заказ" (SHOP_FAILED)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_shop_failed_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупатель не завершил оформление зарезервированного заказа вовремя" (SHOP_FAILED) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_user_changed_mind' => new Type\Integer(array(
                        'description' => t('Причина "Покупатель отменил заказ по собственным причинам" (USER_CHANGED_MIND)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_user_changed_mind_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупатель отменил заказ по собственным причинам" (USER_CHANGED_MIND) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_user_not_paid' => new Type\Integer(array(
                        'description' => t('Причина "Покупатель не оплатил заказ" (USER_NOT_PAID)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_user_not_paid_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупатель не оплатил заказ" (USER_NOT_PAID) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_user_refused_delivery' => new Type\Integer(array(
                        'description' => t('Причина "Покупателя не устраивают условия доставки" (USER_REFUSED_DELIVERY)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_user_refused_delivery_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупателя не устраивают условия доставки" (USER_REFUSED_DELIVERY) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_user_refused_product' => new Type\Integer(array(
                        'description' => t('Причина "Покупателю не подошел товар" (USER_REFUSED_PRODUCT)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_user_refused_product_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупателю не подошел товар" (USER_REFUSED_PRODUCT) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_user_refused_quality' => new Type\Integer(array(
                        'description' => t('Причина "Покупателя не устраивает качество товара" (USER_REFUSED_QUALITY)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_user_refused_quality_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Покупателя не устраивает качество товара" (USER_REFUSED_QUALITY) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

                    'substatus_user_unreachable' => new Type\Integer(array(
                        'description' => t('Причина "Не удалось связаться с покупателем" (USER_UNREACHABLE)'),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'template' => '%yandexmarketcpa%/substatus.tpl'
                    )),
                    'substatus_user_unreachable_reverse' => new Type\ArrayList(array(
                        'description' => t('Причина "Не удалось связаться с покупателем" (USER_UNREACHABLE) для обратного сопоставления'),
                        'attr' => array(array(
                            'size' => 4,
                            'multiple' => true,
                        )),
                        'list' => array(array('\Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                        'visible' => false,
                        'runtime' => false
                    )),

        ));
    }

    /**
     * Возвращает значения свойств по-умолчанию
     *
     * @return array
     */
    public static function getDefaultValues()
    {
        return parent::getDefaultValues() + array(
            'tools' => array(
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('runSelfTest', array(), 'yandexmarketcpa-tools'),
                    'title' => t('Отправить в Яндекс запрос на самопроверку'),
                    'description' => t('Во время <a target="_blank" href="https://tech.yandex.ru/market/partner/doc/dg/concepts/self-check-docpage/">самопроверки</a>, система произведет заказ, который нужно будет обработать как будето данный заказ пришел от реального пользователя.'),
                    'confirm' => t('Вы действительно желаете отправить запрос на самопроверку?')
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('requestTest', array(), 'yandexmarketcpa-tools'),
                    'title' => t('Выполнить произвольный запрос'),
                    'description' => t('Позволяет выполнить запрос cart, accept, order вручную. Необходимо для отладки'),
                    'class' => 'crud-add'
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('deleteLog', array(), 'yandexmarketcpa-tools'),
                    'title' => t('Очистить лог запросов'),
                    'description' => t('Удаляет лог файл'),
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('showLog', array(), 'yandexmarketcpa-tools'),
                    'title' => t('Просмотреть лог запросов'),
                    'description' => t('Открывает в новом окне журнал обмена данными с Яндексом'),
                    'target' => '_blank',
                    'class' => ' ',
                )
            )
        );
    }

    function beforeWrite($flag)
    {
        //Нельзя устанавливать одинаковые статусы для разных статусов Яндекс.Маркета
        $exists = array();
        $exists_reverse = array();

        $substatus_exists = array();
        $substatus_exists_reverse = array();
        foreach($this as $key => $property) {
            if (preg_match('/^status_([^_]+)$/', $key)) {
                if ($this[$key] > 0 && in_array($this[$key], $exists)) {
                    $this->addError(t('Данный статус уже используется'), $key);
                } else {
                    $exists[] = $this[$key];
                }
            }

            if (preg_match('/^status_(.*?)_reverse$/', $key)) {
                $no_select = (count($this[$key]) == 1) && ($this[$key][0] == "0");

                if ($this[$key] && !$no_select && array_intersect($this[$key], $exists_reverse)) {
                    $this->addError(t('Данный статус уже используется(RS->ЯМ)'), $key);
                } else {
                    if ($this[$key]) {
                        if (!$no_select) {
                            $exists_reverse = array_merge($exists_reverse, $this[$key]);
                        }
                    }
                }
            }

            if (preg_match('/^substatus_([^_]+)$/', $key)) {
                if ($this[$key] > 0 && in_array($this[$key], $substatus_exists)) {
                    $this->addError(t('Причина отмены заказа уже используется'), $key);
                } else {
                    $substatus_exists[] = $this[$key];
                }
            }

            if (preg_match('/^substatus_(.*?)_reverse$/', $key)) {
                $no_select = (count($this[$key]) == 1) && ($this[$key][0] == "0");

                if ($this[$key] && !$no_select && array_intersect($this[$key], $substatus_exists_reverse)) {
                    $this->addError(t('Причина отмены заказа(RS->ЯМ) уже используется'), $key);
                } else {
                    if ($this[$key]) {
                        if (!$no_select) {
                            $substatus_exists_reverse = array_merge($substatus_exists_reverse, $this[$key]);
                        }
                    }
                }
            }

        }

        if ($this->hasError()) {
            return false;
        }
    }

    /**
     * Возвращает ID Приложения ReadyScript Яндекса
     *
     * @return string
     */
    function getYandexAppId()
    {
        if (defined('CLOUD_UNIQ')) {
            //Для облачной версии
            return '2df59342581d41fba74d465f32107df8';
        }
        //Для коробочной версии
        return '875288c481b347ad94341a92af6062a3';
    }

    /**
     * Возвращает пароль приложения ReadyScript на Яндексе
     *
     * @return string
     */
    function getYandexAppSecret()
    {
        if (defined('CLOUD_UNIQ')) {
            //Для облачной версии
            return '53305d4bf70d476bb5cd3b4f6c8be533';
        }
        //Для коробочной версии
        return 'fa47178b530c4c3091c9f98a1e51b74d';
    }
}
