<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\Orm;

use RS\Config\Loader as ConfigLoader;
use RS\Db\Exception as DbException;
use RS\Exception as RSException;
use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Type;

class Log extends OrmObject
{
    protected static $table = 'users_log';
    
    function _init()
    {
        parent::_init();

        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата'),
            )),
            'class' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Класс события'),
            )),
            'oid' => new Type\Integer(array(
                'description' => t('ID объекта над которым произошло событие'),
            )),
            'group' => new Type\Integer(array(
                'description' => t('ID Группы (перезаписывается, если событие происходит в рамках одной группы)'),
            )),
            'user_id' => new Type\Bigint(array(
                'description' => t('ID Пользователя'),
            )),
            '_serialized' => new Type\Varchar(array(
                'maxLength' => '4000',
                'description' => t('Дополнительные данные (скрыто)'),
                'visible' => false,
            )),
            'data' => new Type\ArrayList(array(
                'description' => t('Дополнительные данные'),
            )),
        ));
        
        $this
            ->addIndex(array('class', 'user_id', 'group'), self::INDEX_UNIQUE)
            ->addIndex(array('site_id', 'class'));
    }

    /**
     * @param string $save_flag
     * @throws DbException
     * @throws RSException
     */
    function afterWrite($save_flag)
    {
        $config = ConfigLoader::byModule('users');

        $clear_random = $config['clear_random'];
        $clear_hours  = $config['clear_for_last_time'];

        //Попытаемся очитить логи в зависимости от вероятности
        srand();
        $value = rand(0,100);
        if ($value <= $clear_random){ //Если попали в вероятность
            $time = time()-($clear_hours * 60 * 60);
            //Очистим логи
            OrmRequest::make()
                ->delete()
                ->from($this->_getTable())
                ->where("dateof < '".date('Y-m-d H:i:s',$time)."'")
                ->exec();
        }
    }

    function beforeWrite($flag)
    {
        $this['_serialized'] = serialize($this['data']);
    }
    
    function afterObjectLoad()
    {
        $this['data'] = @unserialize($this['_serialized']);
    }
}
