<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Export\Config;

use Export\Model\ExportType;
use RS\Event\HandlerAbstract;
use RS\Router\Route;
use RS\Orm\Type as OrmType;

class Handlers extends HandlerAbstract
{
    function init()
    {
        $this->bind('getmenus');
        $this->bind('getroute');
        $this->bind('export.gettypes');
    }

    /**
     * Возвращает маршруты данного модуля
     */
    public static function getRoute(array $routes)
    {
        $routes[] = new Route('export-front-gate', array(
            '/site{site_id}/export-{export_type}-{export_id}.xml',
            '/site{site_id}/export-{export_type}-{export_id}/',
        ), null, t('Шлюз экспорта данных'), true);

        return $routes;
    }

    /**
     * Возвращает список доступных типов экспорта
     */
    public static function exportGetTypes($list)
    {
        $list[] = new ExportType\Yandex\Yandex();
        $list[] = new ExportType\MailRu\MailRu();
        $list[] = new ExportType\Google\Google();
        $list[] = new ExportType\Avito\Avito();
        $list[] = new ExportType\Facebook\Facebook();
        return $list;
    }

    /**
     * Возвращает пункты меню этого модуля в виде массива
     *
     */
    public static function getMenus($items)
    {
        $items[] = array(
            'title' => t('Экспорт данных'),
            'alias' => 'export',
            'link' => '%ADMINPATH%/export-ctrl/',
            'typelink' => 'link',
            'parent' => 'products',
            'sortn' => 7
        );
        return $items;
    }
}
