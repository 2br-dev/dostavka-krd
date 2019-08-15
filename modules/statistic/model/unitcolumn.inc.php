<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model;

/**
* Денежный тип табличной ячейки, отображает отформатированную сумму с базовой валютой 
*/
class UnitColumn extends \RS\Html\Table\Type\Text
{
    /**
    * Устанавливает единицу измерения, которая будет приписана в конце значения
    * 
    * @param mixed $unit
    * @return MoneyColumn
    */
    function setUnit($unit)
    {
        $this->property['unit'] = $unit;
        return $this;
    }
    
    function getValue()
    {
        return \RS\Helper\CustomView::cost($this->value, "<small>".$this->property['unit']."</small>");
    }
    
}
