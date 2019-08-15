<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;

use RS\Orm\ControllerParamObject;
use RS\Orm\PropertyIterator;
use RS\Orm\Type;

/**
 * Класс описывает стандартные блоки-контроллеры, имеющие только одно действие и шаблон.
 */
class StandartBlock extends Block
{
    protected $default_params = array(
        'indexTemplate' => '', //Должен быть задан у наследника
    );

    /**
     * Возвращает ORM объект, содержащий настриваемые параметры или false в случае,
     * если контроллер не поддерживает настраиваемые параметры
     *
     * @return ControllerParamObject
     */
    function getParamObject()
    {
        $controller_param_object = new ControllerParamObject(
            new PropertyIterator(array(
                'indexTemplate' => new Type\Template(array(
                    'description' => t('Шаблон'),
                    'attr' => array(array(
                        'placeholder' => $this->default_params['indexTemplate']
                    ))
                ))
            ))
        );
        $controller_param_object->setParentObject($this);
        $controller_param_object->setParentParamMethod('Param');
        return $controller_param_object;
    }
}
