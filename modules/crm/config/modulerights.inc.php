<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Config;

use RS\AccessControl\AutoCheckers\ControllerChecker;
use RS\AccessControl\DefaultModuleRights;
use \RS\AccessControl\Right;
use RS\AccessControl\RightGroup;

/**
 * Класс описывает права, которые есть у данного модуля
 */
class ModuleRights extends DefaultModuleRights
{
    const
        INTERACTION_CREATE = 'interaction_create',
        INTERACTION_READ = 'interaction_read',
        INTERACTION_UPDATE = 'interaction_update',
        INTERACTION_DELETE = 'interaction_delete',
        INTERACTION_OTHER_READ = 'interaction_other_read',

        DEAL_CREATE = 'deal_create',
        DEAL_READ = 'deal_read',
        DEAL_UPDATE = 'deal_update',
        DEAL_DELETE = 'deal_delete',
        DEAL_OTHER_READ = 'deal_other_read',

        AUTOTASK_CREATE = 'autotask_create',
        AUTOTASK_READ = 'autotask_read',
        AUTOTASK_UPDATE = 'autotask_update',
        AUTOTASK_DELETE = 'autotask_delete',

        TASK_CREATE = 'task_create',
        TASK_READ = 'task_read',
        TASK_UPDATE = 'task_update',
        TASK_DELETE = 'task_delete',

        TASK_FILTER_CREATE = 'task_filter_create',
        TASK_FILTER_UPDATE = 'task_filter_update',
        TASK_FILTER_DELETE = 'task_filter_delete',

        TASK_OTHER_READ = 'task_other_read',
        TASK_OTHER_CREATE = 'task_other_create',
        TASK_OTHER_UPDATE = 'task_other_update',
        TASK_OTHER_DELETE = 'task_other_delete',

        TASK_CHANGE_IMPLEMENTER_USER = 'task_change_implementer_user',
        TASK_CHANGE_CREATOR_USER = 'task_change_creator_user',

        STATUS_CREATE = 'status_create',
        STATUS_READ = 'status_read',
        STATUS_UPDATE = 'status_update',
        STATUS_DELETE = 'status_delete';

    /**
     * Возвращает возможные права модуля
     *
     * @return (Right|RightGroup)[]
     */
    protected function getSelfModuleRights()
    {
        return array(
            new RightGroup('crm_interaction', t('Взаимодействия'), array(
                new Right(self::INTERACTION_CREATE, t('Создание')),
                new Right(self::INTERACTION_READ, t('Чтение')),
                new Right(self::INTERACTION_UPDATE, t('Изменение')),
                new Right(self::INTERACTION_DELETE, t('Удаление')),
                new Right(self::INTERACTION_OTHER_READ, t('Просмотр чужих взаимодействий')),

            )),

            new RightGroup('crm_deal', t('Сделки'), array(
                new Right(self::DEAL_CREATE, t('Создание')),
                new Right(self::DEAL_READ, t('Чтение')),
                new Right(self::DEAL_UPDATE, t('Изменение')),
                new Right(self::DEAL_DELETE, t('Удаление')),
                new Right(self::DEAL_OTHER_READ, t('Просмотр чужих сделок')),
            )),

            new RightGroup('crm_autotask', t('Автозадачи'), array(
                new Right(self::AUTOTASK_CREATE, t('Создание')),
                new Right(self::AUTOTASK_READ, t('Чтение')),
                new Right(self::AUTOTASK_UPDATE, t('Изменение')),
                new Right(self::AUTOTASK_DELETE, t('Удаление'))
            )),

            new RightGroup('crm_task', t('Задачи'), array(
                new Right(self::TASK_CREATE, t('Создание задач')),
                new Right(self::TASK_READ, t('Просмотр задач')),
                new Right(self::TASK_UPDATE, t('Изменение задач')),
                new Right(self::TASK_DELETE, t('Удаление задач')),
                new Right(self::TASK_OTHER_READ, t('Просмотр чужих задач')),
                new Right(self::TASK_OTHER_UPDATE, t('Изменение чужих задач')),
                new Right(self::TASK_OTHER_DELETE, t('Удаление чужих задач')),
                new Right(self::TASK_CHANGE_IMPLEMENTER_USER, t('Изменение исполнителя у задачи')),
                new Right(self::TASK_CHANGE_CREATOR_USER, t('Изменение создателя у задачи'))
            )),

            new RightGroup('crm_taskfilter', t('Фильтры для задач'), array(
                new Right(self::TASK_FILTER_CREATE, t('Создание фильтра')),
                new Right(self::TASK_FILTER_UPDATE, t('Обновление фильтра')),
                new Right(self::TASK_FILTER_DELETE, t('Удаление фильтра')),
            )),

            new RightGroup('crm_status', t('Статусы'), array(
                new Right(self::STATUS_CREATE, t('Создание')),
                new Right(self::STATUS_READ, t('Чтение')),
                new Right(self::STATUS_UPDATE, t('Изменение')),
                new Right(self::STATUS_DELETE, t('Удаление'))
            ))
        );
    }


    /**
     * Возвращает собственные инструкции для автоматических проверок
     *
     * @return \RS\AccessControl\AutoCheckers\AutoCheckerInterface[]
     */
    protected function getSelfAutoCheckers()
    {
        return array(
            new ControllerChecker('crm-admin-dealctrl', '*', 'index', array(), self::DEAL_READ),
            new ControllerChecker('crm-admin-interactionctrl', '*', 'index', array(), self::INTERACTION_READ),
            new ControllerChecker('crm-admin-taskctrl', '*', 'index', array(), self::TASK_READ),
            new ControllerChecker('crm-admin-boardctrl', '*', 'index', array(), self::TASK_READ),
            new ControllerChecker('crm-admin-autotaskrulectrl', '*', 'index', array(), self::AUTOTASK_READ),
            new ControllerChecker('crm-admin-statusctrl', '*', 'index', array(), self::STATUS_READ),
        );
    }
}