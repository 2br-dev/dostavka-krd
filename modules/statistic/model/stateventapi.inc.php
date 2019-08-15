<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model;
use Main\Model\StatisticEvents;
use RS\Module\AbstractModel\EntityList;
use RS\Orm\Request;
use Statistic\Model\Orm\StatEvent;

class StatEventApi extends EntityList
{
    static private 
            $instance;

    static public function getInstance()
    {
        if(self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    function __construct()
    {
        parent::__construct(new StatEvent, array(
            'defaultOrder' => 'id desc'
        ));
    }

    /**
     * Зарегистрировать событие данного типа
     *
     * @param $event_type
     * @throws \Exception
     * @throws \RS\Orm\Exception
     */
    public function registerEvent($event_type, $site_id = null)
    {
        $site_id = $site_id ?: \RS\Site\Manager::getSiteId();
        $types = StatisticEvents::getTypeList();

        if(!isset($types[$event_type])) {
            throw new \Exception(t('Попытка добавить незарегистрированный тип события \'%0\'', $event_type));
        }

        $cur_date = date('Y-m-d');
        $event = Request::make()->from(new StatEvent)
            ->where(array(
                'site_id' => $site_id, 
                'dateof' => $cur_date, 
                'type' => $event_type
            ))
            ->object();

        if(!$event) {
            $event              = new StatEvent;
            $event['site_id']   = $site_id;
            $event['dateof']    = $cur_date;
            $event['type']      = $event_type;
            $event->insert();
        } else {
            $event['count'] += 1;
            $event->update();
        }
    }

    /**
     * Получить список счетчиков событий за данный период
     *
     * @param $date_begin
     * @param $date_end
     * @param array $event_types
     * @return array [type => sum(count)]
     */
    public function getEventCountsInDate($date_begin, $date_end, array $event_types, $site_id = null)
    {
        $rows = Request::make()
            ->select('`type`, SUM(`count`) as count_sum')
            ->from(new StatEvent)
            ->where(array(
                'site_id' => $site_id ?: \RS\Site\Manager::getSiteId()
            ))
            ->where("`dateof` >= '{$date_begin}'")
            ->where("`dateof` <= '{$date_end}'")
            ->whereIn('type', $event_types)
            ->groupby('type')
            ->exec()
            ->fetchSelected('type', 'count_sum');

        return Utils::sortArrayByArray($rows, $event_types);
    }
}