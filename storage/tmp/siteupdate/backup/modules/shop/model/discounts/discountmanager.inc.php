<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/ declare (strict_types=1);

namespace Shop\Model\Discounts;

use Catalog\Model\CostApi;
use Catalog\Model\Orm\Product;
use RS\Config\Loader as ConfigLoader;
use RS\Db\Exception as DbException;
use RS\Exception as RSException;
use RS\Orm\Exception as OrmException;
use Shop\Model\Orm\AbstractCartItem;
use Shop\Model\Orm\CartItem;

// TODO зарефакторить после рефакторинга комплектаций
class DiscountManager
{
    const DISCOUNT_COMBINATION_MAX = 'max';
    const DISCOUNT_COMBINATION_MIN = 'min';
    const DISCOUNT_COMBINATION_SUM = 'sum';

    protected $discount_combination_rule;

    /**
     * DiscountManager constructor.
     * @throws RSException
     */
    protected function __construct()
    {
        $shop_config = ConfigLoader::byModule(__CLASS__);
        /** @var string $discount_combination_rule */
        $discount_combination_rule = $shop_config['discount_combination'];
        $this->setDiscountCombinationRule($discount_combination_rule);
    }

    /**
     * Возвращает экземпляр класса
     *
     * @return static
     * @throws RSException
     */
    public static function instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * Возвращает иотговую скидку на товарную позицию
     *
     * @param AbstractCartItem $cart_item
     * @return float
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public function getCartItemFinalDiscount($cart_item): float
    {
        $base_cost = $this->getCartItemBaseCost($cart_item);
        $final_discount = round($this->calculateDiscountSum($cart_item->getDiscounts(), $base_cost), 2);

        return $final_discount;
    }

    /**
     * Возвращает базовую цену на товарную позицию
     *
     * @param AbstractCartItem $cart_item - товарная позиция
     * @return float
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public function getCartItemBaseCost(AbstractCartItem $cart_item): float
    {
        if ($cart_item instanceof CartItem) {
            $single_cost = $cart_item->getExtraParam(CartItem::EXTRA_KEY_PRICE);
            if (!$single_cost) {
                $product = $cart_item->getProduct();
                /** @var int $offer */
                $offer = (int)$cart_item['offer'];
                $single_cost = $this->getProductBaseCost($product, $offer);
            }
        } else {
            $single_cost = $cart_item['single_cost'];
        }

        return $single_cost * $cart_item['amount'];
    }

    /**
     * Возвращает базовую цену товара
     *
     * @param Product $product - товар
     * @param int $offer
     * @return float
     * @throws RSException
     * @throws DbException
     * @throws OrmException
     */
    public function getProductBaseCost(Product $product, int $offer = 0): float
    {
        $shop_config = ConfigLoader::byModule($this);
        $base_cost = (float)$product->getCost(CostApi::getUserCost(), $offer, false, true);
        if ($shop_config['old_cost_delta_as_discount']) {
            $old_cost = (float)$product->getCost(CostApi::getOldCostId(), $offer, false, true);
            if ($old_cost > $base_cost) {
                $base_cost = $old_cost;
            }
        }

        return $base_cost;
    }

    /**
     * Расчитывает суммарный размер скидки на основе переданного массиве скидок
     *
     * @param AbstractDiscount[] $discounts - массив скидок
     * @param float $base_cost - базовацена, от которой расчитывается скидка
     * @return float
     * @throws RSException
     */
    protected function calculateDiscountSum(array $discounts, $base_cost): float
    {
        $shop_config = ConfigLoader::byModule($this);
        $result = 0;
        $always_add_sum = 0;

        foreach ($discounts as $discount) {
            $discount_amount = $discount->getAmountOfDiscount();

            if ($discount->isFlagAlwaysAddDiscount()) {
                $always_add_sum += $discount_amount;
            } else {
                switch ($this->getDiscountCombinationRule()) {
                    case self::DISCOUNT_COMBINATION_MAX:
                        if ($discount_amount > $result) {
                            $result = $discount_amount;
                        }
                        break;
                    case self::DISCOUNT_COMBINATION_MIN:
                        if ($discount_amount > 0 && ($result == 0 || $discount_amount < $result)) {
                            $result = $discount_amount;
                        }
                        break;
                    case self::DISCOUNT_COMBINATION_SUM:
                        $result += $discount_amount;
                        break;
                }
            }
        }
        $result += $always_add_sum;

        $max_discount = $base_cost * ($shop_config['cart_item_max_discount'] / 100);
        if ($result > $max_discount) {
            $result = $max_discount;
        }

        return $result;
    }

    /**
     * Справочник правил сочетания скидок
     *
     * @return string[]
     */
    public static function handbookDiscountCombination(): array
    {
        return [
            self::DISCOUNT_COMBINATION_MAX => t('Применяется максимальная скидка'),
            self::DISCOUNT_COMBINATION_MIN => t('Применяется минимальная скидка'),
            self::DISCOUNT_COMBINATION_SUM => t('Скидки суммируются'),
        ];
    }

    /**
     * Возвращает правило совмещения скидок
     *
     * @return string
     */
    protected function getDiscountCombinationRule(): string
    {
        return $this->discount_combination_rule;
    }

    /**
     * Устанавливает правило совмещения скидок
     *
     * @param string $discount_combination_rule
     * @return void
     */
    protected function setDiscountCombinationRule(string $discount_combination_rule): void
    {
        $this->discount_combination_rule = $discount_combination_rule;
    }
}
