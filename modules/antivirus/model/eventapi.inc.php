<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model;


use Antivirus\Model\Orm\Event;
use RS\Module\AbstractModel\EntityList;

class EventApi extends EntityList
                implements \Main\Model\NoticeSystem\HasMeterInterface
{
    function __construct()
    {
        parent::__construct(new Event, array(
            'defaultOrder' => 'id desc'
        ));
    }

    /**
     * Возвращает API по работе со счетчиками
     *
     * @return \Main\Model\NoticeSystem\MeterApiInterface
     */
    function getMeterApi()
    {
        return new EventMeterApi($this);
    }

    /**
     * @param string $component
     * @param string $type
     * @param string $file
     * @return bool
     */
    public function eventExists($component, $type, $file)
    {

        $exists = (boolean) \RS\Orm\Request::make()->from(new Event())
            ->where(array(
                'component' => $component,
                'type' => $type,
                'file' => $file,
            ))
            ->count();
        return $exists;
    }

    public function getUnreadEventCount($component = null, $update_meter = false)
    {
        $req = \RS\Orm\Request::make();
        $req->from(new Event());
        //$req->where(array('type' => Event::TYPE_PROBLEM));
        $req->where(array('viewed' => 0));

        if($component !== null)
        {
            $req->where(array('component'=>$component));
        }

        $counter = $req->count();
        if ($component === null && $update_meter) {
            //Обновляем хранилище счетчиков на сервере
            $meters = \Main\Model\NoticeSystem\Meter::getInstance();
            $meters->updateNumber($this->getMeterApi()->getMeterId(), $counter);
        }

        return $counter;
    }

    public function getUnreadAttacksIps()
    {
        $offset = 0;
        $limit = 100;

        $req = \RS\Orm\Request::make();
        $req->from(new Event());
        $req->where(array(
            'viewed' => 0,
            'component' => Event::COMPONENT_PROACTIVE,
        ));
        $req->limit($offset, $limit);

        $ips = array();

        while($events = $req->objects()) {
            foreach ($events as $event) {
                $details = $event->getDetails();
                if ($details instanceof MaliciousRequestInfo || $details instanceof RequestCountAttackInfo) {
                    $ips[] = $details->ip;
                }
            }
            $offset += $limit;
            $req->offset($offset);
        }

        return array_unique($ips);
    }

    public function markAsViewed($component = null, array $ids = null)
    {
        $req = \RS\Orm\Request::make();
        $req->from(new Event());
        $req->where(array(
            'viewed' => 0
        ));
        
        if ($component) {
            $req->where(array('component' => $component));
        }
        
        if ($ids) {
            $req->whereIn('id', $ids);
        }
        
        $req->set(array('viewed' => 1));
        $req->update()->exec();

        $new_counter = $this->getUnreadEventCount(null, true);

        return $new_counter;
    }
}