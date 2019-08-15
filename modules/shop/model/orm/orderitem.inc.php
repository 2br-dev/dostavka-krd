<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Shop\Model\Orm;

use Catalog\Model\Orm\Product;
use RS\Config\Loader as ConfigLoader;
use RS\Exception as RSException;
use RS\Orm\Type;

/**
 * Позиция в корзине
 */
class OrderItem extends AbstractCartItem
{
    const TYPE_COMMISSION = 'commission';
    const TYPE_TAX = 'tax';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_ORDER_DISCOUNT = 'order_discount';
    const TYPE_SUBTOTAL = 'subtotal';

    protected static $table = 'order_items';

    function _init()
    {
        parent::_init();

        $this->getPropertyIterator()->append(array(
            'order_id' => new Type\Integer(array(
                'description' => t('ID заказа'),
            )),
            'type' => new Type\Enum(array(
                self::TYPE_PRODUCT,
                self::TYPE_COUPON,
                self::TYPE_COMMISSION,
                self::TYPE_TAX,
                self::TYPE_DELIVERY,
                self::TYPE_ORDER_DISCOUNT,
                self::TYPE_SUBTOTAL), array(
                'description' => t('Тип записи товар, услуга, скидочный купон'),
                'index' => true
            )),
            'barcode' => new Type\Varchar(array(
                'description' => t('Артикул'),
                'maxLength' => 100,
            )),
            'model' => new Type\Varchar(array(
                'description' => t('Модель')
            )),
            'single_weight' => new Type\Double(array(
                'description' => t('Вес')
            )),
            'single_cost' => new Type\Decimal(array(
                'description' => t('Цена за единицу продукции'),
                'maxlength' => 20,
                'decimal' => 2
            )),
            'price' => new Type\Decimal(array(
                'description' => t('Стоимость'),
                'maxlength' => 20,
                'decimal' => 2,
                'default' => 0
            )),
            'profit' => new Type\Decimal(array(
                'description' => t('Доход'),
                'maxlength' => 20,
                'decimal' => 2,
                'default' => 0
            )),
            'discount' => new Type\Decimal(array(
                'description' => t('Скидка'),
                'maxlength' => 20,
                'decimal' => 2,
                'default' => 0
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядок')
            )),
        ));

        $this->addIndex(array('order_id', 'uniq'), self::INDEX_PRIMARY);
        $this->addIndex(array('type', 'entity_id'), self::INDEX_KEY);
    }

    /**
     * Возвращает первичный ключ.
     *
     * @return string[]
     */
    function getPrimaryKeyProperty(): array
    {
        return array('order_id', 'uniq');
    }

    /**
     * Вызывается перед сохранением объекта в storage
     * Если возвращено false, то сохранение не произойдет
     *
     * @param string $flag - insert|update|replace
     * @return void
     * @throws RSException
     */
    function beforeWrite($flag)
    {
        if (!$this->isModified('profit')) {
            $this['profit'] = $this->getProfit();
        }
        parent::beforeWrite($flag);
    }

    /**
     * Возвращает доход, от продажи товара в базовой валюте
     * Возвращает null, в случае если невозможно рассчитать доход для записи
     *
     * @return double|null
     * @throws RSException
     */
    function getProfit()
    {
        $config = ConfigLoader::byModule($this);
        if ($this['type'] == self::TYPE_PRODUCT && $config['source_cost']) {
            $product = new Product($this['entity_id']);

            if ($product['id']) {
                $sell_price = $this['price'] - $this['discount'];
                $source_cost = $product->getCost($config['source_cost'], $this['offer'], false, true);
                return $sell_price - ($source_cost * $this['amount']);
            }
        }
        return null;
    }

    /**
     * Возвращает объект заказа
     *
     * @return \Shop\Model\Orm\Order
     */
    function getOrder()
    {
        return new \Shop\Model\Orm\Order($this['order_id']);
    }
}
