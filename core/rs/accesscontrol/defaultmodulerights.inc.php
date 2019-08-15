<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\AccessControl;

use RS\AccessControl\AutoCheckers\ControllerChecker;

/**
* Объект прав модуля по умолчанию
*/
class DefaultModuleRights extends AbstractModuleRights
{
    const
        RIGHT_READ = 'read',
        RIGHT_CREATE = 'create',
        RIGHT_UPDATE = 'update',
        RIGHT_DELETE = 'delete';
    
    /**
    * Возвращает древовидный список собственных прав модуля
    * 
    * @return (Right|RightGroup)[]
    */
    protected function getSelfModuleRights()
    {
        return array(
            new Right(self::RIGHT_READ, t('Чтение')),
            new Right(self::RIGHT_CREATE, t('Создание')),
            new Right(self::RIGHT_UPDATE, t('Изменение')),
            new Right(self::RIGHT_DELETE, t('Удаление')),
        );
    }
    
    /**
    * Возвращает список собственных инструкций для автоматических проверок прав
    * 
    * @return \RS\AccessControl\AutoCheckers\AutoCheckerInterface[]
    */
    protected function getSelfAutoCheckers()
    {
        return array(
            new ControllerChecker('', '*', '*', array(), self::RIGHT_READ, true),
        );
    }
}
