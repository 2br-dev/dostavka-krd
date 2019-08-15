<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Config;
use Crm\Model\AutoTask\RuleIf\CreateFeedback;
use Crm\Model\Autotask\Ruleif\CreateOneClick;
use Crm\Model\Autotask\Ruleif\CreateOrder;
use Crm\Model\Autotask\Ruleif\CreateReservation;
use Crm\Model\AutoTaskRuleApi;
use Crm\Model\Board\DealBoardItem;
use Crm\Model\Board\TaskBoardItem;
use Crm\Model\Links\LinkManager;
use Crm\Model\Links\Type\LinkTypeOneClickItem;
use Crm\Model\Links\Type\LinkTypeOrder;
use Crm\Model\Links\Type\LinkTypeReservation;
use Crm\Model\Links\Type\LinkTypeUser;
use Crm\Model\TaskApi;
use RS\Orm\AbstractObject;

/**
* Класс содержит обработчики событий, на которые подписан модуль
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    /**
    * Добавляет подписку на события
    * 
    * @return void
    */
    function init()
    {
        $this
            ->bind('orm.init.users-user')
            ->bind('orm.init.catalog-oneclickitem')
            ->bind('orm.init.shop-reservation')
            ->bind('orm.afterwrite.shop-order')
            ->bind('orm.afterwrite.catalog-oneclickitem')
            ->bind('orm.afterwrite.shop-reservation')
            ->bind('orm.afterwrite.feedback-resultitem')
            ->bind('crm.getboardtypes')
            ->bind('cron')
            ->bind('getmenus'); //событие сбора пунктов меню для административной панели
    }

    /**
     * Обработчик события планировщика
     *
     * @param array @params
     */
    public static function cron($params)
    {
        $task_api = new TaskApi();
        foreach($params['minutes'] as $minute) {
            if (($minute % 2) == 0) { //Раз в 2 минуты проверять

                $count = $task_api->sendTaskNotice();

                echo t('Отправлено %0 сообщений о скором окончании срока выполнения задач', array($count));

                break; //Выполняем только один раз
            }
        }
    }

    /**
     * Обработчик вызывается при ответе в форме обратной связи
     *
     * @param array $params
     */
    public static function ormAfterwriteFeedbackResultItem($params)
    {
        $feedback = $params['orm'];
        if ($params['flag'] == AbstractObject::INSERT_FLAG) {

            //Создаем автозадачи, если необходимо
            $event = new CreateFeedback();
            $event->init($feedback);

            AutoTaskRuleApi::run($event);
        }

    }

    /**
     * Привязываем CRM сущности к создаваемому заказу
     */
    public static function ormAfterwriteShopOrder($params)
    {
        $order = $params['orm'];
        if ($params['flag'] == AbstractObject::INSERT_FLAG) {
            if ($order['_tmpid']<0){
                //Обновляем ID у всех объектов CRM, привязанных к заказу
                LinkManager::updateLinkId($order['_tmpid'], $order['id'], LinkTypeOrder::getId());
            }

            //Создаем автозадачи, если необходимо
            $event = new CreateOrder();
            $event->init($order);

            AutoTaskRuleApi::run($event);
        }
    }

    /**
     * Обработчик вызывается при создании покупки в 1 клик
     *
     * @param array $params
     */
    public static function ormAfterwriteCatalogOneClickItem($params)
    {
        $oneclick = $params['orm'];
        if ($params['flag'] == AbstractObject::INSERT_FLAG) {
            //Создаем автозадачи, если необходимо
            $event = new CreateOneClick();
            $event->init($oneclick);

            AutoTaskRuleApi::run($event);
        }
    }

    /**
     * Обработчик вызывается при создании предзаказа
     *
     * @param array $params
     */
    public static function ormAfterwriteShopReservation($params)
    {
        $reservation = $params['orm'];
        if ($params['flag'] == AbstractObject::INSERT_FLAG) {
            //Создаем автозадачи, если необходимо
            $event = new CreateReservation();
            $event->init($reservation);

            AutoTaskRuleApi::run($event);
        }

    }

    /**
     * Добавляет покупке в 1 клик блок CRM
     */
    public static function ormInitShopReservation($reservation)
    {
        $reservation->getPropertyIterator()->append(array(
            t('Сделки'),
            '__deal__' => new \Crm\Model\OrmType\DealBlock(array(
                'linkType' => LinkTypeReservation::getId(),
                'onlyExists' => true
            ))
        ));
    }

    /**
     * Добавляет покупке в 1 клик блок CRM
     */
    public static function ormInitCatalogOneClickItem($one_click_item)
    {
        $one_click_item->getPropertyIterator()->append(array(
            t('Сделки'),
            '__deal__' => new \Crm\Model\OrmType\DealBlock(array(
                'linkType' => LinkTypeOneClickItem::getId(),
                'onlyExists' => true
            ))
        ));
    }

    /**
     * Добавляет вкладку взаимодействия у клиента
     */
    public static function ormInitUsersUser($user)
    {
        $user->getPropertyIterator()->append(array(
            t('Взаимодействия'),
            '__interaction__' => new \Crm\Model\OrmType\InteractionBlock(array(
                'linkType' => LinkTypeUser::getId(),
                'onlyExists' => true
            )),
        ));
    }

    /**
    * Возвращает пункты меню этого модуля в виде массива
    *
    * @param array $items - массив с пунктами меню
    * @return array
    */
    public static function getMenus($items)
    {
        $items[] = array(
            'title' => 'CRM',
            'alias' => 'crm',
            'link' => '%ADMINPATH%/crm-dealctrl/',
            'parent' => 0,
            'typelink' => 'link',
        );

        $items[] = array(
            'title' => t('Сделки'),
            'alias' => 'crm-deal',
            'link' => '%ADMINPATH%/crm-dealctrl/',
            'parent' => 'crm',
            'typelink' => 'link',
            'sortn' => 10
        );

        $items[] = array(
            'title' => t('Взаимодействия'),
            'alias' => 'crm-interaction',
            'link' => '%ADMINPATH%/crm-interactionctrl/',
            'parent' => 'crm',
            'typelink' => 'link',
            'sortn' => 20
        );

        $items[] = array(
            'title' => t('Задачи'),
            'alias' => 'crm-task',
            'link' => '%ADMINPATH%/crm-taskctrl/',
            'parent' => 'crm',
            'typelink' => 'link',
            'sortn' => 30
        );

        $items[] = array(
            'title' => t('Правила для автозадач'),
            'alias' => 'crm-autotask',
            'link' => '%ADMINPATH%/crm-autotaskrulectrl/',
            'parent' => 'crm',
            'typelink' => 'link',
            'sortn' => 40
        );

        $items[] = array(
            'title' => t('Kanban доска'),
            'alias' => 'crm-board',
            'link' => '%ADMINPATH%/crm-boardctrl/',
            'parent' => 'crm',
            'typelink' => 'link',
            'sortn' => 50
        );

        return $items;
    }

    /**
     * Регистрирует типы объектов, отображаемых на доске Kanban
     *
     * @return array
     */
    public static function crmGetBoardTypes($list)
    {
        $list[] = new TaskBoardItem();
        $list[] = new DealBoardItem();

        return $list;
    }
}