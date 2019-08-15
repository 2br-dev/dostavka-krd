<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Shop\Model\DeliveryType;

use RS\Module\AbstractModel\TreeList\AbstractTreeListIterator;
use RS\Orm\FormObject;
use RS\Orm\PropertyIterator;
use RS\Orm\Type;
use Shop\Model\Orm\Address;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Order;

/**
 * Тип доставки - Самовывоз. стоимость - 0
 */
class Myself extends AbstractType implements InterfaceIonicMobile
{
    /**
     * Возвращает название
     *
     * @return string
     */
    function getTitle()
    {
        return t('Самовывоз');
    }

    /**
     * Возвращает ORM объект для генерации формы или null
     *
     * @return \RS\Orm\FormObject | null
     */
    function getFormObject()
    {
        $properties = new PropertyIterator(array(
            'myself_addr' => new Type\Integer(array(
                'description' => t('Месторасположение пункта самовывоза'),
                'maxLength' => '11',
                'tree' => array(array('\Shop\Model\RegionApi', 'staticTreeList')),
                'attr' => array(array(
                    AbstractTreeListIterator::ATTRIBUTE_DISALLOW_SELECT_BRANCHES => true,
                )),
            )),
            'pvz_list' => new Type\ArrayList(array(
                'description' => t('Доступные пункты самовывоза'),
                'hint' => t('Если не указаны, используются все пуннкты'),
                'list' => array(array('\Catalog\Model\WareHouseApi', 'staticPickupPointsSelectList'), array(0 => t('- Все -'))),
                'runtime' => false,
                'attr' => array(array(
                    'multiple' => true
                )),
            )),
        ));

        $form_object = new FormObject($properties);
        $form_object->setParentObject($this);
        $form_object->setParentParamMethod('Form');
        return $form_object;
    }

    /**
     * Возвращает описание
     *
     * @return string
     */
    function getDescription()
    {
        return t('Не предполагает взимание оплаты');
    }

    /**
     * Возвращает короткое системное имя
     *
     * @return string
     */
    function getShortName()
    {
        return 'myself';
    }

    /**
     * Возвращает стоимость доставки для заданного заказа. Только число.
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Address $address - адрес доставки
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @param boolean $use_currency - использовать валюту?
     * @return double
     * @throws \RS\Event\Exception
     */
    function getDeliveryCost(Order $order, Address $address = null, Delivery $delivery, $use_currency = true)
    {
        $cost = $this->applyExtraChangeDiscount($delivery, 0, $order); //Добавим наценку или скидку
        if ($use_currency) {
            $cost = $order->applyMyCurrency($cost);
        }
        return $cost;
    }

    /**
     * Возвращает true, если тип доставки предполагает самовывоз
     *
     * @return bool
     */
    function isMyselfDelivery()
    {
        return true;
    }

    /**
     * Возвращает HTML для приложения на Ionic
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @return string
     */
    function getIonicMobileAdditionalHTML(Order $order, Delivery $delivery)
    {
        return "";
    }
}
