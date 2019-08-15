<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model\PaymentType;

/**
 * Тип оплаты - наличными при получении
 */
class YandexCashOnDelivery extends YandexGeneric
{

    /**
     * Возвращает название расчетного модуля (типа доставки)
     *
     * @return string
     */
    function getTitle()
    {
        return t('Оплата наличными при получении в "Заказы на Маркете"');
    }

    /**
     * Возвращает описание типа оплаты для администратора. Возможен HTML
     *
     * @return string
     */
    function getDescription()
    {
        return t('Данный тип используется в модуле Заказы на Маркете');
    }

    /**
     * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
     *
     * @return string
     */
    function getShortName()
    {
        return 'ym-cash-on-delivery';
    }

}