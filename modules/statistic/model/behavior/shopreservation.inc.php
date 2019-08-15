<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Statistic\Model\Behavior;

/**
* Объект - Расширения объекта заказать
*/
class ShopReservation extends \RS\Behavior\BehaviorAbstract
{  
        
    /**
    * Возвращает источник пользователя
    * 
    * @return \Statistic\Model\Orm\Source
    */
    function getSource()
    {  
        $reservation = $this->owner; //Расширяемый объект, в нашем случае - пользователь.

        $source = new \Statistic\Model\Orm\Source($reservation['source_id']);

        if (!empty($reservation['utm_source'])){ //Сравним последнюю UTM метку с тем, есть
            $source['utm_source']   = $reservation['utm_source'];
            $source['utm_medium']   = $reservation['utm_medium'];
            $source['utm_campaign'] = $reservation['utm_campaign'];
            $source['utm_term']     = $reservation['utm_term'];
            $source['utm_content']  = $reservation['utm_content'];
            $source['dateof']       = $reservation['utm_dateof'];
        }

        return $source;
    }
}

