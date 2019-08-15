<?php
namespace DostavkaApi\Model\Behavior;
use \RS\Orm\Type;

/**
* Объект - Расширения пользователя
*/
class DirApiCustom extends \RS\Behavior\BehaviorAbstract
{
    function getDirPalette() {
        $dir = $this->owner;
        $dir_api = new \Catalog\Model\DirApi();
        $parents_id = array_pop($dir_api->getParentsId($dir['id']));
        //
        return \RS\Orm\request::make()->select('palette_type')
                                      ->from(new \Catalog\Model\Orm\Dir())
                                      ->where(['id' => $parents_id])
                                      ->exec()
                                      ->getOneField('palette_type');
    }

}