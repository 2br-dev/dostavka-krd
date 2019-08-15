<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Shop\Model;

use Catalog\Model\Orm\Product;
use RS\Db\Exception as DbException;
use RS\Exception as RSException;
use RS\Module\AbstractModel\EntityList;
use RS\Orm\Exception as OrmException;
use RS\Orm\Request as OrmRequest;
use Shop\Model\Orm\Address;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Tax;
use Users\Model\Orm\User;

/**
 * API функции для работы с налогами
 */
class TaxApi extends EntityList
{
    protected static $all_tax_ids;
    protected static $cache_dir_tax;
    protected static $cache_tax;

    function __construct()
    {
        parent::__construct(new Tax(), array(
            'nameField' => 'title',
        ));
    }

    /**
     * Возвращает id налогов, которые могут быть применены к товару
     *
     * @param Product $product
     * @return array
     */
    public static function getProductTaxIds(Product $product)
    {
        $tax_ids = $product['tax_ids'];
        if ($tax_ids == 'category') {
            $dir_id = $product['maindir'];
            if (!isset(self::$cache_dir_tax[$dir_id])) {
                $main_dir = $product->getMainDir();
                self::$cache_dir_tax[$dir_id] = $main_dir['tax_ids'];
            }
            $tax_ids = self::$cache_dir_tax[$dir_id];
        }

        if ($tax_ids == 'all') {
            if (!isset(self::$all_tax_ids)) {
                self::$all_tax_ids = array_keys(self::staticSelectList());
            }
            $ids = self::$all_tax_ids;
        } else {
            $ids = explode(',', $tax_ids);
        }
        return $ids;
    }

    /**
     * Возвращает список налогов, которые применяются к товару
     *
     * @param Product $product - загруженный товар
     * @param User $user
     * @param Address $address - адрес, который необходим для расчета налогов
     * @param int[] $tax_id_list
     * @return Tax[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public static function getProductTaxes(Product $product, User $user, Address $address, array $tax_id_list = null)
    {
        if ($tax_id_list === null) {
            $tax_id_list = self::getProductTaxIds($product);
        }
        return self::getTaxesByIds($tax_id_list, $user, $address);
    }

    /**
     * Возвращает список налогов, которые применяются к доставке
     *
     * @param Delivery $delivery - доставка
     * @param User $user
     * @param Address $address - адрес, который необходим для расчета налогов
     * @param int[] $tax_id_list
     * @return Tax[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public static function getDeliveryTaxes(Delivery $delivery, User $user, Address $address, array $tax_id_list = null)
    {
        if ($tax_id_list === null) {
            $tax_id_list = ($delivery['tax_ids']) ?: array();
        }
        return self::getTaxesByIds($tax_id_list, $user, $address);
    }

    /**
     * Возвращает список налогов до списку id
     *
     * @param array $tax_id_list - id налогов
     * @param User $user
     * @param Address $address - адрес, который необходим для расчета налогов
     * @return Tax[]
     * @throws OrmException
     * @throws DbException
     * @throws RSException
     */
    public static function getTaxesByIds(array $tax_id_list, User $user, Address $address)
    {
        $address_id = $address['country_id'] . ':' . $address['region_id'];
        $tax_ids = implode(',', $tax_id_list);
        if (!isset(self::$cache_tax[$address_id][$tax_ids])) {
            self::$cache_tax[$address_id][$tax_ids] = array();
            if (count($tax_id_list)) {
                /** @var Tax[] $taxes */
                $taxes = OrmRequest::make()
                    ->from(new Tax())
                    ->whereIn('id', $tax_id_list)
                    ->objects();

                foreach ($taxes as $tax) {
                    if ($tax->canApply($user, $address)) {
                        self::$cache_tax[$address_id][$tax_ids][] = $tax;
                    }
                }
            }
        }
        return self::$cache_tax[$address_id][$tax_ids];
    }
}
