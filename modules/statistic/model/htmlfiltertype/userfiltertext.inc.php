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
class UserFilterText extends \RS\Html\Filter\Type\Text
{
        
        
    /**
    * Модифицирует запрос
    * 
    * @param \RS\Orm\Request $q
    * @return \RS\Orm\Request
    */
    function modificateQuery(\RS\Orm\Request $q)
    {
        if (!empty($this->value)){
            $q->where($this->getWhere());
        }

        return $q;
    }

}

