<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Model;

use Main\Model\GeoIpApi;
use Partnership\Config\File as PartnershipConfig;
use Partnership\Model\Orm\Partner;
use RS\Application\Auth;
use RS\Config\Loader as ConfigLoader;
use RS\Helper\IdnaConvert;
use RS\Http\Request as HttpRequest;
use RS\Module\AbstractModel\EntityList;
use RS\Site\Manager as SiteManager;

class Api extends EntityList
{
    /** @var Orm\Partner */
    public static $current_partner;

    function __construct()
    {
        parent::__construct(new Orm\Partner,
            array(
                'id_field' => 'id',
                'name_field' => 'title',
                'multiedit' => true
            ));
    }

    /**
     * Возвращает ссылку для переадресации на переданого партнёра
     *
     * @param Partner|null $partner - объект партнёра, если передан null, вернёт ссылку на основной сайт
     * @return string
     */
    public static function getPartnerRedirectUrl($partner = null)
    {
        $dont_redirect_param = array(
            PartnershipConfig::PARAM_DONT_REDIRECT_TO_GEO_PARTNER => 1,
            PartnershipConfig::PARAM_DONT_SHOW_CONFIRMATION_GEO_PARTNER => 1,
        );
        $request = HttpRequest::commonInstance();
        $relative_part = $request->replaceKey($dont_redirect_param);

        if ($partner === null) {
            $host = SiteManager::getSite()->getRootUrl(true);
        } else {
            $host = $partner->getRootUrl(true);
        }
        return substr($host, 0, -1) . $relative_part;
    }

    /**
     * Возвращает ближайшего партнёра, используя геолокацию
     * если ближайшим окажется основной сайт - вернёт null
     *
     * @return null|Orm\Partner
     */
    public function getClosestGeolocationPartner()
    {
        $config = ConfigLoader::byModule($this);
        $geo_ip_api = new GeoIpApi();
        $geo_ip_service = $geo_ip_api->getService();
        $coordinates = $geo_ip_service->getCoordByIp($_SERVER['REMOTE_ADDR']);
        $lat = $coordinates['lat'];
        $lng = $coordinates['lng'];
        /** @var Orm\Partner[] $partner_list */
        $partner_list = $this->getList();

        $closest_partner = null;
        $less_distance = pow($config['coordinates']['lat'] - $lat, 2) + pow($config['coordinates']['lng'] - $lng, 2);
        foreach ($partner_list as $partner) {
            $distance = pow($partner['coordinate_lat'] - $lat, 2) + pow($partner['coordinate_lng'] - $lng, 2);
            if ($distance < $less_distance) {
                $less_distance = $distance;
                $closest_partner = $partner;
            }
        }
        return $closest_partner;
    }

    /**
     * Устанавливает id партнера, чей сайт открыт в данный момент
     *
     * @param Orm\Partner $partner - партнёрский сайт
     * @return Orm\Partner
     */
    public static function setCurrentPartner(Orm\Partner $partner = null)
    {
        if ($partner === null) {
            $idnaconvert = new IdnaConvert();
            $_this = new self();
            $partners = $_this->getList();
            foreach ($partners as $each_partner) {
                $domains = preg_split('/[\n,]/', $each_partner['domains'], -1, PREG_SPLIT_NO_EMPTY);
                $current_domain = strtolower($_SERVER['HTTP_HOST']);
                foreach ($domains as $domain) {
                    if (strtolower($domain) == $current_domain ||
                        strtolower($domain) == $idnaconvert->decode($current_domain)
                    ) {
                        $partner = $each_partner;
                        break;
                    }
                }
            }
        }

        return self::$current_partner = $partner;
    }

    /**
     * Возвращает текущего партнера или null
     *
     * @return Orm\Partner|null
     */
    public static function getCurrentPartner()
    {
        return self::$current_partner ? self::$current_partner : null;
    }

    /**
     * Возвращает true, если текущий пользователь является администратором текущего партнерского сайта
     *
     * @return bool
     */
    public static function isUserPartner()
    {
        if ($partner = self::getCurrentPartner()) {
            $current_user = Auth::getCurrentUser();
            return $partner['user_id'] === $current_user['id'];
        }
        return false;
    }

    /**
     * Возвращает разобранные данные партнёра для полей From или Reply уведомлений
     *
     * @param string $property - свойство, которое нужно разобрать
     * @param $return_email - Если true, то вернет email, если false - то вернет надпись, если null - то вернет массив
     * @return mixed
     */
    public function getPartnerNoticeParsed($property, $return_email = null)
    {
        $result = array('line' => $property);
        if (preg_match('/^(.*?)<(.*)>$/', html_entity_decode($result['line']), $match)) {
            $result['string'] = $match[1];
            $result['email'] = $match[2];
        } else {
            $result['string'] = '';
            $result['email'] = $result['line'];
        }

        if ($return_email === null) return $result;
        return $return_email ? $result['email'] : $result['string'];
    }

    /**
     * Аналог getSelectList, только для статичского вызова
     *
     * @param array $first - значения, которые нужно добавить в начало списка
     * @return array
     */
    static function staticSelectList($first = array())
    {
        if ($first === true) { // для совместимости
            $first = array(0 => t('- Не выбрано -'));
        }
        return parent::staticSelectList($first);
    }
}
