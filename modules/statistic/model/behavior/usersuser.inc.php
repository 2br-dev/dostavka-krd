<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Statistic\Model\Behavior;

/**
* Объект - Расширения пользователя
*/
class UsersUser extends \RS\Behavior\BehaviorAbstract
{  
        
    /**
    * Возвращает источник пользователя
    * 
    * @return \Statistic\Model\Orm\Source
    */
    function getSource()
    {  
        $user = $this->owner; //Расширяемый объект, в нашем случае - пользователь.
        return new \Statistic\Model\Orm\Source($user['source_id']);
    }
}

