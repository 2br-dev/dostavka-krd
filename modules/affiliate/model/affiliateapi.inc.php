<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Model;

use Affiliate\Model\Orm\Affiliate;
use Main\Model\GeoIpApi;
use RS\Application\Application;
use RS\Config\Loader as ConfigLoader;
use RS\Db\Exception as DbException;
use RS\Html\Table\Type as TableType;
use RS\Http\Request as HttpRequest;
use RS\Module\AbstractModel\TreeCookieList;
use RS\Orm\Request as OrmRequest;

/**
 * Класс для организации выборок ORM объекта.
 * В этом классе рекомендуется также реализовывать любые дополнительные методы, связанные с заявленной в конструкторе моделью
 */
class AffiliateApi extends TreeCookieList
{
    const SESSION_GEOIP_AFFILIATE_ID = 'affiliate_geoip';
    const COOKIE_AFFILIATE_ID = 'affiliate';
    const COOKIE_ALREADY_SELECT = 'affiliate_already_select';

    protected static $current_affiliate;

    protected $need_recheck = false;
    protected $sub_tools;

    public function __construct()
    {
        parent::__construct(new Affiliate, array(
            'parentField' => 'parent_id',
            'nameField' => 'title',
            'aliasField' => 'alias',
            'sortField' => 'sortn',
            'defaultOrder' => 'sortn',
            'multisite' => true,
        ));
    }

    /**
     * сортирует филиалы по алфавиту
     *
     * @return void
     * @throws DbException
     */
    public function sortAffiliatesAlphabetically()
    {
        $list = $this->clearFilter()
            ->setOrder('title asc')
            ->getListAsArray();

        if ($list) {
            foreach ($list as $key => $affiliate) {
                OrmRequest::make()
                    ->update(new Affiliate())
                    ->set(array('sortn' => $key))
                    ->where(array('id' => $affiliate['id']))
                    ->exec();
            }
        }
    }

    /**
     * Устанавливает состав инструментов для отображения в админ. панели
     * для не корневых филиалов
     *
     * @param \RS\Html\Table\Type\Actions $tools
     * @return void
     */
    public function setSubTools(TableType\Actions $tools)
    {
        $this->sub_tools = $tools;
    }

    /**
     * Аналог getSelectList, только для статичского вызова
     *
     * @param int $parent_id = id корневого элемента дерева
     * @param array $first = значения, которые нужно добавить в начало списка
     * @return array
     */
    public static function staticSelectList($parent_id = 0, $first = array())
    {
        if (gettype($parent_id) == 'string') { // для совместимости
            $first = array(0 => $parent_id);
            $parent_id = 0;
        }
        return parent::staticSelectList($parent_id, (array)$first);
    }

    /**
     * Возвращает список элементов первого уровня + корневой элемент
     *
     * @return array
     */
    public static function staticRootList()
    {
        $_this = new self();
        $_this->setFilter('parent_id', 0);
        $root = $_this->getListAsResource()->fetchSelected('id', 'title');

        return array(0 => t('Верхний уровень')) + $root;
    }

    /**
     * Возвращает объект текущего филиала
     *
     * @return \Affiliate\Model\Orm\Affiliate
     */
    public static function getCurrentAffiliate()
    {
        if (!isset(self::$current_affiliate)) {
            $config = ConfigLoader::byModule('affiliate');
            $request = HttpRequest::commonInstance();
            $cookie_affiliate_id = $request->cookie(self::COOKIE_AFFILIATE_ID, TYPE_INTEGER);

            if ($cookie_affiliate_id) { //Читаем из cookie
                self::$current_affiliate = new Orm\Affiliate($cookie_affiliate_id);
            }

            if (empty(self::$current_affiliate['id'])) {
                if ($config->use_geo_ip) { //Определяем по GEO IP
                    $affiliate = self::getAffiliateByIp($request->server('REMOTE_ADDR')); //'31.181.15.239'
                }

                if (empty($affiliate)) { //Используем филиал по-умолчанию
                    $api = new self();
                    $affiliate = $api->getCleanQueryObject()
                        ->where(array(
                            'is_default' => 1
                        ))->object() ?: new Orm\Affiliate();
                }
                self::$current_affiliate = $affiliate;
            }
        }
        return self::$current_affiliate;
    }

    /**
     * Устанавливает текущий филиал
     *
     * @param Orm\Affiliate $affiliate - филиал
     * @param boolean $write_to_cookie - записывать в куки? true - да
     * @return void
     */
    public static function setCurrentAffiliate(Orm\Affiliate $affiliate, $write_to_cookie = false)
    {
        self::$current_affiliate = $affiliate;

        if ($write_to_cookie) {
            $app = Application::getInstance();
            $app->headers->addCookie(self::COOKIE_AFFILIATE_ID, $affiliate['id'], time() + 60 * 60 * 24 * 365 * 5, '/');
        }
    }

    /**
     * Возвращает true, если необходимо подтвердить у пользователя его город
     *
     * @return bool
     */
    public static function needRecheck()
    {
        $config = ConfigLoader::byModule('affiliate');
        $http = HttpRequest::commonInstance();

        //Филиал выбирался непосредственно пользователем ранее
        $cookie_affiliate_id = $http->cookie(self::COOKIE_AFFILIATE_ID, TYPE_INTEGER);
        //Диалог подтверждения филиала был показан ранее
        $already_city_select = $http->cookie(self::COOKIE_ALREADY_SELECT, TYPE_INTEGER);

        return $config->confirm_city_select && !$cookie_affiliate_id && !$already_city_select;
    }

    /**
     * Возвращает город по IP адресу
     *
     * @param string $ip - IP адрес
     * @return \Affiliate\Model\Orm\Affiliate | false
     */
    public static function getAffiliateByIp($ip)
    {
        if (!isset($_SESSION[self::SESSION_GEOIP_AFFILIATE_ID])) {
            //Определяем город по названию
            $geoIp = new GeoIpApi();
            $city = $geoIp->getCityByIp($ip);

            if ($city !== false) {
                $affiliate_id = OrmRequest::make()
                    ->select('id')
                    ->from(new Orm\Affiliate)
                    ->where(array(
                        'skip_geolocation' => 0,
                        'clickable' => 1,
                        'public' => 1,
                        'title' => $city
                    ))->exec()->getOneField('id');
            } else {
                $affiliate_id = null;
            }

            if ($city === false || !$affiliate_id) {
                //Определяем ближайший город по координатам
                if ($coord = $geoIp->getCoordByIp($ip)) {
                    $affiliate_id = self::getNearAffiliate($coord['lat'], $coord['lng']);
                }
            }
            $_SESSION[self::SESSION_GEOIP_AFFILIATE_ID] = $affiliate_id;
        }

        if ($_SESSION[self::SESSION_GEOIP_AFFILIATE_ID]) {
            return Orm\Affiliate::loadSingle($_SESSION[self::SESSION_GEOIP_AFFILIATE_ID]);
        }
        return false;
    }

    /**
     * Возвращает ближайший для указанных коорднат Филиал
     *
     * @param float $lat - широта
     * @param float $lng - долгота
     * @return \Affiliate\Model\Orm\Affiliate | false
     */
    public static function getNearAffiliate($lat, $lng)
    {
        $config = ConfigLoader::byModule(__CLASS__);
        $delta = $config['coord_max_distance;'];
        $lat = (float)$lat;
        $lng = (float)$lng;

        //Загружаем все филиалы в допустимом квадрате
        $affiliates = OrmRequest::make()
            ->select('coord_lng, coord_lat, id')
            ->from(new Orm\Affiliate())
            ->where("'#lng_from' <= coord_lng AND coord_lng <= '#lng_to'", array(
                'lng_from' => $lng - $delta,
                'lng_to' => $lng + $delta
            ))
            ->where("'#lat_from' <= coord_lat AND coord_lat <= '#lat_to'", array(
                'lat_from' => $lat - $delta,
                'lat_to' => $lat + $delta
            ))->exec()->fetchAll();

        if (!$affiliates) return false;

        //Находим ближайший по расстоянию
        $min_distance = null;
        $affiliate_id = false;
        foreach ($affiliates as $row) {
            $a = pow(abs($row['coord_lat'] - $lat), 2);
            $b = pow(abs($row['coord_lng'] - $lng), 2);
            $distance = sqrt($a + $b);
            if ($min_distance === null || $min_distance > $distance) {
                $min_distance = $distance;
                $affiliate_id = $row['id'];
            }
        }
        return $affiliate_id;
    }
}
