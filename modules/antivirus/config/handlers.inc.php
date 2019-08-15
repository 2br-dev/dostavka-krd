<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Config;

use Antivirus\Model\EventApi;
use Antivirus\Model\MaliciousRequestDetector;
use Antivirus\Model\Orm\RequestCount;
use Antivirus\Model\RequestCountApi;
use Antivirus\Model\SelfUpdater;
use Antivirus\Model\SignVerifyManager;
use antivirus\model\Utils;
use Antivirus\Model\VirusDetectManager;
use RS\Event\HandlerAbstract;
use RS\Router\Route;

class Handlers extends HandlerAbstract
{
    function init()
    {
        $this
            ->bind('applyroute')
            ->bind('getmenus')
            ->bind('meter.recalculate')
            ->bind('cron');
    }

    /**
     * Добавляем информацию о количестве непросмотренных
     * антивирусных событий
     */
    public static function meterRecalculate($meters)
    {
        $event_api = new EventApi();
        $event_meter_api = $event_api->getMeterApi();

        $meters[$event_meter_api->getMeterId()] = $event_meter_api->getUnviewedCounter();

        return $meters;
    }    
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    */
    public static function getMenus($items)
    {
        $router = \RS\Router\Manager::obj();
        
        $items[] = array(
                'title' => t('Антивирус'),
                'alias' => 'antivirus',
                'link' => $router->getAdminUrl(false, array(), 'antivirus-events'),
                'typelink' => 'link',
                'parent' => 'control',
                'sortn' => 7
        );   
        $items[] = array(
                'title' => t('Угрозы безопасности'),
                'alias' => 'antivirus-events',
                'link' => $router->getAdminUrl(false, array(), 'antivirus-events'),
                'typelink' => 'link',
                'parent' => 'antivirus',
                'sortn' => 0
        );   
        $items[] = array(
                'title' => t('Исключения'),
                'alias' => 'antivirus-excludedfiles',
                'link' => $router->getAdminUrl(false, array(), 'antivirus-excludedfiles'),
                'typelink' => 'link',
                'parent' => 'antivirus',
                'sortn' => 10
        );   
        $items[] = array(
                'title' => t('Настройка'),
                'alias' => 'antivirus-settings',
                'link' => $router->getAdminUrl('edit', array('mod' => 'antivirus'), 'modcontrol-control'),
                'typelink' => 'link',
                'parent' => 'antivirus',
                'sortn' => 20
        );
        
        return $items;
    }

    /**
     * Вызывается по расписанию (обычно раз в минуту)
     *
     * @param array $params массив вида ["last_time" => "1452777134", "current_time" => 1452777135, "minutes" => [100,101,102]]
     */
    public static function cron($params)
    {
        Utils::logNewLine();
        Utils::log(t("******************* НАЧАЛО *********************"));
        Utils::log(t("Антивирус: начало выполнения плановых заданий..."));

        SelfUpdater::getInstance()->onCron();               // Самообновление модуля
        SignVerifyManager::getInstance()->execute();        // Проверка подписей файлов
        VirusDetectManager::getInstance()->execute();       // Поиск вирусов и вредоносного кода
        RequestCountApi::getInstance()->onCron();           // Блокировка по частоте запросов
        MaliciousRequestDetector::getInstance()->onCron();  // Блокировка вредоносных запросов

        Utils::log(t("******************* КОНЕЦ **********************"));
        Utils::logNewLine();
    }

    /**
     * Вызывается в момент определения роута
     *
     * @param $route Route
     */
    public static function applyroute($route)
    {
        RequestCountApi::getInstance()->onRequest();
        MaliciousRequestDetector::getInstance()->onRequest();
    }

}
