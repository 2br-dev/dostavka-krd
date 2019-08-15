<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;

use \YandexMarketCpa\Model\YandexType\YItemFactory;

/**
 * Класс товаров в корзине Яндекс.Маркет
 */
class YItems
{
    /**
     * объект от Яндекса
     * @var
     */
    public $yandex_cart_object;
    public $scope;
    private $scanned_items;

    public function __construct($scope, $yandexObject)
    {
        $this->setScope($scope);
        $this->setYandexCartObject($yandexObject);
    }

    /**
     * Получваем контекст Корзина или Заказ
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Устанавливаем контекст Корзина или Заказ
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Получаем объект от Яндекса
     * @return array
     */
    public function getYandexCartObject()
    {
        return $this->yandex_cart_object;
    }

    /**
     * Устанавливаем объект от яндекса
     * @param mixed $yandex_cart_object
     */
    public function setYandexCartObject($yandex_cart_object)
    {
        $this->yandex_cart_object = $yandex_cart_object;
    }

    /**
     * Спарсить список заказов из Яндекс
     * @param string $scope
     * @return array список итемов заказа или корзины от Яндекса
     * @throws Exception
     */
    private function parseItems($scope = 'cart')
    {
        switch ($scope) {
            case 'cart':
                if (!empty($this->yandex_cart_object[$scope])) {
                    $items = $this->yandex_cart_object[$scope]['items'];
                }

                break;
            case 'order':
                if (!empty($this->yandex_cart_object[$scope])) {
                    $items = $this->yandex_cart_object[$scope]['items'];
                }
                break;
        }
        if (!empty($items)) {
            return $items;
        } else {
            throw  new  Exception('empty items object');
        }
    }

    /**  Получаем массив товаров в формате rs
     * @param string $scope 'cart' or 'order'
     * @return array
     * @throws \Yandexmarketcpa\model\Exception
     */
    public function getParsedItems($scope = 'cart')
    {
        $items = array();
        $yitems = $this->parseItems($scope);
        foreach ($yitems as $yi) {
            $items[$yi['offerId']] = array(
                'count' => $yi['count'],
                'productId' => $this->parseProductId($yi['offerId']),
                'offerId' => $this->parseOfferId($yi['offerId']),
                'feedId' => $yi['feedId']);
        }
        $this->scanned_items = $items;

        return $items;
    }

    /**
     * Возвращает ID товара из ID предложения Яндекса
     *
     * @return integer
     */
    private function parseProductId($y_offer_id)
    {
        $parts = explode('x', $y_offer_id);
        return $parts[0];
    }

    /**
     * Возвращает ID комплектации товара из ID предложения Яндекса
     *
     * @return integer
     */
    private function parseOfferId($y_offer_id)
    {
        $parts = explode('x', $y_offer_id);
        return isset($parts[1]) ? (int)$parts[1] : 0;
    }

    /**
     * Получить данные итемов из бд, отдать в формате Yandex
     * @return array of YTypeItem
     */
    private function scanItems()
    {
        $items = $this->getParsedItems($this->scope);
        $ids = array();
        foreach ($items as $id => $data) {
            $ids[] = $data['productId'];
        }
        if (count($ids) == 0) {
            return array();
        }

        /**
         * @var $rs_items \Catalog\Model\Orm\Product[]
         */
        $rs_items = $this->getProductsInfo($ids);

        $check_quantity = \RS\Config\Loader::byModule('shop')->check_quantity; //Опция - запретить оформление заказа, если товаров недостаточно на складе
        $res = array();

        $any_product_exists = false;
        foreach ($items as $id => $data) {
            if (isset($rs_items[$data['productId']])) {
                //Количество товаров данной комплектации
                $offer_amount = $rs_items[$data['productId']]->getNum($data['offerId']);
                if ($offer_amount < 0) {
                    $offer_amount = 0;
                }
                $price = (float)$rs_items[$data['productId']]->getCost(\Catalog\Model\CostApi::getDefaultCostId(), $data['offerId'], false, true);

                if ($check_quantity) {
                    //Нельзя купить больше, чем есть на складе
                    $amount = $data['count'] > $offer_amount ? $offer_amount : $data['count'];
                } else {
                    //Товары всегда доступны в полном объеме
                    $amount = $data['count'];
                }

                if ($amount) {
                    $any_product_exists = true;
                }

                $res[] = YItemFactory::create($price, $amount, $id, true, (int)$data['feedId']);
            }
        }

        //Возвращаем пустой массив, если ни одного товара нет в наличии согласно инструкции Яндекса
        return $any_product_exists ? $res : array();
    }

    /**
     * Получаем данные из БД по ид товаров
     * @param array $ids
     * @return array инфы о товарах
     * @throws \RS\Orm\Exception
     */
    private function getProductsInfo($ids = array())
    {
        $api = new \Catalog\Model\Api();
        $api->setFilter('id', $ids, 'in');
        $products = $api->getAssocList('id');
        $products = $api->addProductsCost($products);
        $products = $api->addProductsOffers($products);
        return $products;
    }

    /**
     * Получить список товаров
     * @return array товаров
     */
    public function getItemsArray()
    {
        $result_array = array();
        foreach ($this->scanItems() as $item) {
            $result_array[] = $item->expose();
        }
        return $result_array;
    }
}