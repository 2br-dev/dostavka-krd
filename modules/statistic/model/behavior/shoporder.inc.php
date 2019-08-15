<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Statistic\Model\Behavior;

/**
* Объект - Расширения заказа
*/
class ShopOrder extends \RS\Behavior\BehaviorAbstract
{  
        
    /**
    * Возвращает источник пользователя
    * 
    * @return \Statistic\Model\Orm\Source
    */
    function getSource()
    {  
        $order = $this->owner; //Расширяемый объект, в нашем случае - пользователь.
        $source = new \Statistic\Model\Orm\Source($order['source_id']);

        if (!empty($order['utm_source'])){ //Сравним последнюю UTM метку с тем, есть
            $source['utm_source']   = $order['utm_source'];
            $source['utm_medium']   = $order['utm_medium'];
            $source['utm_campaign'] = $order['utm_campaign'];
            $source['utm_term']     = $order['utm_term'];
            $source['utm_content']  = $order['utm_content'];
            $source['dateof']       = $order['utm_dateof'];
        }

        return $source;
    }
}

