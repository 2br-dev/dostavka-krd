<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model;
use RS\Application\Application;
use RS\Helper\Tools;
use RS\Http\Request;

/**
 * Class для работы с источникам пользователя
 * @package Statistic\Model
 */
class UserSourceApi
{
    const SOURCE_COOKIE_NAME = 'user_source'; //Кука для хранения источника
    const LAST_SOURCE_COOKIE_NAME = 'last_user_utm'; //Кука для хранения последней UTM метки

    /**
     * Возвращает исходное значение cookie last_user_utm без экранирования
     *
     * @return mixed
     */
    public static function getLastSourceUtmCookie()
    {
        $request = Request::commonInstance();
        $last_source = $request->cookie(UserSourceApi::LAST_SOURCE_COOKIE_NAME, TYPE_STRING, '', null);

        return $last_source;
    }

    /**
     * Возвращает массив данных, подготовленных для вставки в базу данных (экранированных)
     *
     * @return array
     */
    public static function getLastSourceUtmCookieArray()
    {
        $data_string = self::getLastSourceUtmCookie();

        $data = (array)@unserialize($data_string);

        //Оставляем только нужные ключи
        $data_filtered = array_intersect_key($data, array_flip(array(
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'utm_dateof')));

        foreach($data_filtered as &$item) {
            $item = Tools::toEntityString((string)$item);
        }

        return $data_filtered;
    }


    /**
     * Возвращает сведения по источнику пользователя из куки
     *
     * @return array
     */
    public static function getCookieSourceInfo()
    {
        $request = Request::commonInstance();
        $user_source_string = $request->cookie(self::SOURCE_COOKIE_NAME, TYPE_STRING, '', null);
        $user_source = $user_source_string ? (array)@unserialize($user_source_string) : array();

        //Оставляем только нужные ключи
        $data_filtered = array_intersect_key($user_source, array_flip(array(
            'partner_id',
            'referer_site',
            'referer_source',
            'landing_page',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'dateof')));

        foreach($data_filtered as &$item) {
            $item = Tools::toEntityString((string)$item);
        }

        return $data_filtered;
    }

    /**
     * Проверяет сущестует ли у пользователя кука источника от которого пришёл пользователя
     *
     * @return boolean
     */
    public static function checkUnsetUserCookie()
    {
        return Request::commonInstance()->cookie(self::SOURCE_COOKIE_NAME, TYPE_STRING, false) === false;
    }

    /**
     * Проверяет назначен ли у пользователя источник
     * @param \Users\Model\Orm\User $user - объект пользователя
     *
     * @return boolean
     */
    public static function checkUnsetUserSourceId(\Users\Model\Orm\User $user)
    {
        if ($user['source_id'] == "-1") {
            return false;
        }
        return (empty($user['source_id']) && !$user->isAdmin());
    }

    /**
     * Возвращает сайт с которого перешел пользователь из адреса
     *
     * @param string $referer - источник
     * @return string
     */
    public static function getHttpRefererSiteFromURL($referer)
    {
        if (empty($referer)){
            return null;
        }

        return parse_url($referer, PHP_URL_HOST);
    }

    /**
     * Устанавливаем куку для пользователя с источником перехода
     */
    public static function setCookieSourceInfo()
    {
        $partner_id = "";
        if (\RS\Module\Manager::staticModuleEnabled('partnership')){
            $partner = \Partnership\Model\Api::getCurrentPartner();
            if ($partner['id']){
                $partner_id = $partner['id'];
            }
        }

        $request = Request::commonInstance();

        //Смотрим сам источник. Все данные без экранирования пока
        $request_uri = $request->server('REQUEST_URI', TYPE_STRING, null, null);
        $query_str   = $request->server('QUERY_STRING', TYPE_STRING, null, null);
        $http_referer = $request->server('HTTP_REFERER', TYPE_STRING, null, null);
        $http_host = $request->server('HTTP_HOST', TYPE_STRING, null, null);

        parse_str($query_str, $query_arr);

        $source = array(
            'partner_id'     => $partner_id,
            'referer_site'   => $http_referer ? self::getHttpRefererSiteFromURL($http_referer) : self::getHttpRefererSiteFromURL($http_host.$request_uri),
            'referer_source' => $http_referer,
            'landing_page'   => $http_host.$request_uri,

            //UTM метки
            'utm_source'    => isset($query_arr['utm_source']) ? $query_arr['utm_source'] : "",
            'utm_medium'    => isset($query_arr['utm_medium']) ? $query_arr['utm_medium'] : "",
            'utm_campaign'  => isset($query_arr['utm_campaign']) ? $query_arr['utm_campaign'] : "",
            'utm_term'      => isset($query_arr['utm_term']) ? $query_arr['utm_term'] : "",
            'utm_content'   => isset($query_arr['utm_content']) ? $query_arr['utm_content'] : "",
            'dateof'        => date('Y-m-d H:i:s'),
        );

        //Запишем на год
        Application::getInstance()
            ->headers
            ->addCookie(self::SOURCE_COOKIE_NAME, serialize($source), time() + 60 * 60 * 24 * 365, "/");
    }

    /**
     * Устанавливаем источник для пользователя
     *
     * @param \Users\Model\Orm\User $user - пользователь
     * @throws \RS\Db\Exception
     */
    public function setSourceInfoToUser($user)
    {
        $source = new \Statistic\Model\Orm\Source();
        $source->getFromArray(self::getCookieSourceInfo());
        if ($source->insert()){
            $this->setSourceToUser($user, $source);
        }
    }

    /**
     * Устанавливает последние UTM метки откуда пришёл пользователь
     *
     * @return void
     */
    public static function setLastUTMToCookie()
    {
        $utms = array(
            'utm_source'   => $_REQUEST['utm_source'],
            'utm_medium'   => isset($_REQUEST['utm_medium']) ? $_REQUEST['utm_medium'] : "",
            'utm_campaign' => isset($_REQUEST['utm_campaign']) ? $_REQUEST['utm_campaign'] : "",
            'utm_term'     => isset($_REQUEST['utm_term']) ? $_REQUEST['utm_term'] : "",
            'utm_content'  => isset($_REQUEST['utm_content']) ? $_REQUEST['utm_content'] : "",
            'utm_dateof'   => date('Y-m-d H:i:s'),
        );

        Application::getInstance()
            ->headers
            ->addCookie(self::LAST_SOURCE_COOKIE_NAME, serialize($utms), time() + 60 * 60 * 24 * 365, "/");
    }


    /**
     * Устанавливает идентификатор источника к пользователю
     *
     * @param \Users\Model\Orm\User $user - объект пользователя
     * @param \Statistic\Model\Orm\Source $source - источник
     * @throws \RS\Db\Exception
     */
    private function setSourceToUser($user, $source)
    {
        \RS\Orm\Request::make()
            ->update()
            ->from($user)
            ->where(array(
                'id' => $user['id']
            ))
            ->set(array(
                'source_id' => $source['id'],
                'date_arrive' => $source['dateof'] //Источник первого прихода
            ))->exec();
        $user['source_id'] = $source['id'];
    }

    /**
     * Устанавливает идентификатор источника к заказу
     *
     * @param \Shop\Model\Orm\Order $order - объект пользователя
     * @param integer $source_id - id источника
     * @throws \RS\Db\Exception
     */
    private function setSourceIdToOrder($order, $source_id)
    {
        \RS\Orm\Request::make()
            ->update()
            ->from($order)
            ->where(array(
                'id' => $order['id']
            ))
            ->set(array(
                'source_id' => $source_id
            ))->exec();
        $order['source_id'] = $source_id;
    }

    /**
     * Устанавливает последние UTM метки источника к объекту
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @throws \RS\Db\Exception
     */
    function setUTMToOrderFromCookie($order)
    {
        $data = self::getLastSourceUtmCookieArray();
        $order->getFromArray($data);

        \RS\Orm\Request::make()
            ->update()
            ->from($order)
            ->where(array(
                'id' => $order['id']
            ))
            ->set(array(
                'utm_source'   => $data['utm_source'],
                'utm_medium'   => $data['utm_medium'],
                'utm_campaign' => $data['utm_campaign'],
                'utm_term'     => $data['utm_term'],
                'utm_content'  => $data['utm_content'],
                'utm_dateof'   => $data['utm_dateof']
            ))->exec();

        self::deleteLastUTMSourceCookie();
    }

    /**
     * Удаляет куку UTM последнего источника
     *
     * @return void
     */
    public static function deleteLastUTMSourceCookie()
    {
        Application::getInstance()
            ->headers
            ->addCookie(self::LAST_SOURCE_COOKIE_NAME, '', time()-300, "/");
    }



    /**
     * Устанавливает источник перехода пользователя у заказа из пользователя или из куки
     *
     * @param \Shop\Model\Orm\Order|\Shop\Model\Orm\Reservation|\Catalog\Model\Orm\OneClickItem $order - объект заказа, предзаказа или купить в один клик
     * @return void
     * @throws \RS\Db\Exception
     */
    function setSourceToOrder($order)
    {
        if (empty($order['source_id'])){
            $user      = new \Users\Model\Orm\User($order['user_id']);
            $source_id = false;
            $source    = new \Statistic\Model\Orm\Source();
            
            if ((int)$order['user_id'] <= 0 && !self::checkUnsetUserCookie()){
                if (!\RS\Application\Auth::isAuthorize() || (\RS\Application\Auth::isAuthorize() && !$user->isAdmin())){ //Подстрахуемся, чтобы админ не записал свои данные
                    $source->getFromArray(self::getCookieSourceInfo());
                    $source->insert();
                    $source_id = $source['id'];
                }
            }else if($order['user_id'] > 0 && !$user->isAdmin()){ //Если есть пользователь и не админ
                $source_id = ($user['source_id'] > 0) ? $user['source_id'] : false;
            }

            //Обновим источник у заказа
            if ($source_id){
                $this->setSourceIdToOrder($order, $source_id);
            }
        }
    }
}