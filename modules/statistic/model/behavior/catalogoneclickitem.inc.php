<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Statistic\Model\Behavior;

/**
* Объект - Купить в один клик
*/
class CatalogOneClickItem extends \RS\Behavior\BehaviorAbstract
{  
        
    /**
    * Возвращает источник пользователя
    * 
    * @return \Statistic\Model\Orm\Source
    */
    function getSource()
    {  
        $oneclick = $this->owner; //Расширяемый объект, в нашем случае - пользователь.

        $source = new \Statistic\Model\Orm\Source($oneclick['source_id']);

        if (!empty($oneclick['utm_source'])){ //Сравним последнюю UTM метку с тем, есть
            $source['utm_source']   = $oneclick['utm_source'];
            $source['utm_medium']   = $oneclick['utm_medium'];
            $source['utm_campaign'] = $oneclick['utm_campaign'];
            $source['utm_term']     = $oneclick['utm_term'];
            $source['utm_content']  = $oneclick['utm_content'];
            $source['dateof']       = $oneclick['utm_dateof'];
        }
        return $source;
    }
}

