<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;
use Shop\Model\Orm\UserStatus;

/**
 * Класс отвечает за инициализацию системных данных на новом сайте,
 * необходимых для работы модуля Заказы на Яндексе.
 */
class Initialize
{
    /**
     * ID сайта в контексте которого работает класс
     * @var integer
     */
    private
        $site_id;

    function __construct($site_id)
    {
        $this->site_id = $site_id;
    }

    /**
     * Создает все необходимое и устанавливает настройки модуля по-умолчанию
     *  TODO: почему то не присваивает статусы заказов
     * @return void
     */
    public function initializeSite()
    {
        $id_generic = $this->createPayment(t('Оплата через Яндекс ("Заказах на Яндекс")'), 'ym-generic');
        $id_cash = $this->createPayment(t('Оплата наличными при получении ("Заказы на Яндекс")'), 'ym-cash-on-delivery');
        $id_card = $this->createPayment(t('Оплата картой при получении ("Заказы на Яндекс")'), 'ym-card-on-delivery');

        //Определяем id существующих
        $status_cancelled = $this->getStatusIdIfExist(UserStatus::STATUS_CANCELLED);
        $status_delivered = $this->getStatusIdIfExist(UserStatus::STATUS_SUCCESS);
        $status_processing = $this->getStatusIdIfExist(UserStatus::STATUS_NEW);
        $status_unpaid = $this->getStatusIdIfExist(UserStatus::STATUS_WAITFORPAY);

        //Создаем недостающие
        $status_delivery = $this->createStatus(t('Заказ передан в доставку'), 'delivery', '#68d4b4');
        $status_pickup = $this->createStatus(t('Заказ доставлен в пункт самовывоза'), 'pickup', '#a1b019');
        $status_reserved = $this->createStatus(t('Заказ в резерве'), 'reserved', '#e07445');

        $config = \RS\Config\Loader::byModule($this, $this->site_id);

        $config['payment_cash_on_delivery'] = $id_cash;
        $config['payment_card_on_delivery'] = $id_card;
        $config['payment_generic'] = $id_generic;

        $config['status_cancelled'] = $status_cancelled;
        $config['status_cancelled_reverse'] = array($status_cancelled);

        $config['status_delivered'] = $status_delivered;
        $config['status_delivered_reverse'] = array($status_delivered);

        $config['status_processing'] = $status_processing;
        $config['status_processing_reverse'] = array($status_processing);

        $config['status_unpaid'] = $status_unpaid;
        $config['status_unpaid_reverse'] = array($status_unpaid);

        $config['status_delivery'] = $status_delivery;
        $config['status_delivery_reverse'] = array($status_delivery);

        $config['status_pickup'] = $status_pickup;
        $config['status_pickup_reverse'] = array($status_pickup);

        $config['status_reserved'] = $status_reserved;
        $config['status_pickup_reverse'] = array($status_reserved);

        //Причины отмены заказа
        foreach(YandexReference::getSubstatusTitles() as $key => $title) {
            $config['substatus_'.strtolower($key)] = $this->getSubStatusIdIfExist($key);
            $config['substatus_'.strtolower($key).'_reverse'] = array($this->getSubStatusIdIfExist($key));
        }

        //Устанавливаем произвольный API ключ, при установке модуля
        $config['secret_part_url'] = \RS\Helper\Tools::generatePassword(8, array_merge(range('a', 'z'), range('0', '9')));
        $config->update();
    }

    /**
     * Создает способ оплаты
     *
     * @param string $title Название способы оплаты
     * @param string $type Тип расчетного класса
     * @return integer
     */
    protected function createPayment($title, $type)
    {
        $payment = \Shop\Model\Orm\Payment::loadByWhere(array(
            'class' => $type,
            'site_id' => $this->site_id
        ));
        if (!$payment['id']) {
            $payment['site_id'] = $this->site_id;
            $payment['title'] = $title;
            $payment['class'] = $type;
            $payment['public'] = 0;
            $payment->insert();
        }

        return $payment['id'];
    }

    /**
     * Создает новый статус
     *
     * @param string $title Название статуса
     * @param string $type Строковый идентификатор статуса
     * @return integer
     */
    protected function createStatus($title, $type, $color)
    {
        $status = \Shop\Model\Orm\UserStatus::loadByWhere(array(
            'site_id' => $this->site_id,
            'type' => $type
        ));

        if (!$status['id']) {
            $status['site_id'] = $this->site_id;
            $status['title'] = $title;
            $status['type'] = $type;
            $status['bgcolor'] = $color;
            $status['is_system'] = 1;
            $status->insert();
        }

        return $status['id'];
    }


    /**
     * Получаем первый статус из всех которые есть с заданным типом
     *
     * @param string $type Строковый идентификатор статуса
     * @return integer Возвращает 0, если статус не найден
     */
    protected function getStatusIdIfExist($type)
    {
        $status = \RS\Orm\Request::make()
            ->select('id')
            ->from(new \Shop\Model\Orm\UserStatus)
            ->where(array(
                'site_id' => $this->site_id,
                'type' => $type
            ))
            ->object();

        return $status ? $status['id'] : 0;
    }

    /**
     * Получаем ID причины отмены заказа, подходящей для Яндекса
     *
     * @param string $type Строковый идентификатор статуса
     * @return integer Возвращает 0, если статус не найден
     */
    protected function getSubStatusIdIfExist($yandex_key)
    {
        $substatus = \RS\Orm\Request::make()
            ->select('id')
            ->from(new \Shop\Model\Orm\SubStatus)
            ->where(array(
                'site_id' => $this->site_id,
                'alias' => strtolower($yandex_key)
            ))
            ->object();

        return $substatus ? $substatus['id'] : 0;
    }


}