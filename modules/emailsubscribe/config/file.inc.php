<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace EmailSubscribe\Config;

use RS\Orm\ConfigObject;
use RS\Orm\Type;

/**
* Конфигурационный файл модуля 
*/
class File extends ConfigObject
{  
    public function _init()
    {
        parent::_init()->append(array(
            'dialog_open_delay' => new Type\Integer(array(
                'description' => t('Время задержки перед открытием диалог подписки'),
                'hint' => t('0 - всплывающее окно показываться не будет')
            )),
            'send_confirm_email' => new Type\Integer(array(
                'description' => t('Отправлять письмо со ссылкой на подтверждение'),
                'checkboxView' => array(1, 0),
            )),
        ));
    }
}
