<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;

use RS\Config\Loader;
use RS\Module\AbstractModel\BaseModel;
use Shop\Model\DeliveryType\Helper\DeliveryPeriod;
use Shop\Model\Orm\Address;

/**
 * Класс позволяет накладывать выходные и праздничные дни,
 * полученные из Яндекс.Маркета на срок доставки, тем самым не позволять
 * рассчитывать некорректные даты доставки
 */
class HolidayMapper extends BaseModel
{
    const
        HASHSTORE_PREFIX = 'YM_PREFIX';

    private
        $config,
        $settings,
        $yandex_request;

    function __construct()
    {
        $this->config = Loader::byModule($this);
        $this->yandex_request = new YRequestUtils($this->config['ytoken'], $this->config->getYandexAppId());
    }

    /**
     * Возвращает настройки кампании в Яндекс.Маркете
     *
     * @return array | false
     */
    protected function refreshSettings()
    {
        $this->cleanErrors();
        $settings = $this->yandex_request->getCampaignSettings($this->config['campaign_id']);

        if ($settings) {
            $key = $this->getHashStoreKey();
            \RS\HashStore\Api::set($key, $settings);
            $this->settings = $settings;
        } else {
            $this->importErrors($this->yandex_request->exportErrors());
            Log::write("[error]: Can't load settings for mapping holidays");
        }

        return $settings;
    }

    /**
     * Возвращает ключ, с которым будут сохранены настройки кампании в HashStore
     *
     * @return string
     */
    protected function getHashStoreKey()
    {
        return self::HASHSTORE_PREFIX.$this->config['campaign_id']
                                     .$this->config->getYandexAppId()
                                     .$this->config['ytoken'];
    }

    /**
     * Возвращает настройки кампании в Яндекс.Маркете
     *
     * @return array|false
     */
    public function getSettings()
    {
        if (!isset($this->settings)) {
            $key = $this->getHashStoreKey();
            $this->settings = \RS\HashStore\Api::get($key, array());
            if (!$this->settings) {
                $this->settings = $this->refreshSettings();
            }
        }

        return $this->settings;
    }

    /**
     * Возвращает true, если период доставки выпадает за период расчетов, который предоставил в последний раз Яндекс.
     * Яндекс.Маркет возвращает праздники на ближайшие 2,5 месяца со дня запроса настроек кампании
     *
     * @return bool
     */
    protected function issetDateInSettings(DeliveryPeriod $period)
    {
        $max_days = $period->getDayMin() > $period->getDayMax() ? $period->getDayMin() : $period->getDayMax();
        $max_date = strtotime("+".$max_days." days");

        $settings = $this->getSettings();
        $max_settings_period = strtotime($settings['settings']['localRegion']['delivery']['schedule']['period']['toDate']);

        return $max_date < $max_settings_period;
    }

    /**
     * Возвращает true, если адрес доставки находится в локальном регионе, который задан в настройках кампании Яндекс.Маркета
     *
     * @param $address
     */
    public function isAddressInLocalDelivery(Address $address = null)
    {
        if ($address
            && ($settings = $this->getSettings())
            && (isset($settings['settings']['localRegion']['type']))) {

            $region_type = $settings['settings']['localRegion']['type'];
            switch($region_type) {
                case 'SUBURB':
                case 'TOWN':
                case 'CITY':
                    $verdict = strtolower($settings['settings']['localRegion']['name']) == strtolower($address['city']);
                    break;

                case 'AREA':
                case 'REGION':
                case 'REPUBLIC':
                    $verdict = strtolower($settings['settings']['localRegion']['name']) == strtolower($address['region']);
                    break;

                case 'COUNTRY':
                    $verdict = strtolower($settings['settings']['localRegion']['name']) == strtolower($address['country']);
                    break;

                default:
                    $verdict = false;
                    Log::write("[error]: Unsupported localRegion type '".$region_type."' for mapping holidays");
            }

            return $verdict;
        }

        return false;
    }

    /**
     * Корректирует интервал даты доставки с учетом выходных дней, указанных в настройках кампании Яндекс.Маркета для
     * доставки в локальном регионе
     *
     * @param \Shop\Model\DeliveryType\Helper\DeliveryPeriod $period
     * @param $address
     *
     * @return \Shop\Model\DeliveryType\Helper\DeliveryPeriod
     */
    public function mapPeriod(DeliveryPeriod $period, $address)
    {
        if ($period->hasPeriod() && $this->isAddressInLocalDelivery($address)) {
            if (!$this->issetDateInSettings($period)) {
                //Обновляем сведения о выходных, если они устарели
                $this->refreshSettings();
            }

            //Проверяем не попадает ли дата доставки на выходные дни
            if ($day_min = $period->getDayMin()) {
                $period->setDayMin($this->mapDays($day_min));
            }

            if ($day_max = $period->getDayMax()) {
                $period->setDayMax($this->mapDays($day_max));
            }
        }

        return $period;
    }

    /**
     * Проверяет, не попадает ли конкретная дата на выходной. Если попадает, то корректирует дату.
     * Иначе возвращает исходное кол-во дней
     *
     * @param integer $days количество дней, относительно текущей даты
     * @return integer Возвращает правильное количество дней.
     */
    protected function mapDays($days)
    {
        if ($settings = $this->getSettings()) {
            $date = date('d-m-Y', strtotime('+'.$days.' days'));
            $total_holidays = $settings['settings']['localRegion']['delivery']['schedule']['totalHolidays'];

            foreach ($total_holidays as $holiday_date) {
                if ($holiday_date == $date) {
                    //Рекурсивно увеличиваем на 1 кол-во дней, пока день не будет выпадать на выходной
                    return $this->mapDays($days + 1);
                }
            }
        }

        return $days;
    }
}