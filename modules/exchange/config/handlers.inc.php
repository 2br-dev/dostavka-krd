<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Config;

use RS\Event\HandlerAbstract;
use RS\Orm\Type as OrmType;
use RS\Router\Route;

/**
 * Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
 */
class Handlers extends HandlerAbstract
{
    function init()
    {
        $this->bind('getmenus');
        $this->bind('getroute');
    }

    public static function getRoute($routes)
    {
        $routes[] = new Route('exchange-front-gate', array('/site{site_id}/exchange/',), null, t('Шлюз обмена данными'), true);

        return $routes;
    }

    /**
     * Возвращает пункты меню этого модуля в виде массива
     *
     * @param array $items - список пунктов меню
     * @return array
     */
    public static function getMenus($items)
    {
        $items[] = array(
            'title' => t('Обмен данными с 1С'),
            'alias' => 'exchange',
            'link' => '%ADMINPATH%/exchange-ctrl/',
            'typelink' => 'link',
            'parent' => 'products',
            'sortn' => 8
        );
        return $items;
    }
}
