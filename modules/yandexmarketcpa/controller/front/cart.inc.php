<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Controller\Front;

use YandexMarketCpa\Model\YandexUtils;

class Cart extends AbstractYandexController
{
    protected $log;

    /**
     * Принимает запросы о наличии и стоимости товаров от Яндекс.маркета /cart
     */
    function actionIndex()
    {
        $yu = new YandexUtils();
        $flags = defined('JSON_UNESCAPED_UNICODE') ?  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : null;
        $res= json_encode((array)$yu->buildYCart(), $flags);
        return $res;
    }
}