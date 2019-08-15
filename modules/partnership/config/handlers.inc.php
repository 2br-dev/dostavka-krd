<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Config;

use Alerts\Model\Manager as AlertsManager;
use Catalog\Model\CostApi;
use Catalog\Model\Orm\OneClickItem;
use Menu\Model\Orm\Menu;
use Partnership\Model\Api;
use Partnership\Model\Notice\CheckoutPartner as NoticeCheckoutPartner;
use Partnership\Model\Orm\Partner;
use RS\Application\Application;
use RS\Application\Auth;
use RS\Config\Loader as ConfigLoader;
use RS\Event\HandlerAbstract;
use RS\Http\Request as HttpRequest;
use RS\Orm\AbstractObject;
use RS\Orm\Type as OrmType;
use RS\Html\Filter;
use RS\Html\Table\Type as TableType;
use RS\Router\Manager as RouterManager;
use RS\Router\Route;
use RS\Theme\Manager as ThemeManager;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\Payment;
use Shop\Model\Orm\Reservation;
use Shop\Model\Orm\Transaction;
use Site\Controller\Front\SiteClosed as ControllerSiteClosed;

class Handlers extends HandlerAbstract
{
    function init()
    {
        $this
            ->bind('applyroute')
            ->bind('getmenus')
            ->bind('getroute')
            ->bind('checkout.delivery.list')
            ->bind('checkout.payment.list')
            ->bind('controller.afterinit.catalog-front-listproducts')
            ->bind('controller.afterinit.menu-block-menu')
            ->bind('orm.init.shop-order')
            ->bind('orm.init.catalog-oneclickitem')
            ->bind('orm.init.menu-menu')
            ->bind('orm.init.shop-delivery')
            ->bind('orm.init.shop-payment')
            ->bind('orm.init.shop-reservation')
            ->bind('orm.init.shop-transaction')
            ->bind('orm.beforewrite.catalog-oneclickitem')
            ->bind('orm.beforewrite.shop-delivery')
            ->bind('orm.beforewrite.shop-order')
            ->bind('orm.beforewrite.shop-payment')
            ->bind('orm.beforewrite.shop-reservation')
            ->bind('orm.beforewrite.shop-transaction')
            ->bind('orm.afterload.shop-delivery')
            ->bind('orm.afterload.shop-payment')
            ->bind('orm.afterwrite.shop-order')
            ->bind('init.dirapi.catalog-block-category')
            ->bind('init.searchlineapi.catalog-block-searchline')
            ->bind('theme.getcontextlist')
            ->bind('mailer.alerts.beforesend')
            ->bind('controller.exec.catalog-admin-oneclickctrl.index')
            ->bind('controller.exec.shop-admin-reservationctrl.index')
            ->bind('start', null, null, 11);
    }

    public static function applyRoute()
    {
        $config = ConfigLoader::byModule('partnership');
        $request = HttpRequest::commonInstance();
        $router = RouterManager::obj();
        $application = Application::getInstance();
        $param_dont_redirect = $request->request(File::PARAM_DONT_REDIRECT_TO_GEO_PARTNER, TYPE_INTEGER);
        $param_dont_confirm = $request->request(File::PARAM_DONT_SHOW_CONFIRMATION_GEO_PARTNER, TYPE_INTEGER);

        if ($config['redirect_to_geo_partner'] && !$request->cookie(File::COOKIE_REDIRECTED_TO_GEO_PARTNER, TYPE_INTEGER) && !$router->isAdminZone() && !$router->isTechRoute() && !$router::isStorageUrl() && !$router::isRobotUserAgent()) {
            setcookie(File::COOKIE_REDIRECTED_TO_GEO_PARTNER, 1, time()+(3600*24*3650));

            if (!$param_dont_redirect) {
                $api = new Api();
                $current_partner = Api::getCurrentPartner();
                $current_partner_id = $current_partner ? $current_partner['id'] : 0;
                $closest_partner = $api->getClosestGeolocationPartner();
                $closest_partner_id = $closest_partner ? $closest_partner['id'] : 0;

                if ($current_partner_id != $closest_partner_id) {
                    $application->redirect(Api::getPartnerRedirectUrl($closest_partner));
                }
            }
        }

        if ($param_dont_redirect || $param_dont_confirm) {
            if ($param_dont_confirm) {
                setcookie(File::COOKIE_GEO_PARTNER_CONFIRMATION_SHOWN, 1, time()+(3600*24*3650));
            }
            $application->redirect($request->replaceKey(array(
                File::PARAM_DONT_REDIRECT_TO_GEO_PARTNER => null,
                File::PARAM_DONT_SHOW_CONFIRMATION_GEO_PARTNER => null,
            )));
        }
    }

    public static function initSearchlineapiCatalogBlockSearchline($controller)
    {
        $partner = Api::getCurrentPartner();
        if ($partner) {
            $dirs = $partner->getAllowFolderList();
            if (!empty($dirs)) {
                $controller->search_line_api->api->setFilter('dir', $dirs, 'in');
                $controller->search_line_api->dirapi->setFilter('id', $dirs, 'in');
            }
        }
    }

    /**
     * Возвращает пункты меню этого модуля в виде массива
     *
     * @param array[] $items - список пунктов меню
     * @return array[]
     */
    public static function getMenus($items)
    {
        $items[] = array(
            'title' => t('Партнерские сайты'),
            'alias' => 'partnership',
            'link' => '%ADMINPATH%/partnership-ctrl/',
            'parent' => 'modules',
            'sortn' => 3,
            'typelink' => 'link',
        );
        return $items;
    }

    public static function getRoute($routes)
    {
        $routes[] = new Route('partnership-front-profile', array(
            '/profile-partner/',
        ), null, t('Профиль партнера'));

        return $routes;
    }

    /**
     * Убирает доставки не относящиеся к текущему партнёру
     *
     * @param array $params - массив с параметрами
     * @return array
     */
    public static function checkoutDeliveryList($params)
    {
        /** @var Delivery[] $delivery_list */
        $delivery_list = $params['list'];
        foreach ($delivery_list as $key => $delivery) {
            if (!empty($delivery['show_on_partners'])) {
                $partner_id = ($current_partner = Api::getCurrentPartner()) ? $current_partner['id'] : 0;
                if (!in_array($partner_id, $delivery['show_on_partners'])) {
                    unset($delivery_list[$key]);
                }
            }
        }
        $params['list'] = $delivery_list;
        return $params;
    }

    /**
     * Убирает оплаты не относящиеся к текущему партнёру
     *
     * @param array $params - массив с параметрами
     * @return array
     */
    public static function checkoutPaymentList($params)
    {
        /** @var Payment[] $payment_list */
        $payment_list = $params['list'];
        foreach ($payment_list as $key => $payment) {
            if (!empty($payment['show_on_partners'])) {
                $partner_id = ($current_partner = Api::getCurrentPartner()) ? $current_partner['id'] : 0;
                if (!in_array($partner_id, $payment['show_on_partners'])) {
                    unset($payment_list[$key]);
                }
            }
        }
        $params['list'] = $payment_list;
        return $params;
    }

    /**
     * Определяет ID партнера для текущей сессии
     */
    public static function start()
    {
        $config = ConfigLoader::byModule('partnership');
        if ($config['installed']) {
            $partner = Api::setCurrentPartner();
            if ($partner) {
                ThemeManager::setCurrentTheme($partner->getTheme());
                Application::getInstance()->initThemePath();
                CostApi::setSessionDefaultCost($partner['cost_type_id']);
                if ($partner['logo']) {
                    $site = ConfigLoader::getSiteConfig();
                    $site['logo'] = $partner['logo'];
                    $site['slogan'] = $partner['slogan'];
                }

                if ($partner['is_closed']
                    && !Auth::getCurrentUser()->isAdmin()
                ) {
                    $closed_controller = new ControllerSiteClosed();
                    echo $closed_controller->renderClosePage($partner);
                    exit;
                }
            }
        }
    }

    /**
     * Добавляем колонку с партнёрами в купить в один клик
     *
     * @param \RS\Controller\Admin\Helper\CrudCollection $helper - объект помошника
     */
    public static function controllerExecCatalogAdminOneClickCtrlIndex($helper)
    {
        /**
         * @var \RS\Html\Table\Element $table
         * @var \RS\Html\Filter\Container $container
         */
        $table = $helper['table']->getTable();

        //Добавлем партнерский сайт для отображения
        $partners_list = Api::staticSelectList(); //Список потрёрских сайтов в системе
        $table->addColumn(new TableType\Userfunc('partner_id', t('Партнерский сайт'), function ($partner_id) use ($partners_list) {
            if ($partner_id > 0) {
                return isset($partners_list[$partner_id]) ? $partners_list[$partner_id] : "";
            }
            return "";
        }, array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)), -1);

        $container = $helper['filter']->getContainer();
        //Добавим партнерский сайт, если он присутствует
        $partners_list = array('' => t('-Не выбрано-')) + Api::staticSelectList(); //Список потрёрских сайтов в системе
        $container->addLine(new Filter\Line(array(
            'Items' => array(
                new Filter\Type\Select('partner_id', t('Партнерский сайт'), $partners_list)
            )
        )));

        $container->cleanItemsCache();
    }

    /**
     * Добавляем колонку с партнёрами в купить в один клик
     *
     * @param \RS\Controller\Admin\Helper\CrudCollection $helper - объект помошника
     */
    public static function controllerExecShopAdminReservationCtrlIndex($helper)
    {
        /**
         * @var \RS\Html\Table\Element $table
         * @var \RS\Html\Filter\Container $container
         */
        $table = $helper['table']->getTable();

        //Добавлем партнерский сайт для отображения
        $partners_list = Api::staticSelectList(); //Список потрёрских сайтов в системе
        $table->addColumn(new TableType\Userfunc('partner_id', t('Партнерский сайт'), function ($partner_id) use ($partners_list) {
            if ($partner_id > 0) {
                return isset($partners_list[$partner_id]) ? $partners_list[$partner_id] : "";
            }
            return "";
        }, array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_ASC)), -1);

        $container = $helper['filter']->getContainer();
        //Добавим партнерский сайт, если он присутствует
        $partners_list = array('' => t('-Не выбрано-')) + Api::staticSelectList(); //Список потрёрских сайтов в системе
        $container->addLine(new Filter\Line(array(
            'Items' => array(
                new Filter\Type\Select('partner_id', t('Партнерский сайт'), $partners_list)
            )
        )));

        $container->cleanItemsCache();
    }

    /**
     * Добавляем в ORM объект сведения о том на каком парнёрском сайте сохранен объект
     *
     * @param string $flag - insert или update
     * @param \RS\Orm\AbstractObject $object - объект ORM с которым работаем
     */
    private static function addPartnerColumnToOrm($flag, AbstractObject $object)
    {
        $partner = Api::getCurrentPartner();
        if ($partner) {
            if ($flag == AbstractObject::INSERT_FLAG) {
                $object['partner_id'] = $partner['id'];
            }
        }
    }

    /**
     * Рассериализует свойства
     *
     * @param array $params - массив с параметрами
     */
    public static function ormAfterLoadShopDelivery($params)
    {
        /** @var Delivery $orm */
        $orm = $params['orm'];
        $orm['show_on_partners'] = @unserialize($orm['_show_on_partners']);
    }

    /**
     * Рассериализует свойства
     *
     * @param array $params - массив с параметрами
     */
    public static function ormAfterLoadShopPayment($params)
    {
        /** @var Payment $orm */
        $orm = $params['orm'];
        $orm['show_on_partners'] = @unserialize($orm['_show_on_partners']);
    }

    /**
     * Сериализует свойства
     *
     * @param array $params - массив с параметрами перед записью
     */
    public static function ormBeforeWriteShopDelivery($params)
    {
        /** @var Delivery $orm */
        $orm = $params['orm'];
        if ($orm->isModified('show_on_partners')) {
            $orm['_show_on_partners'] = serialize($orm['show_on_partners']);
        }
    }

    /**
     * Добавляет в заказ сведения о партнерском сайте
     *
     * @param array $params - массив с параметрами перед записью
     */
    public static function ormBeforeWriteShopOrder($params)
    {
        self::addPartnerColumnToOrm($params['flag'], $params['orm']);
        $partner = Api::getCurrentPartner();
        if ($partner) {
            $order = $params['orm'];
            $order->addExtraInfoLine(t('Оформлен от партнера'), $partner['title'], array(
                'partner_id' => $partner['id']
            ));
        }
    }

    /**
     * Сериализует свойства
     *
     * @param array $params - массив с параметрами перед записью
     */
    public static function ormBeforeWriteShopPayment($params)
    {
        /** @var Payment $orm */
        $orm = $params['orm'];
        if ($orm->isModified('show_on_partners')) {
            $orm['_show_on_partners'] = serialize($orm['show_on_partners']);
        }
    }

    /**
     * Добавляет в предзаказ сведения о партнерском сайте
     *
     * @param array $params - массив с параметрами перед записью
     */
    public static function ormBeforeWriteShopReservation($params)
    {
        self::addPartnerColumnToOrm($params['flag'], $params['orm']);
    }

    /**
     * Добавляет в купить в один клик сведения о партнерском сайте
     *
     * @param array $params - массив с параметрами перед записью
     */
    public static function ormBeforeWriteCatalogOneClickItem($params)
    {
        self::addPartnerColumnToOrm($params['flag'], $params['orm']);
    }

    /**
     * Добавляет в транзакцию сведения о партнерском сайте
     *
     * @param array $params - массив с параметрами перед записью
     */
    public static function ormBeforeWriteShopTransaction($params)
    {
        self::addPartnerColumnToOrm($params['flag'], $params['orm']);
    }

    /**
     * Отправляем уведомление администратору партнерского сайта
     *
     * @param array $params - массив с параметрами
     */
    public static function ormAfterWriteShopOrder($params)
    {
        $order = $params['orm'];
        if ($order['partner_id'] && $params['flag'] == 'insert') {
            $notice = new NoticeCheckoutPartner();
            $notice->init($order);
            AlertsManager::send($notice);
        }
    }

    /**
     * Добавляем к заказу дополнительную колонку - партнер ID
     *
     * @param \Shop\Model\Orm\Order $orm_order - объект заказа
     */
    public static function ormInitShopOrder(Order $orm_order)
    {
        $orm_order->getPropertyIterator()->append(array(
            'partner_id' => new OrmType\Integer(array(
                'description' => t('ID партнера'),
                'meVisible' => false,
            ))
        ));
    }

    /**
     * Добавляем доставке список партнёрских сайтов, на которых её отображать
     *
     * @param Delivery $orm - объект доставки
     */
    public static function ormInitShopDelivery(Delivery $orm)
    {
        $orm->getPropertyIterator()->append(array(
            t('Основные'),
                'show_on_partners' => new OrmType\ArrayList(array(
                    'description' => t('На каких партнёрских сайтах показывать'),
                    'hint' => t('Если ничего не выбрано - доставка будет отображаться на всех партнёрских сайтах'),
                    'list' => array(array('\Partnership\Model\Api', 'staticSelectList'), array(0 => t('Основной сайт'))),
                    'attr' => array(array(
                        'multiple' => true,
                        'size' => 5,
                    )),
                )),
                '_show_on_partners' => new OrmType\Varchar(array(
                    'description' => t('Показывать на партнёрских сайтах (сериализованное)'),
                    'visible' => false,
                )),
        ));
    }

    /**
     * Добавляем оплате список партнёрских сайтов, на которых её отображать
     *
     * @param Payment $orm - объект оплаты
     */
    public static function ormInitShopPayment(Payment $orm)
    {
        $orm->getPropertyIterator()->append(array(
            t('Основные'),
                'show_on_partners' => new OrmType\ArrayList(array(
                    'description' => t('На каких партнёрских сайтах показывать'),
                    'hint' => t('Если ничего не выбрано - оплата будет отображаться на всех партнёрских сайтах'),
                    'list' => array(array('\Partnership\Model\Api', 'staticSelectList'), array(0 => t('Основной сайт'))),
                    'attr' => array(array(
                        'multiple' => true,
                        'size' => 5,
                    )),
                )),
                '_show_on_partners' => new OrmType\Varchar(array(
                    'description' => t('Показывать на партнёрских сайтах (сериализованное)'),
                    'visible' => false,
                )),
        ));
    }

    /**
     * Добавляем к предзаказу дополнительную колонку - партнер ID
     *
     * @param \Shop\Model\Orm\Reservation $orm_reserve - объект предзаказа
     */
    public static function ormInitShopReservation(Reservation $orm_reserve)
    {
        $orm_reserve->getPropertyIterator()->append(array(
            'partner_id' => new OrmType\Integer(array(
                'maxLength' => '11',
                'description' => t('Партнёрский сайт'),
                'default' => 0,
                'template' => '%partnership%/form/partner/partner_id.tpl'
            ))
        ));
    }

    /**
     * Добавляем к купить в один клик дополнительную колонку - партнер ID
     *
     * @param \Catalog\Model\Orm\OneClickItem $orm_one_click - объект покупки в один клик
     */
    public static function ormInitCatalogOneClickItem(OneClickItem $orm_one_click)
    {
        $orm_one_click->getPropertyIterator()->append(array(
            'partner_id' => new OrmType\Integer(array(
                'maxLength' => '11',
                'description' => t('Партнёрский сайт'),
                'default' => 0,
                'template' => '%partnership%/form/partner/partner_id.tpl'
            ))
        ));
    }

    /**
     * Добавим связь пунктов меню с партнёскими сайтами
     *
     * @param \Menu\Model\Orm\Menu $menu
     * @return void
     */
    public static function ormInitMenuMenu(Menu $menu)
    {
        $default_list = array(
            0 => t('- Не задан -'),
            '-1' => t('Основной сайт')
        );
        $menu->getPropertyIterator()->append(array(
            t('Основные'),
            'partner_id' => new OrmType\Integer(array(
                'description' => t('Партнёрский сайт'),
                'allowEmpty' => false,
                'default' => 0,
                'listFromArray' => array($default_list + Api::staticSelectList()),
                'hint' => t('Если указан - данный пункт меню будет отображаться только при выборе указанного партнёрского сайта')
            ))
        ));
    }

    /**
     * Добавляем к транзакции дополнительную колонку - партнер ID
     *
     * @param \Shop\Model\Orm\Transaction $orm_transaction - объект транзакции
     */
    public static function ormInitShopTransaction(Transaction $orm_transaction)
    {
        $orm_transaction->getPropertyIterator()->append(array(
            'partner_id' => new OrmType\Integer(array(
                'maxLength' => '11',
                'description' => t('Партнёрский сайт'),
                'default' => 0,
                'template' => '%partnership%/form/partner/partner_id.tpl'
            ))
        ));
    }

    /**
     * Возвращает контексты тем, которые добавляет данный модуль
     *
     * @param array[] $contexts - список контекстов темы оформления
     * @return array[]
     */
    public static function themeGetContextList($contexts)
    {
        $config = ConfigLoader::byModule('partnership');
        if ($config['installed']) {
            $api = new Api();
            foreach ($api->getList() as $partner) {
                /** @var Partner $partner */
                $theme_data = ThemeManager::parseThemeValue($partner['theme']);
                $contexts[$partner->getThemeContext()] = array(
                    'title' => $partner['title'],
                    'theme' => $theme_data['theme']
                );
            }
        }
        return $contexts;
    }

    public static function initDirapiCatalogBlockCategory($controller)
    {
        $partner = Api::getCurrentPartner();
        if ($partner) {
            $ids = $partner->getAllowFolderList();
            if (!empty($ids)) {
                $controller->api->setFilter('id', $ids, 'in');
            }
        }
    }

    public static function controllerAfterInitCatalogFrontListProducts($controller)
    {
        $partner = Api::getCurrentPartner();
        if ($partner) {
            $dirs = $partner->getAllowFolderList();
            if (!empty($dirs)) {
                $controller->api->setFilter('dir', $dirs, 'in');
                if (isset($controller->dirapi)) { // в контроллере catalog-block-searchline нет dirapi
                    $controller->dirapi->setFilter('id', $dirs, 'in');
                }
            }
        }
    }

    /**
     * Добавляем фильтр по партнёрскому сайту к пунктам меню
     *
     * @param \Menu\Controller\Block\Menu $menu_block_controller
     */
    public static function controllerAfterInitMenuBlockMenu($menu_block_controller)
    {
        $partner = Api::getCurrentPartner();
        $partner_id = ($partner['id']) ?: '-1';
        $menu_block_controller->api->setFilter(array(
            array(
                'partner_id' => 0,
                '|partner_id' => $partner_id,
            )
        ));
    }

    /**
     * Подменяет поля from и reply-to в письме уведомления
     *
     * @param array $params - массив с параметрами
     */
    public static function mailerAlertsBeforeSend($params)
    {
        switch ($params['notice']->getSelfType()) {
            case 'shop-orderchange':
                $partner = new Partner($params['notice']->order->partner_id);
                break;
            default:
                $partner = Api::getCurrentPartner();
        }

        if (!empty($partner['id'])) {
            $api = new Api();
            if (!empty($partner['notice_from'])) {
                $params['mailer']->FromName = $api->getPartnerNoticeParsed($partner['notice_from'], false);
                $params['mailer']->From = $api->getPartnerNoticeParsed($partner['notice_from'], true);
            }
            if (!empty($partner['notice_reply'])) {
                $params['mailer']->clearReplyTos();
                $params['mailer']->addReplyTo($api->getPartnerNoticeParsed($partner['notice_reply'], true), $api->getPartnerNoticeParsed($partner['notice_reply'], false));
            }
        }
    }
}
