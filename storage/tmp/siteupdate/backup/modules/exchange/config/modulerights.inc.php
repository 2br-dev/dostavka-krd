<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Config;

use RS\AccessControl\Right;
use RS\AccessControl\RightGroup;

class ModuleRights extends \RS\AccessControl\DefaultModuleRights
{
    const
        RIGHT_EXCHANGE = 'exchange';
    
    protected function getSelfModuleRights()
    {
        return array(
            new Right(self::RIGHT_READ, t('Чтение')),
            new Right(self::RIGHT_EXCHANGE, t('Импорт/экспорт')),
        );
    }
}
