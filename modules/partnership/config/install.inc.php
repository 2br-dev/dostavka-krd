<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Config;

/**
* Класс отвечает за установку и обновление модуля
*/
class Install extends \RS\Module\AbstractInstall
{
    
    /**
    * Функция обновления модуля, вызывается только при обновлении
    */
    function update()
    {
        $result = parent::update();
        
        if ($result) { //Обновим пользователя и товар
            $order = new \Shop\Model\Orm\Order();
            $order->dbUpdate();
            
            $oneclickitem = new \Catalog\Model\Orm\OneClickItem();
            $oneclickitem->dbUpdate();
            
            $reserve = new \Shop\Model\Orm\Reservation();
            $reserve->dbUpdate();
        }
        return $result;
    }     
    
}
