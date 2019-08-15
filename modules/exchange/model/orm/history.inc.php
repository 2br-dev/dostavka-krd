<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Orm;

use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Type;

/**
 * Объект записи в историю запросов при импорте
 * Каждый отдельный get или post запрос создает новую запись в таблице `exchange_history`
 */
class History extends OrmObject
{
    protected static $table = 'exchange_history';

    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата обмена'),
                'index' => true
            )),
            'method' => new Type\Varchar(array(
                'maxLength' => '10',
                'description' => t('Метод'),
            )),
            'type' => new Type\Varchar(array(
                'maxLength' => '20',
                'description' => t('Тип'),
            )),
            'mode' => new Type\Varchar(array(
                'maxLength' => '20',
                'description' => t('Режим'),
            )),
            'query' => new Type\Varchar(array(
                'maxLength' => '1024',
                'description' => t('Запрос'),
            )),
            'postsize' => new Type\Integer(array(
                'maxLength' => '10',
                'description' => t('Размер post'),
            )),
            'response' => new Type\Text(array(
                'description' => t('Ответ сервера'),
            )),
            'duration' => new Type\Decimal(array(
                'maxLength' => 10,
                'decimal' => 3,
                'description' => t('Время обработки запроса сек.'),
            )),
            'memory_peak' => new Type\Integer(array(
                'maxLength' => 10,
                'description' => t('Памяти израсходовано'),
            )),
            'readed_nodes' => new Type\Integer(array(
                'maxLength' => 10,
                'description' => t('Позиция, на которой остановился импорт'),
            )),
        ));
    }

    static public function removeOldItems()
    {
        $total = OrmRequest::make()
            ->from(new self)
            ->count();

        $limit = 400;
        if ($total <= $limit) return;

        OrmRequest::make()
            ->from(new self)
            ->orderby("id")
            ->limit($total - $limit)
            ->delete()
            ->exec();
    }
}
