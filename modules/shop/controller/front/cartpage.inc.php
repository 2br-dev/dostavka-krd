<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Shop\Controller\Front;

use Main\Model\StatisticEvents;
use RS\Application\Application;
use RS\Controller\Front;
use RS\Db\Exception as DbException;
use RS\Event\Manager as EventManager;
use RS\Exception as RSException;
use RS\Orm\Exception as OrmException;
use Shop\Model\Cart;

/**
 * Просмотр корзины
 */
class CartPage extends Front
{
    const CART_SOURCE_CART_PAGE = 'cart_page'; // источник "товар добавлен вручную"

    /** @var Cart $cart */
    public $cart;

    function init()
    {
        $this->cart = Cart::currentCart();
    }

    /**
     * Корзина
     */
    function actionIndex()
    {
        $id = $this->url->request('add', TYPE_INTEGER);       //id товара
        $amount = $this->url->request('amount', TYPE_FLOAT);    //Количество
        $offer = $this->url->request('offer', TYPE_STRING);      //Комплектация
        $multioffers = $this->url->request('multioffers', TYPE_ARRAY); //Многомерные комплектации
        $concomitants = $this->url->request('concomitant', TYPE_ARRAY); //Сопутствующие товары
        $concomitants_amount = $this->url->request('concomitant_amount', TYPE_ARRAY); //Количество сопутствующих твоаров
        $additional_uniq = $this->url->request('uniq', TYPE_STRING); // Дополнительный унификатор товара
        $checkout = $this->url->request('checkout', TYPE_BOOLEAN);

        $this->app->breadcrumbs->addBreadCrumb(t('Корзина'));
        $this->app->title->addSection(t('Корзина'));

        if (!empty($id)) {

            $this->cart->addProduct($id, $amount, $offer, $multioffers, $concomitants, $concomitants_amount, $additional_uniq, self::CART_SOURCE_CART_PAGE, true);

            if (!$this->url->isAjax()) {
                Application::getInstance()->redirect($this->router->getUrl('shop-front-cartpage'));
            }
        }

        $cart_data = $this->cart->getCartData();

        $this->view->assign(array(
            'cart' => $this->cart,
            'cart_data' => $cart_data,
        ));

        if ($checkout && !$cart_data['has_error']) {
            // Фиксация события "Начало оформления заказа" для статистики
            EventManager::fire('statistic', array('type' => StatisticEvents::TYPE_SALES_CART_SUBMIT));

            $this->result->setRedirect($this->router->getUrl('shop-front-checkout'));
        }

        $this->result->addSection('cart', array(
            'can_checkout' => !$cart_data['has_error'],
            'total_unformated' => $cart_data['total_unformatted'],
            'total_price' => $cart_data['total'],
            'items_count' => $cart_data['items_count'],
        ));

        return $this->result->setTemplate('cartpage.tpl');
    }

    /**
     * Обновляет информацию о товарах, их количестве в корзине. Добавляет купон на скидку, если он задан
     */
    function actionUpdate()
    {
        if ($this->url->isPost()) {
            $products = $this->url->request('products', TYPE_ARRAY);
            $coupon = trim($this->url->request('coupon', TYPE_STRING));
            $apply_coupon = $this->cart->update($products, $coupon, true, true);

            if ($apply_coupon !== true) {
                $this->cart->addUserError($apply_coupon, false, 'coupon');

                $this->view->assign(array(
                    'coupon_code' => $coupon,
                ));
            }
        }

        return $this->actionIndex();
    }

    /**
     * Удаляет товар из корзины
     */
    function actionRemoveItem()
    {
        $uniq = $this->url->request('id', TYPE_STRING);
        $this->cart->removeItem($uniq, true);
        return $this->actionIndex();
    }

    /**
     * Очищает корзину
     *
     * @return void
     * @throws OrmException
     * @throws RSException
     * @throws DbException
     */
    function actionCleanCart()
    {
        $this->cart->clean();
        return $this->actionIndex();
    }

    /**
     * Повторяет предыдущий заказ
     *
     * @return void
     * @throws RSException
     * @throws OrmException
     */
    function actionRepeatOrder()
    {
        $order_num = $this->url->request('order_num', TYPE_STRING, false); //Номер заказа

        if ($order_num) { //Если заказ найден, повторим его и переключимся в корзину
            $this->getCart()->repeatCartFromOrder($order_num);
        }
        Application::getInstance()->redirect($this->router->getUrl('shop-front-cartpage'));
    }

    /**
     * Возвращает корзину
     *
     * @return Cart
     */
    function getCart()
    {
        return $this->cart;
    }
}
