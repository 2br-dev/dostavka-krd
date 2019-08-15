<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Controller\Admin\Widget;

use Antivirus\Model\EventApi;
use Antivirus\Model\Orm\Event;
use Antivirus\Model\SignVerifyManager;
use Antivirus\Model\VirusDetectManager;
use RS\AccessControl\Rights;
use Antivirus\Config\ModuleRights;

class StateInfo extends \RS\Controller\Admin\Widget
{
    protected
        $action_var = 'avdo',
        $info_title = 'Антивирус',
        $info_description = 'Информация о состоянии целостности системы и других обнаруженных проблемах';

    private $event_api;

    function __construct($params = array())
    {
        parent::__construct($params);
        $this->event_api = new EventApi();
    }
    
    function actionIndex()
    {
        $integrity_intensive = SignVerifyManager::getInstance()->isIntensiveModeEnabled();
        $antivirus_intensive = VirusDetectManager::getInstance()->isIntensiveModeEnabled();
        $intensive           = $integrity_intensive || $antivirus_intensive;

        // Общее количество вычисляется только в режиме "полной проверки"
        $integrity_total = $integrity_intensive ? SignVerifyManager::getInstance()->getTotalCount() : 0;
        $antivirus_total = $antivirus_intensive ? VirusDetectManager::getInstance()->getTotalCount() : 0;

        $this->view->assign(array(
            'ctrl' => $this,
            'integrity' => array(
                'progress'          => round(100 * SignVerifyManager::getInstance()->getPosition() / SignVerifyManager::getInstance()->getTotalCount()),
                'completed'         => SignVerifyManager::getInstance()->getLastCycleCompletedDate(),
                'event_list_url'    => $this->router->getAdminUrl('index', array('f'=>array('component'=>'integrity')), 'antivirus-events'),
                'is_intensive'      => $integrity_intensive,
                'global_position'   => SignVerifyManager::getInstance()->getPosition(),
                'total_files_count' => $integrity_total,
                'unread_event_count'=> $this->event_api->getUnreadEventCount(Event::COMPONENT_INTEGRITY),
            ),
            'antivirus' => array(
                'progress'          => round(100 * VirusDetectManager::getInstance()->getPosition() / VirusDetectManager::getInstance()->getTotalCount()),
                'completed'         => VirusDetectManager::getInstance()->getLastCycleCompletedDate(),
                'event_list_url'    => $this->router->getAdminUrl('index', array('f'=>array('component'=>'antivirus')), 'antivirus-events'),
                'is_intensive'      => $antivirus_intensive,
                'global_position'   => VirusDetectManager::getInstance()->getPosition(),
                'total_files_count' => $antivirus_total,
                'unread_event_count'=> $this->event_api->getUnreadEventCount(Event::COMPONENT_ANTIVIRUS),
            ),
            'proactive' => array(
                'event_list_url'    => $this->router->getAdminUrl('index', array('f'=>array('component'=>'proactive')), 'antivirus-events'),
                'unread_event_count'=> $this->event_api->getUnreadEventCount(Event::COMPONENT_PROACTIVE),
            ),
            'excluded_list_url' => $this->router->getAdminUrl('index', null, 'antivirus-excludedFiles'),
            'is_cron_work'      => \RS\Cron\Manager::obj()->isCronWork(),
            'refresh_url'       => $this->router->getAdminUrl(false, array('avdo' => $intensive ? 'indexIntensive' : null), $this->getUrlName()),
        ));

        $this->app->addJs( $this->mod_js.'widget.js', null, BP_ROOT);
        $this->app->addJsVar('antivirus_widget_update_url', $this->router->getAdminUrl('index', null, 'antivirus-widget-stateinfo'));
        return $this->result->setTemplate( 'widget/state_info.tpl' );
    }

    function actionIndexIntensive()
    {
        ob_start();
        SignVerifyManager::getInstance()->execute();
        VirusDetectManager::getInstance()->execute();
        ob_get_clean();

        return $this->actionIndex();
    }

    function actionEnableIntegrityIntensiveMode()
    {
        if ($access_error = Rights::CheckRightError($this, ModuleRights::RIGHT_INTENSIVE_MODE)) {
            $this->result->addEMessage($access_error);
        } else {
            SignVerifyManager::getInstance()->enableIntensiveMode(true);
        }
        return $this->actionIndex();
    }

    function actionDisableIntegrityIntensiveMode()
    {
        SignVerifyManager::getInstance()->enableIntensiveMode(false);
        return $this->actionIndex();
    }

    function actionEnableAntivirusIntensiveMode()
    {
        if ($access_error = Rights::CheckRightError($this, ModuleRights::RIGHT_INTENSIVE_MODE)) {
            $this->result->addEMessage($access_error);
        } else {
            VirusDetectManager::getInstance()->enableIntensiveMode(true);
        }
        return $this->actionIndex();
    }

    function actionDisableAntivirusIntensiveMode()
    {
        VirusDetectManager::getInstance()->enableIntensiveMode(false);
        return $this->actionIndex();
    }

    function actionReadIntegrityEvents()
    {
        $this->event_api->markAsViewed(Event::COMPONENT_INTEGRITY);
        return $this->actionIndex();
    }

    function actionReadAntivirusEvents()
    {
        $this->event_api->markAsViewed(Event::COMPONENT_ANTIVIRUS);
        return $this->actionIndex();
    }

    function actionReadProactiveEvents()
    {
        $this->event_api->markAsViewed(Event::COMPONENT_PROACTIVE);
        return $this->actionIndex();
    }

    function getIpCount()
    {
        return count($this->event_api->getUnreadAttacksIps());
    }

}

