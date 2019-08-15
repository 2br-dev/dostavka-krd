<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model\YandexType;

/**
 * Класс описывает один товар на Яндекс.Маркете
 */
class YTypeItem
{
    /**
     * Количество товара, которое доступно для заказа.
     * @var Integer
     */
    public $count;

    /**
     * Признак возможности доставки товара в указанный в запросе регион либо по указанному в запросе адресу.
     * @var Boolean
     */
    public $delivery;

    /**
     * Идентификатор прайс-листа, в котором указан товар.
     * @var integer
     */
    public $feedId;

    /**
     * Идентификатор товара из прайс-листа.
     * @var String
     */
    public $offerId;

    /**
     * Актуальная цена товара в валюте корзины.
     * @var Float
     */
    public $price;


    public function __construct($price, $count, $offerId, $delivery = true, $feedId = '')
    {
        $this->setPrice($price);
        $this->setCount($count);
        $this->setOfferId($offerId);
        $this->setDelivery($delivery);
        $this->setFeedId($feedId);
    }

    /**
     * @return int
     */
    private function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    private function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return boolean
     */
    public function isDelivery()
    {
        return $this->delivery;
    }

    /**
     * @param boolean $delivery
     */
    private function setDelivery($delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * @return int
     */
    private function getFeedId()
    {
        return $this->feedId;
    }

    /**
     * @param int $feedId
     */
    private function setFeedId($feedId)
    {
        $this->feedId = $feedId;
    }

    /**
     * @return String
     */
    private function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * @param String $offerId
     */
    private function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }

    /**
     * @return Float
     */
    private function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Float $price
     */
    private function setPrice($price)
    {
        $this->price = $price;
    }


    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getJson()
    {
        return json_encode($this->expose());
    }
}