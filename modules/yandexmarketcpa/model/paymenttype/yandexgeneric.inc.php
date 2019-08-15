<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model\PaymentType;
use RS\Orm\FormObject;
use \Shop\Model\Orm\Transaction;
use \Shop\Model\PaymentType\AbstractType;


/**
 * Тип оплаты - оплата на Яндексе
 */
class YandexGeneric extends AbstractType
{
    const
        ENTITY_YANDEX_MARKET_GENERIC = 'ym-generic';

    /**
     * Возвращает название расчетного модуля (типа доставки)
     *
     * @return string
     */
    function getTitle()
    {
        return t('Оплата в "Заказы на Маркете"');
    }

    /**
     * Возвращает описание типа оплаты для администратора. Возможен HTML
     *
     * @return string
     */
    function getDescription()
    {
        return t('Данный тип используется в модуле Заказы на Маркете');
    }

    /**
     * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
     *
     * @return string
     */
    function getShortName()
    {
        return 'ym-generic';
    }

    /**
     * Возвращает true, если данный тип поддерживает проведение платежа через интернет
     *
     * @return bool
     */
    function canOnlinePay()
    {
        return false;
    }

    /**
     * Возвращает true, если можно обращаться к ResultUrl для данного метода оплаты.
     * Обычно необходимо для способов оплаты, которые применяются только на мобильных приложениях.
     * По умолчанию возвращает то же, что и canOnlinePay.
     *
     * @return bool
     */
    function isAllowResultUrl()
    {
        return false;
    }

    /**
     * Возвращает ORM объект для генерации формы в административной панели
     *
     * @return \RS\Orm\FormObject
     */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array());

        $form_object = new FormObject($properties);
        $form_object->setParentObject($this);
        $form_object->setParentParamMethod('Form');
        return $form_object;
    }


    /**
     * Возвращае ID транзакции
     *
     * @param mixed $request
     * @return bool
     */
    function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        return false;
    }

    /**
     * Вызывается при оплате сервером платежной системы.
     * Возвращает строку - ответ серверу платежной системы.
     * В случае неверной подписи бросает исключение
     * Используется только для Online-платежей
     *
     * @param Transaction $transaction
     * @param \RS\Http\Request $request
     * @return string
     */
    function onResult(Transaction $transaction, \RS\Http\Request $request)
    {
        return true;
    }
}