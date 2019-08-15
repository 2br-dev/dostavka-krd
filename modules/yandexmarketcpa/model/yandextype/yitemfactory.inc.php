<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model\YandexType;


/**
 * Фабрика товарных предложений
 */
class YItemFactory
{
    public static function create($price, $count, $offerId, $delivery = true, $feedId)
    {
        return new YTypeItem($price, $count, $offerId, $delivery, $feedId);
    }
}