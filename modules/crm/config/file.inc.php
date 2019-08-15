<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Config;

use RS\Config\UserFieldsManager;
use RS\Orm\ConfigObject;
use RS\Orm\Type;
use RS\Router\Manager as RouterManager;

/**
* Класс конфигурации модуля
*/
class File extends ConfigObject
{
    public function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'widget_task_pagesize' => new Type\Integer(array(
                    'description' => t('Количество выводимых задач в виджете')
                )),
            t('Задачи'),
                'expiration_task_notice_statuses' => new Type\ArrayList(array(
                    'description' => t('Подходящие статусы для отправки уведомлений об истечении срока действия задачи (Удерживая CTRL можно выбрать несколько)'),
                    'hint' => t('ReadyScript будет уведомлять об истечении срока действия задачи, только если она находится в одном из выбранных статусов'),
                    'list' => array(array('\Crm\Model\Orm\Status', 'getStatusesTitles'), 'crm-task', array(0 => 'Любой статус')),
                    'Attr' => array(array('size' => 5, 'multiple' => 'multiple', 'class' => 'multiselect')),
                    'runtime'=>false,
                )),
                'expiration_task_default_notice_time' => new Type\Integer(array(
                    'description' => t('Время для уведомлений по умолчанию'),
                    'hint' => t('На сайте должен бытьнастроен планировщик для работы уведомлений. См.документацию для пользователей'),
                    'list' => array(array(__CLASS__, 'getNoticeExpirationTimeList'))
                )),
            t('Дополнительные поля сделок'),
                '__deal_userfields__' => new Type\UserTemplate('%crm%/form/config/deal_userfield.tpl'),
                'deal_userfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные поля сделок'),
                    'runtime' => false,
                    'visible' => false
                )),
            t('Дополнительные поля взаимодействий'),
                '__interaction_userfields__' => new Type\UserTemplate('%crm%/form/config/interaction_userfield.tpl'),
                'interaction_userfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные поля взаимодействий'),
                    'runtime' => false,
                    'visible' => false
                )),
            t('Дополнительные поля задач'),
                '__task_userfields__' => new Type\UserTemplate('%crm%/form/config/task_userfield.tpl'),
                'task_userfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные поля задач'),
                    'runtime' => false,
                    'visible' => false
                )),
        ));
    }

    /**
     * Возвращает список возможных временных интервалов для отправки уведомлений до истечения срока действия
     * @return int[] В ключе - кол-во секунд
     */
    public static function getNoticeExpirationTimeList()
    {
        return array(
            0      => t('Не выбрано'),
            300    => t('5 минут'),
            900    => t('15 минут'),
            1800   => t('30 минут'),
            3600   => t('1 час'),
            10800  => t('3 часа'),
            21600  => t('6 часов'),
            43200  => t('12 часов'),
            86400  => t('24 часа'),
            172800 => t('48 часов')
        );
    }

    /**
     * Сообщаем, что данный модуль не мультисайтовый и его настройки общие для всех сайтов
     *
     * @return bool
     */
    public function isMultisiteConfig()
    {
        return false;
    }

    /**
     * Возвращает список действий для панели конфига
     *
     * @return array
     */
    public static function getDefaultValues()
    {
        $router = RouterManager::obj();

        return parent::getDefaultValues() + array(
            'tools' => array(
                array(
                    'url' => $router->getAdminUrl(false, array(), 'crm-statusctrl'),
                    'title' => t('Управление статусами'),
                    'description' => t('В этом разделе можно настроить статусы сделок, задач.'),
                    'class' => ' '
                ),
                array(
                    'url' => $router->getAdminUrl('deleteUnlinked', array(), 'crm-interactionctrl'),
                    'title' => t('Удалить несвязанные с другими объектами взаимодействия'),
                    'description' => t('Не связанные взаимодействия бесполезны, поэтому вы можете использовать данный инструмент для их удаления'),
                    'confirm' => t('Вы действительно желаете удалить все несвязанные взаимодействия?')
                )
            )
        );
    }


    /**
     * Возвращает объект, отвечающий за работу с пользовательскими полями объекта сделки.
     *
     * @return \RS\Config\UserFieldsManager
     */
    public function getDealUserFieldsManager()
    {
        return new UserFieldsManager($this['deal_userfields'], null, 'deal_userfields');
    }


    /**
     * Возвращает объект, отвечающий за работу с пользовательскими полями объекта взаимодействий.
     *
     * @return \RS\Config\UserFieldsManager
     */
    public function getInteractionUserFieldsManager()
    {
        return new UserFieldsManager($this['interaction_userfields'], null, 'interaction_userfields');
    }

    /**
     * Возвращает объект, отвечающий за работу с пользовательскими полями объекта задачи.
     *
     * @return \RS\Config\UserFieldsManager
     */
    public function getTaskUserFieldsManager()
    {
        return new UserFieldsManager($this['task_userfields'], null, 'task_userfields');
    }
}
