<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

use RS\Module\AbstractModel\EntityList;
use RS\Orm\Request as OrmRequest;
use Shop\Model\Orm\Xregion;
use Shop\Model\Orm\Zone;

class ZoneApi extends EntityList
{
    function __construct()
    {
        parent::__construct(new Zone(), array(
            'nameField' => 'title',
            'multisite' => true,
            'defaultOrder' => 'title'
        ));
    }

    static public function getZonesByRegionId($region_id, $country_id = null, $city_id = null)
    {
        $q = OrmRequest::make()
            ->from(new Xregion())
            ->where('zone_id IS NOT NULL')
            ->where(array('region_id' => $region_id));

        if ($country_id) {
            $q->where(array('region_id' => $country_id), null, 'OR');
        }

        if ($city_id) {
            $q->where(array('region_id' => $city_id), null, 'OR');
        }

        return $q->exec()->fetchSelected(null, 'zone_id');
    }

    static public function getZoneByTitle($zone_title)
    {
        return Zone::loadByWhere(array('title' => $zone_title));
    }
}
