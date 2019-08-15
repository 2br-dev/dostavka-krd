<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\HtmlFilterType;

/**
* Класс поиска по простым текстовым свойствам товара со списком в отчетах статистики
*/
class ProductSelect extends \RS\Html\Filter\Type\Select
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
        if (!$q->issetTable(new \Catalog\Model\Orm\Product())){
            $q->leftjoin(new \Catalog\Model\Orm\Product(), 'OI.entity_id=P.id', 'P');
        }

        if ($this->value && !empty($this->value)){
            $q->where($this->getWhere());
        }

        return $q;
    }

}

