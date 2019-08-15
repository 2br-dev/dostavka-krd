<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\HtmlFilterType;

/**
* Класс поиска по простым текстовым свойствам товара в отчетах статистики
*/
class UserFilter extends \RS\Html\Filter\Type\User
{
        
        
    /**
    * Модифицирует запрос
    * 
    * @param \RS\Orm\Request $q
    * @return \RS\Orm\Request
    */
    function modificateQuery(\RS\Orm\Request $q)
    {
        //Если указано значение и таблица ещё не присоединена
        if (!$q->issetTable(new \Users\Model\Orm\User())){
            $q->leftjoin(new \Users\Model\Orm\User(), 'ORDERS.user_id=USERS.id', 'USERS');
        }

        if (!empty($this->value)){
            $q->where($this->getWhere());
        }

        return $q;
    }

}

