<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Controller\Front;

use RS\Controller\Front;
use YandexMarketCpa\Model\YandexUtils;

class Order extends AbstractYandexController
{
    /**
     * Принимает запрос на резервирование товаров (по сути создание заказа, но пока без подробных данных о покупателе)
     */
    function actionAccept()
    {
        $yu = new YandexUtils();
        $accpet = $yu->buildYAccept();

        $flags = defined('JSON_UNESCAPED_UNICODE') ?  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : null;
        return json_encode($accpet, $flags);
    }

    /**
     * Принимает запрос на изменение заказа
     */
    function actionStatus()
    {
        $yu = new YandexUtils();

        $user_id = $yu->getOrCreateUserId();
        $y_order_id = $yu->getYOrderId();
        $y_status_id = $yu->getOrderStatus();
        
        //Проверим был ли у заказа user_id, если нет и появился, то отправим уведомление
        if ($user_id){
            $order = \Shop\Model\Orm\Order::loadByWhere(array(
                'id_yandex_market_cpa_order' => $y_order_id
            ));
            
            if ($order['id'] && !$order['user_id']){ 
                //Отправляем уведомление 
                $notice = new \YandexMarketCpa\Model\Notice\OrderSuccess();
                $notice->init($order);
                \Alerts\Model\Manager::send($notice); 
            }
        }

        if ($yu->updateOrder($y_order_id, $y_status_id, $user_id)) {
            return "OK";
        } else {
            return "Fail: ".$yu->getErrorsStr();

        }
    }
}