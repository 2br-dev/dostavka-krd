<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model;

/**
 * Класс предоставляет API связанные со счетчиком для CRUD контроллера
 */
class EventMeterApi implements \Main\Model\NoticeSystem\MeterApiInterface
{
    protected
        $event_api;

    function __construct($event_api)
    {
        $this->event_api = $event_api;
    }

    /**
     * Возвращает идентификатор счетчика
     *
     * @return string
     */
    function getMeterId()
    {
        return 'rs-admin-menu-antivirus-events';
    }

    /**
     * Возвращает количество непросмотренных объектов
     *
     * @param integer|null $user_id
     * @return integer
     */
    function getUnviewedCounter()
    {
        return $this->event_api->getUnreadEventCount();
    }

    /**
     * Отмечает просмотренным один объект
     *
     * @param mixed $ids
     * @param integer|null $user_id
     * @return bool
     */
    function markAsViewed($ids)
    {
        return $this->event_api->markAsViewed(null, (array)$ids);
    }

    /**
     * Отмечает просмотренными все объекты
     *
     * @param integer|null $user_id
     * @return bool
     */
    function markAllAsViewed()
    {
        return $this->event_api->markAsViewed();
    }

    /**
     * Удаляет сведения о просмотрах объектов
     *
     * @return bool
     */
    function removeViewedFlag($ids)
    {
        return 0;
    }
}