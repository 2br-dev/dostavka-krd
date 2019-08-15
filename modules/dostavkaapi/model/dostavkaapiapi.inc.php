<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace DostavkaApi\Model;

use RS\Module\AbstractModel\TreeCookieList;


class DostavkaApiApi extends TreeCookieList
{
    public static function getCategoryPaletteByAlias($alias)
    {
        $dir_id = \RS\Orm\request::make()->select('id')
        ->from(new \Catalog\Model\Orm\Dir())
        ->where(['alias' => $alias])
        ->exec()
        ->getOneField('id');
        $dir_api = new \Catalog\Model\DirApi();
        $parents_id = array_pop($dir_api->getParentsId($dir_id));

        return \RS\Orm\request::make()->select('palette_type')
                                      ->from(new \Catalog\Model\Orm\Dir())
                                      ->where(['id' => $parents_id])
                                      ->exec()
                                      ->getOneField('palette_type');
    }

    public static function getProductPaletteByAlias($alias)
    {
        $product = \RS\Orm\request::make()
        ->from(new \Catalog\Model\Orm\Product())
        ->where(['alias' => $alias])
        ->object();
        $product_dir = $product->getMainDir();
       
        $dir_api = new \Catalog\Model\DirApi();
        $parents_id = array_pop($dir_api->getParentsId($product_dir['id']));

        return \RS\Orm\request::make()->select('palette_type')
                                      ->from(new \Catalog\Model\Orm\Dir())
                                      ->where(['id' => $parents_id])
                                      ->exec()
                                      ->getOneField('palette_type');
    }
}