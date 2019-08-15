<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Statistic\Config;

use RS\Db\Exception as DbException;
use RS\Event\HandlerAbstract;
use RS\Http\Request;
use Shop\Model\Orm\Order;
use Statistic\Model\StatEventApi;
use \RS\Orm\Type;
use Statistic\Model\UserSourceApi;

class Handlers extends HandlerAbstract
{
    function init()
    {
        $this
            ->bind('initialize')
            ->bind('statistic')
            ->bind('getmenus')
            ->bind('orm.init.shop-order')
            ->bind('orm.init.shop-reservation')
            ->bind('orm.init.catalog-oneclickitem')
            ->bind('orm.init.users-user')
            ->bind('orm.beforewrite.catalog-oneclickitem')
            ->bind('orm.beforewrite.shop-order')
            ->bind('orm.beforewrite.shop-reservation')
            ->bind('orm.afterwrite.catalog-oneclickitem')
            ->bind('orm.afterwrite.shop-reservation')
            ->bind('orm.afterwrite.shop-order')
            ->bind('applyroute');
    }

    /**
     * Инициализация методов для ORM объектов
     */
    public static function initialize()
    {
        \Users\Model\Orm\User::attachClassBehavior(new \Statistic\Model\Behavior\UsersUser());
        \Shop\Model\Orm\Reservation::attachClassBehavior(new \Statistic\Model\Behavior\ShopReservation());
        \Shop\Model\Orm\Order::attachClassBehavior(new \Statistic\Model\Behavior\ShopOrder());
        \Catalog\Model\Orm\OneClickItem::attachClassBehavior(new \Statistic\Model\Behavior\CatalogOneClickItem());
    }

    /**
     * Действия на применении маршрута
     *
     * @param array $routes - массив маршрутов
     * @throws \RS\Db\Exception
     */
    public static function applyRoute($routes)
    {
        //Если мы не в админке и у нас пустая кука или не заполнен истчни к пользователя, то ставим этот сточник
        if (!\RS\Router\Manager::obj()->isAdminZone()){
            $user_source_api = new \Statistic\Model\UserSourceApi();

            $request = Request::commonInstance();
            $http_referer = $request->server('HTTP_REFERER', TYPE_STRING, null, null);
            $http_host = $request->server('HTTP_HOST', TYPE_STRING, null, null);

            if ($user_source_api->checkUnsetUserCookie() &&
                !empty($http_referer) &&
                (parse_url($http_referer, PHP_URL_HOST) !== $http_host)
            ){ //Если нет куки то поставим её
                $user_source_api->setCookieSourceInfo();
            }

            //Посмотрим, есть ли у пользователя источник
            if (\RS\Application\Auth::isAuthorize() && $user_source_api->checkUnsetUserSourceId(\RS\Application\Auth::getCurrentUser())){ //Если авторизован
                $user_source_api->setSourceInfoToUser(\RS\Application\Auth::getCurrentUser());
            }

            //Если не авторизованы или авторизованы и не админ, но при этом есть UTM метка
            if ((!\RS\Application\Auth::isAuthorize() || (\RS\Application\Auth::isAuthorize() && !\RS\Application\Auth::getCurrentUser()->isAdmin())) && isset($_REQUEST['utm_source'])){
                $user_source_api->setLastUTMToCookie();
            }
        }
    }


    /**
     * Действия после записи заказа. Установим источник
     *
     * @param array $data - массив данных
     * @throws \RS\Db\Exception
     */
    public static function ormAfterWriteShopOrder($data)
    {
        /**
         * @var \Shop\Model\Orm\Order $order
         */
        $order = $data['orm'];
        $flag  = $data['flag'];

        $source_api = new \Statistic\Model\UserSourceApi();
        $source_api->setSourceToOrder($order);

        if ($flag == $order::INSERT_FLAG){
            //Припишем последнюю UTM метку к заказу

            if (UserSourceApi::getLastSourceUtmCookie()){
                $source_api->setUTMToOrderFromCookie($order);
            }
        }
    }

    /**
     * Действия перед записью купить в один клик. Установим источник
     *
     * @param array $data - массив данных
     */
    public static function ormBeforeWriteCatalogOneClickItem($data)
    {
        /**
         * @var \Catalog\Model\Orm\OneClickItem $oneclick
         */
        $oneclick = $data['orm'];
        $flag     = $data['flag'];

        if ($flag == $oneclick::INSERT_FLAG){

            //Припишем последнюю UTM метку к заказу
            if (UserSourceApi::getLastSourceUtmCookie()){
                $data = UserSourceApi::getLastSourceUtmCookieArray();
                $oneclick->getFromArray($data);
            }
        }
    }

    /**
     * Действия после записи купить в один клик. Установим источник
     *
     * @param array $data - массив данных
     * @throws \RS\Db\Exception
     */
    public static function ormAfterWriteCatalogOneClickItem($data)
    {
        /**
         * @var \Catalog\Model\Orm\OneClickItem $oneclick
         */
        $oneclick = $data['orm'];
        $flag     = $data['flag'];

        $source_api = new \Statistic\Model\UserSourceApi();
        $source_api->setSourceToOrder($oneclick);

        if ($flag == $oneclick::INSERT_FLAG){
            //Удалим последнюю UTM метку к заказу
            if (UserSourceApi::getLastSourceUtmCookie()){
                \Statistic\Model\UserSourceApi::deleteLastUTMSourceCookie();
            }
        }
    }

    /**
     * Действия перед записью заказать. Установим источник
     *
     * @param array $data - массив данных
     */
    public static function ormBeforeWriteShopReservation($data)
    {
        /**
         * @var \Shop\Model\Orm\Reservation $reservation
         */
        $reservation = $data['orm'];
        $flag        = $data['flag'];

        if ($flag == $reservation::INSERT_FLAG){
            //Припишем последнюю UTM метку к заказу
            if (UserSourceApi::getLastSourceUtmCookie()){
                $data = UserSourceApi::getLastSourceUtmCookieArray();
                $reservation->getFromArray($data);
            }
        }
    }

    /**
     * Действия перед записью заказа. Установим источник
     *
     * @param array $params - массив данных
     * @return void
     * @throws DbException
     */
    public static function ormBeforeWriteShopOrder($params)
    {
        /** @var Order $order */
        $order = $params['orm'];
        $source_api = new UserSourceApi();
        $source_api->setSourceToOrder($order);
    }

    /**
     * Действия после записи заказать. Установим источник
     *
     * @param array $data - массив данных
     * @throws \RS\Db\Exception
     */
    public static function ormAfterWriteShopReservation($data)
    {
        /**
         * @var \Shop\Model\Orm\Reservation $reservation
         */
        $reservation = $data['orm'];
        $flag        = $data['flag'];

        $source_api = new \Statistic\Model\UserSourceApi();
        $source_api->setSourceToOrder($reservation);

        if ($flag == $reservation::INSERT_FLAG){
            //Удалим последнюю UTM метку к заказу
            if (UserSourceApi::getLastSourceUtmCookie()){
                \Statistic\Model\UserSourceApi::deleteLastUTMSourceCookie();
            }
        }
    }

    /**
     * Расширение объект пользователя
     *
     * @param \Users\Model\Orm\User $user - объект пользователя
     */
    public static function ormInitUsersUser(\Users\Model\Orm\User $user)
    {
        $user->getPropertyIterator()->append(array(
            t('Статистика'),
                'source_id' => new Type\Integer(array(
                    'description' => t('Источник перехода'),
                    'template' => '%statistic%/form/user/source_id.tpl',
                    'listenPost' => false,
                    'default' => "0"
                )),
                'date_arrive' => new Type\Datetime(array(
                    'description' => t('Дата первого посещения'),
                ))
        ));
    }

    /**
     * Расширение объекта заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     */
    public static function ormInitShopOrder(\Shop\Model\Orm\Order $order)
    {
        $order->addIndex(array('dateof', 'profit'), $order::INDEX_KEY);
        $order->addIndex(array('dateof', 'totalcost'), $order::INDEX_KEY);

        $order->getPropertyIterator()->append(array(
            'source_id' => new Type\Integer(array(
                'description' => t('Источник перехода пользователя'),
                'infoVisible' => true,
                'listenPost' => false,
                'default' => 0,
                'template' => '%statistic%/form/source/source_id.tpl'
            )),
            //Параметры UTM меток
            'utm_source' => new Type\Varchar(array(
                'description' => t('Рекламная система UTM_SOURCE'),
                'maxLength' => 50,
                'readonly' => true
            )),
            'utm_medium' => new Type\Varchar(array(
                'description' => t('Тип трафика UTM_MEDIUM'),
                'maxLength' => 50,
                'readonly' => true
            )),
            'utm_campaign' => new Type\Varchar(array(
                'description' => t('Рекламная кампания UTM_COMPAING'),
                'maxLength' => 50,
                'readonly' => true
            )),
            'utm_term' => new Type\Varchar(array(
                'description' => t('Ключевое слово UTM_TERM'),
                'maxLength' => 50,
                'readonly' => true
            )),
            'utm_content' => new Type\Varchar(array(
                'description' => t('Различия UTM_CONTENT'),
                'maxLength' => 50,
                'readonly' => true
            )),
            'utm_dateof' => new Type\Date(array(
                'description' => t('Дата события'),
                'readonly' => true
            )),
        ));
    }

    /**
     * Расширение объекта купить в один клик
     *
     * @param \Catalog\Model\Orm\OneClickItem $oneclick - объект купить в один клик
     */
    public static function ormInitCatalogOneClickItem(\Catalog\Model\Orm\OneClickItem $oneclick)
    {
        $oneclick->getPropertyIterator()->append(array(
            t('Статистика'),
                'source_id' => new Type\Integer(array(
                    'description' => t('Источник перехода пользователя'),
                    'default' => 0,
                    'listenPost' => false,
                    'template' => '%statistic%/form/source/source_id.tpl'
                )),
                //Параметры UTM меток
                'utm_source' => new Type\Varchar(array(
                    'description' => t('Рекламная система UTM_SOURCE'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_medium' => new Type\Varchar(array(
                    'description' => t('Тип трафика UTM_MEDIUM'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_campaign' => new Type\Varchar(array(
                    'description' => t('Рекламная кампания UTM_COMPAING'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_term' => new Type\Varchar(array(
                    'description' => t('Ключевое слово UTM_TERM'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_content' => new Type\Varchar(array(
                    'description' => t('Различия UTM_CONTENT'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_dateof' => new Type\Date(array(
                    'description' => t('Дата события'),
                    'readonly' => true
                )),
        ));
    }

    /**
     * Расширение объекта заказать
     *
     * @param \Shop\Model\Orm\Reservation $reserve - объект заказать
     */
    public static function ormInitShopReservation(\Shop\Model\Orm\Reservation $reserve)
    {
        $reserve->getPropertyIterator()->append(array(
            t('Статистика'),
                'source_id' => new Type\Integer(array(
                    'description' => t('Источники прихода'),
                    'default' => 0,
                    'listenPost' => false,
                    'template' => '%statistic%/form/source/source_id.tpl'
                )),
                //Параметры UTM меток
                'utm_source' => new Type\Varchar(array(
                    'description' => t('Рекламная система UTM_SOURCE'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_medium' => new Type\Varchar(array(
                    'description' => t('Тип трафика UTM_MEDIUM'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_campaign' => new Type\Varchar(array(
                    'description' => t('Рекламная кампания UTM_COMPAING'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_term' => new Type\Varchar(array(
                    'description' => t('Ключевое слово UTM_TERM'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_content' => new Type\Varchar(array(
                    'description' => t('Различия UTM_CONTENT'),
                    'maxLength' => 50,
                    'readonly' => true
                )),
                'utm_dateof' => new Type\Date(array(
                    'description' => t('Дата события'),
                )),
        ));
    }
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    */
    public static function getMenus($items)
    {
        $items[] = array(
                'title' => t('Статистика'),
                'alias' => 'statistic',
                'link' => '%ADMINPATH%/statistic-dashboard/',
                'typelink' => 'link',                      
                'parent' => 'modules',
                'sortn' => 0
            );
        $items[] = array(
                'title' => t('Отчеты'),
                'alias' => 'statisticgraph',
                'link' => '%ADMINPATH%/statistic-dashboard/',
                'typelink' => 'link',
                'parent' => 'statistic',
                'sortn' => 1
            );
        $items[] = array(
                'title' => t('Типы источников'),
                'alias' => 'statisticsourcetypes',
                'link' => '%ADMINPATH%/statistic-sourcetypesctrl/',
                'typelink' => 'link',
                'parent' => 'statistic',
                'sortn' => 2
            );
        return $items;
    }

    /**
     * Записывает статистические события в базе
     *
     * @param array $params - массив параметров
     * @throws \Exception
     * @throws \RS\Orm\Exception
     */
    public static function statistic($params)
    {
        $event_type = $params['type'];
        StatEventApi::getInstance()->registerEvent($event_type);
    }
}