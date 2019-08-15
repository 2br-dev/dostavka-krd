<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Model\Orm;

use RS\Orm\OrmObject;
use RS\Orm\Type;

/**
 * ORM объект, отвечающий за хранение событий для подсистемы long polling
 */
class LongPollingEvent extends OrmObject
{
    protected static $table = 'long_polling_event';

    protected function _init()
    {
        parent::_init()->append(array(
            'user_id' => new Type\User(array(
                'description' => t('Пользователь-адресат')
            )),
            'date_create' => new Type\Datetime(array(
                'description' => t('Дата создания события')
            )),
            'event_name' => new Type\Varchar(array(
                'description' => t('Имя события')
            )),
            'json_data' => new Type\Text(array(
                'description' => t('JSON данные, которые следует передать с событием')
            )),
            'expire' => new Type\Datetime(array(
                'description' => t('Время потери актуальности'),
                'hint' => t('Сообщения, утратившие актуальность будут удаляться')
            ))
        ));

        $this->addIndex(array('expire', 'user_id'), self::INDEX_KEY);
    }
}