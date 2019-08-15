<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace DostavkaApi\Config;
use RS\Event\HandlerAbstract;
use RS\Orm\Type;

class Handlers extends HandlerAbstract
{
    function init()
    {
        $this->bind('orm.init.catalog-dir')
             ->bind('initialize');
    }

    public static function ormInitCatalogDir(\Catalog\Model\Orm\Dir $dir)
    {
        $dir->getPropertyIterator()->append(array(
            t('Основные'),
                'palette_type' => new Type\Integer(array(
                    'maxLength' => 2,
                    'default' => 0,
                    'description' => t('Цветовая схема')
                ))
        ));

    }

    public static function initialize()
    {
        \Catalog\Model\Orm\Dir::attachClassBehavior(new \DostavkaApi\Model\Behavior\DirApiCustom());
    }
}
